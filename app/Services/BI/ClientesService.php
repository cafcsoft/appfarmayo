<?php

namespace App\Services\BI;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

/**
 * ClientesService — Análisis de fidelidad y segmentación RFM.
 *
 * Unifica clientes de facturas (farmacia.clientes) y tickets (farmacia.client)
 * en una sola fuente de análisis. Excluye clientes genéricos.
 */
class ClientesService
{
    use CacheHelper;

    public function __construct(private VentasService $ventas) {}

    // =========================================================================
    // FUENTE UNIFICADA DE CLIENTES
    // =========================================================================

    /**
     * Resumen de compras por cliente (historial completo últimos 5 años).
     * Excluye clientes genéricos (id=0, nombre vacío, SIN NOMBRE, XXX).
     *
     * @return Collection con: id_cliente, nombre, origen, primera_compra,
     *         ultima_compra, dias_inactivo, total_compras, venta_total,
     *         unidades, margen_total, ticket_promedio
     */
    public function resumenPorCliente(string $desde = null, string $hasta = null): Collection
    {
        $desde ??= now()->subYears(5)->startOfYear()->toDateString();
        $hasta ??= now()->toDateString();

        return $this->cacheJson("bi_resumen_clientes_{$desde}_{$hasta}", 3600 * 4, function () use ($desde, $hasta) {

            // ---- Facturas ----
            $facturas = DB::table('fact_cab as fc')
                ->join('fact_lin as fl', 'fc.n_compro', '=', 'fl.n_compro')
                ->leftJoin(DB::raw('farmacia.clientes as cl'), 'fc.codi_clie', '=', 'cl.codi_clie')
                ->where('fc.anulado', 0)
                ->where('fl.anulado', 0)
                ->whereBetween('fc.fecha', [$desde, $hasta])
                ->whereNotNull('fc.codi_clie')
                ->where('fc.codi_clie', '!=', 0)
                ->where('fc.codi_clie', '!=', '')
                ->where('fc.codi_clie', '!=', '13350')
                ->where('fc.codi_clie', '!=', '3714389-1')
                ->where('fc.codi_clie', '!=', '80051943-4')
                ->whereNotNull('cl.nomb_clie')
                ->where('cl.nomb_clie', '!=', '')
                ->whereRaw("UPPER(TRIM(cl.nomb_clie)) NOT IN ('SIN NOMBRE','XXX','SIN_NOMBRE','N/A')")
                ->selectRaw("
                    fc.codi_clie AS id_cliente,
                    CONCAT(cl.nomb_clie, ' ', COALESCE(cl.apel_clie, '')) AS nombre,
                    'factura' AS origen,
                    MIN(fc.fecha) AS primera_compra,
                    MAX(fc.fecha) AS ultima_compra,
                    COUNT(DISTINCT fc.n_compro) AS total_compras,
                    SUM(fl.total) AS venta_total,
                    SUM(fl.cantidad) AS unidades,
                    SUM(CAST(fl.total AS SIGNED) - CAST((fl.costo1 * fl.cantidad) AS SIGNED)) AS margen_total
                ")
                ->groupBy('fc.codi_clie', 'cl.nomb_clie', 'cl.apel_clie');

            // ---- Tickets ----
            $tickets = DB::table('ticket_cab as tc')
                ->join('ticket_lin as tl', 'tc.n_compro', '=', 'tl.n_compro')
                ->leftJoin(DB::raw('farmacia.client as ct'), 'tc.idcliente', '=', 'ct.IDCLIENTE')
                ->where('tc.anulado', 0)
                ->where('tl.anulado', 0)
                ->whereBetween('tc.fecha', [$desde, $hasta])
                ->whereNotNull('tc.idcliente')
                ->where('tc.idcliente', '!=', 0)
                ->where('tc.idcliente', '!=', '')
                ->where('tc.idcliente', '!=', '13350')
                ->where('tc.idcliente', '!=', '3714389-1')
                ->where('tc.idcliente', '!=', '80051943-4')
                ->whereNotNull('ct.NOMBRE')
                ->where('ct.NOMBRE', '!=', '')
                ->whereRaw("UPPER(TRIM(ct.NOMBRE)) NOT IN ('SIN NOMBRE','XXX','SIN_NOMBRE','N/A')")
                ->selectRaw("
                    tc.idcliente AS id_cliente,
                    ct.NOMBRE AS nombre,
                    'ticket' AS origen,
                    MIN(tc.fecha) AS primera_compra,
                    MAX(tc.fecha) AS ultima_compra,
                    COUNT(DISTINCT tc.n_compro) AS total_compras,
                    SUM(tl.total) AS venta_total,
                    SUM(tl.cantidad) AS unidades,
                    SUM(CAST(tl.total AS SIGNED) - CAST((tl.costo1 * tl.cantidad) AS SIGNED)) AS margen_total
                ")
                ->groupBy('tc.idcliente', 'ct.NOMBRE');

            // Unir y agregar por cliente
            $union = $facturas->unionAll($tickets);

            $datos = DB::table(DB::raw('(' . $union->toSql() . ') as c'))
                ->mergeBindings($union)
                ->selectRaw('
                    id_cliente,
                    MAX(nombre) AS nombre,
                    MAX(origen) AS origen,
                    MIN(primera_compra) AS primera_compra,
                    MAX(ultima_compra) AS ultima_compra,
                    DATEDIFF(CURDATE(), MAX(ultima_compra)) AS dias_inactivo,
                    SUM(total_compras) AS total_compras,
                    SUM(venta_total) AS venta_total,
                    SUM(unidades) AS unidades,
                    SUM(margen_total) AS margen_total,
                    SUM(venta_total) / NULLIF(SUM(total_compras), 0) AS ticket_promedio
                ')
                ->groupBy('id_cliente')
                ->get();

            // Calcular ventana de frecuencia promedio (días entre compras)
            return $datos->map(function ($c) {
                $diasTotal = now()->diffInDays($c->primera_compra);
                $frecuencia = $c->total_compras > 1 && $diasTotal > 0
                    ? round($diasTotal / ($c->total_compras - 1), 0)
                    : null;

                return (object) array_merge((array) $c, [
                    'frecuencia_dias' => $frecuencia,
                    'segmento'        => $this->clasificarCliente($c),
                ]);
            });
        });
    }
    // =========================================================================
    // SEGMENTACIÓN
    // =========================================================================

    /**
     * Clasifica un cliente según su comportamiento de compra.
     *
     * Nuevo:      primera compra en los últimos 30 días
     * Activo:     última compra en los últimos 30 días
     * Recurrente: 3+ compras en últimos 180 días
     * Fiel:       6+ compras en últimos 365 días
     * En Riesgo:  última compra entre 60 y 120 días
     * Perdido:    última compra hace más de 120 días
     * Ocasional:  1 o 2 compras históricas
     */
    public function clasificarCliente(object $cliente): string
    {
        $diasInactivo   = (int) $cliente->dias_inactivo;
        $totalCompras   = (int) $cliente->total_compras;
        $primerCompra   = \Carbon\Carbon::parse($cliente->primera_compra);
        $diasDesdeAlta  = $primerCompra->diffInDays(now());

        if ($diasDesdeAlta <= 30 && $totalCompras <= 2) {
            return 'Nuevo';
        }

        if ($totalCompras <= 2) {
            return 'Ocasional';
        }

        if ($diasInactivo > 120) {
            return 'Perdido';
        }

        if ($diasInactivo >= 60 && $diasInactivo <= 120) {
            return 'En Riesgo';
        }

        if ($totalCompras >= 6) {
            return 'Fiel';
        }

        if ($totalCompras >= 3 && $diasInactivo <= 30) {
            return 'Recurrente';
        }

        return 'Activo';
    }

    /**
     * Conteo de clientes por segmento.
     */
    public function distribucionSegmentos(string $desde = null, string $hasta = null): Collection
    {
        return $this->resumenPorCliente($desde, $hasta)
            ->groupBy('segmento')
            ->map(fn ($grupo, $segmento) => (object) [
                'segmento' => $segmento,
                'cantidad' => $grupo->count(),
                'venta'    => round($grupo->sum('venta_total'), 2),
                'margen'   => round($grupo->sum('margen_total'), 2),
            ])
            ->values();
    }

    // =========================================================================
    // RANKINGS DE CLIENTES
    // =========================================================================

    /** Ranking de mejores clientes por venta acumulada. */
    public function rankingPorVenta(int $limite = 30, string $desde = null, string $hasta = null): Collection
    {
        return $this->resumenPorCliente($desde, $hasta)
            ->sortByDesc('venta_total')
            ->take($limite)
            ->values();
    }

    /** Ranking de mejores clientes por margen bruto. */
    public function rankingPorMargen(int $limite = 30, string $desde = null, string $hasta = null): Collection
    {
        return $this->resumenPorCliente($desde, $hasta)
            ->sortByDesc('margen_total')
            ->take($limite)
            ->values();
    }

    /** Ranking de clientes por frecuencia de compra (más transacciones). */
    public function rankingPorFrecuencia(int $limite = 30, string $desde = null, string $hasta = null): Collection
    {
        return $this->resumenPorCliente($desde, $hasta)
            ->sortByDesc('total_compras')
            ->take($limite)
            ->values();
    }

    // =========================================================================
    // ANÁLISIS DE COMPORTAMIENTO
    // =========================================================================

    /** Clientes que dejaron de comprar hace más de N días. */
    public function queDejaronDeComprar(int $dias = 120, string $desde = null, string $hasta = null): Collection
    {
        return $this->resumenPorCliente($desde, $hasta)
            ->filter(fn ($c) => $c->dias_inactivo > $dias)
            ->sortByDesc('venta_total')
            ->values();
    }

    /**
     * Clientes reactivados: tenían más de 90 días sin comprar y volvieron
     * a comprar en los últimos 30 días.
     */
    public function reactivados(string $desde = null, string $hasta = null): Collection
    {
        $desde ??= now()->subYears(5)->startOfYear()->toDateString();
        $hasta ??= now()->toDateString();

        // Fecha de penúltima compra por cliente
        $penultimas = DB::table('fact_cab as fc')
            ->where('fc.anulado', 0)
            ->whereBetween('fc.fecha', [$desde, $hasta])
            ->whereNotNull('fc.codi_clie')
            ->where('fc.codi_clie', '!=', 0)
            ->where('fc.codi_clie', '!=', '13350')
            ->selectRaw('codi_clie AS id_cliente, fecha')
            ->orderByDesc('fecha')
            ->get()
            ->groupBy('id_cliente')
            ->map(fn ($g) => $g->skip(1)->first());

        $activos = $this->resumenPorCliente()
            ->filter(fn ($c) => $c->dias_inactivo <= 30);

        return $activos->filter(function ($c) use ($penultimas) {
            $penultima = $penultimas[$c->id_cliente] ?? null;
            if (! $penultima) {
                return false;
            }
            $diasEntre = now()->diffInDays($penultima->fecha);

            return $diasEntre > 90;
        })->values();
    }

    /** Clientes con caída de consumo: venta reciente < 70% de venta período anterior. */
    public function conCaidaDeConsumo(int $meses = 3): Collection
    {
        return $this->_comparacionConsumo($meses, 'caida');
    }

    /** Clientes con aumento de consumo: venta reciente > 130% de venta período anterior. */
    public function conAumentoDeConsumo(int $meses = 3): Collection
    {
        return $this->_comparacionConsumo($meses, 'aumento');
    }

    /**
     * KPIs globales de clientes para el dashboard.
     */
    public function kpisClientes(string $desde = null, string $hasta = null): array
    {
        $desde ??= now()->subYears(5)->startOfYear()->toDateString();
        $hasta ??= now()->toDateString();

        return (array) $this->cacheJson("bi_kpis_clientes_{$desde}_{$hasta}", 3600 * 2, function () use ($desde, $hasta) {
            $todos = $this->resumenPorCliente($desde, $hasta);

            return [
                'total_clientes'   => $todos->count(),
                'activos_30d'      => $todos->filter(fn ($c) => $c->dias_inactivo <= 30)->count(),
                'nuevos_30d'       => $todos->filter(fn ($c) => $c->segmento === 'Nuevo')->count(),
                'en_riesgo'        => $todos->filter(fn ($c) => $c->segmento === 'En Riesgo')->count(),
                'perdidos'         => $todos->filter(fn ($c) => $c->segmento === 'Perdido')->count(),
                'fieles'           => $todos->filter(fn ($c) => $c->segmento === 'Fiel')->count(),
                'ticket_promedio'  => round($todos->avg('ticket_promedio'), 2),
                'venta_top10_pct'  => $this->_concentracionTop(10, $todos),
            ];
        });
    }

    // =========================================================================
    // HELPERS INTERNOS
    // =========================================================================

    private function _comparacionConsumo(int $meses, string $tipo): Collection
    {
        $corte   = now()->subMonths($meses)->toDateString();
        $inicio  = now()->subMonths($meses * 2)->toDateString();

        $period1 = $this->_ventasPorClientePeriodo($inicio, $corte);
        $period2 = $this->_ventasPorClientePeriodo($corte, now()->toDateString());

        return $period2->map(function ($c) use ($period1, $tipo) {
            $ant   = $period1->firstWhere('id_cliente', $c->id_cliente);
            $vAnt  = $ant?->venta_total ?? 0;
            $ratio = $vAnt > 0 ? $c->venta_total / $vAnt : 0;

            return (object) [
                'id_cliente'  => $c->id_cliente,
                'nombre'      => $c->nombre,
                'venta_p1'    => round($vAnt, 2),
                'venta_p2'    => round($c->venta_total, 2),
                'variacion'   => round(($ratio - 1) * 100, 1),
            ];
        })->filter(fn ($c) => $tipo === 'caida' ? $c->variacion < -30 : $c->variacion > 30)
            ->sortBy($tipo === 'caida' ? 'variacion' : fn ($c) => -$c->variacion)
            ->take(20)
            ->values();
    }

    private function _ventasPorClientePeriodo(string $desde, string $hasta): Collection
    {
        $facturas = DB::table('fact_cab as fc')
            ->join('fact_lin as fl', 'fc.n_compro', '=', 'fl.n_compro')
            ->leftJoin(DB::raw('farmacia.clientes as cl'), 'fc.codi_clie', '=', 'cl.codi_clie')
            ->where('fc.anulado', 0)->where('fl.anulado', 0)
            ->whereBetween('fc.fecha', [$desde, $hasta])
            ->whereNotNull('fc.codi_clie')->where('fc.codi_clie', '!=', 0)
            ->where('fc.codi_clie', '!=', '13350')
            ->where('fc.codi_clie', '!=', '3714389-1')
            ->where('fc.codi_clie', '!=', '80051943-4')
            ->selectRaw("fc.codi_clie AS id_cliente,
                CONCAT(cl.nomb_clie,' ',COALESCE(cl.apel_clie,'')) AS nombre,
                SUM(fl.total) AS venta_total")
            ->groupBy('fc.codi_clie', 'cl.nomb_clie', 'cl.apel_clie');

        $tickets = DB::table('ticket_cab as tc')
            ->join('ticket_lin as tl', 'tc.n_compro', '=', 'tl.n_compro')
            ->leftJoin(DB::raw('farmacia.client as ct'), 'tc.idcliente', '=', 'ct.IDCLIENTE')
            ->where('tc.anulado', 0)->where('tl.anulado', 0)
            ->whereBetween('tc.fecha', [$desde, $hasta])
            ->whereNotNull('tc.idcliente')->where('tc.idcliente', '!=', 0)
            ->where('tc.idcliente', '!=', '13350')
            ->where('tc.idcliente', '!=', '3714389-1')
            ->where('tc.idcliente', '!=', '80051943-4')
            ->selectRaw("tc.idcliente AS id_cliente,
                ct.NOMBRE AS nombre,
                SUM(tl.total) AS venta_total")
            ->groupBy('tc.idcliente', 'ct.NOMBRE');

        $union = $facturas->unionAll($tickets);

        return DB::table(DB::raw('(' . $union->toSql() . ') as r'))
            ->mergeBindings($union)
            ->selectRaw('id_cliente, MAX(nombre) AS nombre, SUM(venta_total) AS venta_total')
            ->groupBy('id_cliente')
            ->get();
    }

    private function _concentracionTop(int $top, Collection $clientes): float
    {
        $total = $clientes->sum('venta_total');
        if ($total <= 0) {
            return 0;
        }
        $topVenta = $clientes->sortByDesc('venta_total')->take($top)->sum('venta_total');

        return round(($topVenta / $total) * 100, 1);
    }
}
