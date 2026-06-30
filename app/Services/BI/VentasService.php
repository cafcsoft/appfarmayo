<?php

namespace App\Services\BI;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * VentasService — Núcleo del módulo BI.
 *
 * Todas las consultas usan UNION de facturas + tickets como fuente única.
 * Aplica reglas de negocio: anulado=0, últimos 5 años.
 * Los resultados pesados se cachean por 24 horas (cache de DB configurada).
 */
class VentasService
{
    use CacheHelper;

    /** Fecha de inicio: 5 años atrás desde el 1ro del año en curso. */
    private \Carbon\CarbonInterface $desde5;

    public function __construct()
    {
        $this->desde5 = now()->subYears(5)->startOfYear();
    }

    // =========================================================================
    // QUERIES BASE — UNION consolidada de facturas y tickets
    // =========================================================================

    /**
     * Retorna el SQL base de la unión de CABECERAS únicamente.
     * MUCHO MÁS RÁPIDO para KPIs globales que no requieren detalle de productos o márgenes.
     * Utiliza la nueva columna 'gran_total' optimizada.
     */
    public function unionQueryCabecera(string $desde = null, string $hasta = null): \Illuminate\Database\Query\Builder
    {
        $desde = $desde ?? $this->desde5->toDateString();
        $hasta = $hasta ?? now()->toDateString();

        $facturas = DB::table('fact_cab as fc')
            ->where('fc.anulado', 0)
            ->where('fc.codi_clie', '!=', '13350')
            ->where('fc.codi_clie', '!=', '3714389-1')
            ->where('fc.codi_clie', '!=', '80051943-4')
            ->whereBetween('fc.fecha', [$desde, $hasta])
            ->select([
                'fc.fecha',
                DB::raw('YEAR(fc.fecha) AS anio'),
                DB::raw('MONTH(fc.fecha) AS mes'),
                DB::raw('DAYOFWEEK(fc.fecha) AS dia_semana'),
                DB::raw("'factura' AS origen"),
                'fc.n_compro',
                'fc.codi_vend AS vendedor',
                'fc.codi_clie AS id_cliente',
                'fc.turno',
                'fc.nromaquina AS caja',
                DB::raw('COALESCE(fc.gran_total, 0) AS venta'),
            ]);

        $tickets = DB::table('ticket_cab as tc')
            ->where('tc.anulado', 0)
            ->where('tc.idcliente', '!=', '13350')
            ->where('tc.idcliente', '!=', '3714389-1')
            ->where('tc.idcliente', '!=', '80051943-4')
            ->whereBetween('tc.fecha', [$desde, $hasta])
            ->select([
                'tc.fecha',
                DB::raw('YEAR(tc.fecha) AS anio'),
                DB::raw('MONTH(tc.fecha) AS mes'),
                DB::raw('DAYOFWEEK(tc.fecha) AS dia_semana'),
                DB::raw("'ticket' AS origen"),
                'tc.n_compro',
                'tc.codi_vend AS vendedor',
                'tc.idcliente AS id_cliente',
                'tc.turno',
                'tc.nromaquina AS caja',
                DB::raw('COALESCE(tc.gran_total, 0) AS venta'),
            ]);

        return $facturas->unionAll($tickets);
    }

    /**
     * Retorna el SQL base de la unión consolidada de ventas (DETALLE).
     * Necesario para cálculos de MARGEN y CANTIDADES por producto.
     */
    public function unionQuery(string $desde = null, string $hasta = null): \Illuminate\Database\Query\Builder
    {
        $desde = $desde ?? $this->desde5->toDateString();
        $hasta = $hasta ?? now()->toDateString();

        // --- Facturas ---
        $facturas = DB::table('fact_cab as fc')
            ->join('fact_lin as fl', 'fc.n_compro', '=', 'fl.n_compro')
            ->where('fc.anulado', 0)
            ->where('fl.anulado', 0)
            ->where('fl.codigo', '!=', '146211')
            ->where('fc.codi_clie', '!=', '13350')
            ->where('fc.codi_clie', '!=', '3714389-1')
            ->where('fc.codi_clie', '!=', '80051943-4')
            ->whereBetween('fc.fecha', [$desde, $hasta])
            ->select([
                'fc.fecha',
                DB::raw('YEAR(fc.fecha) AS anio'),
                DB::raw('MONTH(fc.fecha) AS mes'),
                DB::raw('DAYOFWEEK(fc.fecha) AS dia_semana'),
                DB::raw("'factura' AS origen"),
                'fc.n_compro',
                'fc.codi_vend AS vendedor',
                'fc.codi_clie AS id_cliente',
                'fc.turno',
                'fc.nromaquina AS caja',
                'fl.codigo AS cod_producto',
                'fl.nombre AS producto',
                'fl.cantidad',
                'fl.total AS venta',
                DB::raw("'CREDITO' AS forma_pago"),
                DB::raw('(fl.costo1 * fl.cantidad) AS costo'),
                DB::raw('(CAST(fl.total AS SIGNED) - CAST((fl.costo1 * fl.cantidad) AS SIGNED)) AS margen'),
            ]);

        // --- Tickets ---
        $tickets = DB::table('ticket_cab as tc')
            ->join('ticket_lin as tl', 'tc.n_compro', '=', 'tl.n_compro')
            ->where('tc.anulado', 0)
            ->where('tl.anulado', 0)
            ->where('tl.codigo', '!=', '146211')
            ->where('tc.idcliente', '!=', '13350')
            ->where('tc.idcliente', '!=', '3714389-1')
            ->where('tc.idcliente', '!=', '80051943-4')
            ->whereBetween('tc.fecha', [$desde, $hasta])
            ->select([
                'tc.fecha',
                DB::raw('YEAR(tc.fecha) AS anio'),
                DB::raw('MONTH(tc.fecha) AS mes'),
                DB::raw('DAYOFWEEK(tc.fecha) AS dia_semana'),
                DB::raw("'ticket' AS origen"),
                'tc.n_compro',
                'tc.codi_vend AS vendedor',
                'tc.idcliente AS id_cliente',
                'tc.turno',
                'tc.nromaquina AS caja',
                'tl.codigo AS cod_producto',
                'tl.nombre AS producto',
                'tl.cantidad',
                'tl.total AS venta',
                DB::raw("IF(tc.formapago = 'CONTADO', 'EFECTIVO', tc.formapago) AS forma_pago"),
                DB::raw('(tl.costo1 * tl.cantidad) AS costo'),
                DB::raw('(CAST(tl.total AS SIGNED) - CAST((tl.costo1 * tl.cantidad) AS SIGNED)) AS margen'),
            ]);

        return $facturas->unionAll($tickets);
    }


    // =========================================================================
    // KPIs POR PERÍODO
    // =========================================================================

    /**
     * KPIs para un período dado.
     */
    public function kpisPeriodo(string $desde = null, string $hasta = null): array
    {
        $desde ??= now()->startOfMonth()->toDateString();
        $hasta ??= now()->toDateString();

        return (array) $this->cacheJson("bi_kpis_{$desde}_{$hasta}", 3600, function () use ($desde, $hasta) {
            
            $inicio = Carbon::parse($desde);
            $fin    = Carbon::parse($hasta);
            $dias   = $inicio->diffInDays($fin) + 1;
            
            $desdeAnterior = $inicio->copy()->subDays($dias)->toDateString();
            $hastaAnterior = $inicio->copy()->subDay()->toDateString();

            // Venta y comprobantes desde CABECERA (Rápido)
            $calcCab = fn (string $d, string $h) => DB::table(DB::raw('(' . $this->unionQueryCabecera($d, $h)->toSql() . ') as v'))
                ->mergeBindings($this->unionQueryCabecera($d, $h))
                ->selectRaw('COALESCE(SUM(venta), 0) AS venta_total, COUNT(DISTINCT n_compro) AS comprobantes')
                ->first();

            // Margen y unidades desde DETALLE (Lento pero necesario)
            $calcDet = fn (string $d, string $h) => DB::table(DB::raw('(' . $this->unionQuery($d, $h)->toSql() . ') as v'))
                ->mergeBindings($this->unionQuery($d, $h))
                ->selectRaw('COALESCE(SUM(cantidad), 0) AS unidades, COALESCE(SUM(margen), 0) AS margen_total')
                ->first();

            $actualCab   = $calcCab($desde, $hasta);
            $actualDet   = $calcDet($desde, $hasta);
            $anteriorCab = $calcCab($desdeAnterior, $hastaAnterior);

            $variacion = $anteriorCab->venta_total > 0
                ? (($actualCab->venta_total - $anteriorCab->venta_total) / $anteriorCab->venta_total) * 100
                : 0;

            return [
                'venta_mes'        => round($actualCab->venta_total, 2),
                'unidades_mes'     => round($actualDet->unidades, 0),
                'margen_mes'       => round($actualDet->margen_total, 2),
                'pct_margen'       => $actualCab->venta_total > 0
                    ? round(($actualDet->margen_total / $actualCab->venta_total) * 100, 1)
                    : 0,
                'ticket_promedio'  => $actualCab->comprobantes > 0
                    ? round($actualCab->venta_total / $actualCab->comprobantes, 2)
                    : 0,
                'comprobantes'     => $actualCab->comprobantes,
                'venta_anterior'   => round($anteriorCab->venta_total, 2),
                'variacion_pct'    => round($variacion, 1),
            ];
        });
    }

    public function kpisMesActual(): array
    {
        return $this->kpisPeriodo(now()->startOfMonth()->toDateString(), now()->toDateString());
    }

    // =========================================================================
    // EVOLUCIÓN Y TENDENCIAS
    // =========================================================================

    public function ventasPorDia(int $dias = 30): Collection
    {
        $desde = now()->subDays($dias)->toDateString();
        $hasta = now()->toDateString();

        return $this->cacheJson("bi_ventas_dia_{$dias}", 3600, function () use ($desde, $hasta) {
            return DB::table(DB::raw('(' . $this->unionQuery($desde, $hasta)->toSql() . ') as v'))
                ->mergeBindings($this->unionQuery($desde, $hasta))
                ->selectRaw('fecha, SUM(venta) AS venta_total, SUM(cantidad) AS unidades, SUM(margen) AS margen_total, COUNT(DISTINCT n_compro) AS comprobantes')
                ->groupBy('fecha')->orderBy('fecha')->get();
        });
    }

    public function ventasPorMes(int $meses = 24): Collection
    {
        $desde = now()->subMonths($meses)->startOfMonth()->toDateString();

        return $this->cacheJson("bi_ventas_mes_{$meses}", 3600 * 6, function () use ($desde) {
            return DB::table(DB::raw('(' . $this->unionQuery($desde)->toSql() . ') as v'))
                ->mergeBindings($this->unionQuery($desde))
                ->selectRaw('anio, mes, CONCAT(anio, \'-\', LPAD(mes, 2, \'0\')) AS periodo, SUM(venta) AS venta_total, SUM(cantidad) AS unidades, SUM(margen) AS margen_total, COUNT(DISTINCT n_compro) AS comprobantes, SUM(venta) / COUNT(DISTINCT n_compro) AS ticket_promedio')
                ->groupByRaw('anio, mes')->orderByRaw('anio, mes')->get();
        });
    }

    public function evolucionTicketPromedio(int $meses = 24): Collection
    {
        $desde = now()->subMonths($meses)->startOfMonth()->toDateString();

        return $this->cacheJson("bi_ticket_evo_{$meses}", 3600, function () use ($desde) {
            return DB::table(DB::raw('(' . $this->unionQueryCabecera($desde)->toSql() . ') as v'))
                ->mergeBindings($this->unionQueryCabecera($desde))
                ->selectRaw("DATE_FORMAT(fecha, '%Y-%m') AS periodo, SUM(venta) AS venta_total, COUNT(DISTINCT n_compro) AS comprobantes, SUM(venta) / COUNT(DISTINCT n_compro) AS ticket_promedio")
                ->groupBy('periodo')->orderBy('periodo')->get();
        });
    }

    // =========================================================================
    // DESGLOSE POR DIMENSIÓN
    // =========================================================================

    public function ventasPorVendedor(string $desde = null, string $hasta = null): Collection
    {
        $desde ??= now()->startOfMonth()->toDateString();
        $hasta ??= now()->toDateString();

        return DB::table(DB::raw('(' . $this->unionQuery($desde, $hasta)->toSql() . ') as v'))
            ->mergeBindings($this->unionQuery($desde, $hasta))
            ->selectRaw('vendedor, SUM(venta) AS venta_total, SUM(cantidad) AS unidades, SUM(margen) AS margen_total, COUNT(DISTINCT n_compro) AS comprobantes, SUM(venta) / COUNT(DISTINCT n_compro) AS ticket_promedio, (SUM(margen) / NULLIF(SUM(venta), 0)) * 100 AS pct_margen')
            ->groupBy('vendedor')->orderByDesc('venta_total')->get();
    }

    public function ventasPorTurno(string $desde = null, string $hasta = null): Collection
    {
        $desde ??= now()->startOfMonth()->toDateString();
        $hasta ??= now()->toDateString();

        return DB::table(DB::raw('(' . $this->unionQueryCabecera($desde, $hasta)->toSql() . ') as v'))
            ->mergeBindings($this->unionQueryCabecera($desde, $hasta))
            ->selectRaw('COALESCE(turno, \'Sin turno\') AS turno, SUM(venta) AS venta_total, COUNT(DISTINCT n_compro) AS comprobantes')
            ->groupBy('turno')->orderByDesc('venta_total')->get();
    }

    public function ventasPorCaja(string $desde = null, string $hasta = null): Collection
    {
        $desde ??= now()->startOfMonth()->toDateString();
        $hasta ??= now()->toDateString();

        return DB::table(DB::raw('(' . $this->unionQueryCabecera($desde, $hasta)->toSql() . ') as v'))
            ->mergeBindings($this->unionQueryCabecera($desde, $hasta))
            ->selectRaw('COALESCE(caja, \'Sin caja\') AS caja, SUM(venta) AS venta_total, COUNT(DISTINCT n_compro) AS comprobantes')
            ->groupBy('caja')->orderByDesc('venta_total')->get();
    }

    public function ventasPorOrigen(string $desde = null, string $hasta = null): Collection
    {
        $desde ??= now()->startOfMonth()->toDateString();
        $hasta ??= now()->toDateString();

        return DB::table(DB::raw('(' . $this->unionQueryCabecera($desde, $hasta)->toSql() . ') as v'))
            ->mergeBindings($this->unionQueryCabecera($desde, $hasta))
            ->selectRaw('origen, SUM(venta) AS venta_total, COUNT(DISTINCT n_compro) AS comprobantes')
            ->groupBy('origen')->get();
    }

    // =========================================================================
    // COMPARACIONES Y ESTACIONALIDAD
    // =========================================================================

    public function comparacionAnioVsAnio(int $anio1, int $anio2): Collection
    {
        $desde = "{$anio1}-01-01";
        $hasta = "{$anio2}-12-31";

        return $this->cacheJson("bi_comp_{$anio1}_{$anio2}", 3600 * 6, function () use ($desde, $hasta, $anio1, $anio2) {
            return DB::table(DB::raw('(' . $this->unionQueryCabecera($desde, $hasta)->toSql() . ') as v'))
                ->mergeBindings($this->unionQueryCabecera($desde, $hasta))
                ->whereIn('anio', [$anio1, $anio2])
                ->selectRaw('anio, mes, SUM(venta) AS venta_total')
                ->groupByRaw('anio, mes')->orderByRaw('anio, mes')->get();
        });
    }

    public function estacionalidadMensual(): Collection
    {
        return $this->cacheJson('bi_estacionalidad_mensual', 3600 * 12, function () {
            $raw = DB::table(DB::raw('(' . $this->unionQueryCabecera()->toSql() . ') as v'))
                ->mergeBindings($this->unionQueryCabecera())
                ->selectRaw('mes, SUM(venta) AS venta_total, COUNT(DISTINCT n_compro) AS comprobantes')
                ->groupBy('mes')->orderBy('mes')->get();

            $media = $raw->avg('venta_total');
            return $raw->map(fn ($r) => (object) [
                'mes' => $r->mes,
                'venta_total' => $r->venta_total,
                'factor' => $media > 0 ? round($r->venta_total / $media, 4) : 1,
            ]);
        });
    }

    public function estacionalidadSemanal(): Collection
    {
        return $this->cacheJson('bi_estacionalidad_semanal', 3600 * 12, function () {
            $dias = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
            $raw = DB::table(DB::raw('(' . $this->unionQueryCabecera()->toSql() . ') as v'))
                ->mergeBindings($this->unionQueryCabecera())
                ->selectRaw('dia_semana, SUM(venta) AS venta_total')
                ->groupBy('dia_semana')->orderBy('dia_semana')->get();

            $media = $raw->avg('venta_total');
            return $raw->map(fn ($r) => (object) [
                'dia_num' => $r->dia_semana,
                'dia_nombre' => $dias[$r->dia_semana - 1] ?? "Día {$r->dia_semana}",
                'venta_total' => $r->venta_total,
                'factor' => $media > 0 ? round($r->venta_total / $media, 4) : 1,
            ]);
        });
    }

    public function variacionMensual(int $anio = null): Collection
    {
        $anio ??= now()->year;
        $meses = $this->ventasPorMes(36)->where('anio', $anio)->values();

        return $meses->map(function ($actual, $i) use ($meses) {
            $actual = is_array($actual) ? (object) $actual : $actual;
            $anterior = isset($meses[$i - 1]) ? (is_array($meses[$i - 1]) ? (object) $meses[$i - 1] : $meses[$i - 1]) : null;
            $variacion = ($anterior && $anterior->venta_total > 0) ? (($actual->venta_total - $anterior->venta_total) / $anterior->venta_total) * 100 : null;
            return (object) [
                'periodo' => $actual->periodo,
                'mes' => $actual->mes,
                'venta_total' => $actual->venta_total,
                'variacion_pct' => $variacion !== null ? round($variacion, 1) : null,
            ];
        });
    }

    public function ventasPorFormaPago(string $desde, string $hasta): Collection
    {
        return DB::table(DB::raw('(' . $this->unionQuery($desde, $hasta)->toSql() . ') as sub'))
            ->mergeBindings($this->unionQuery($desde, $hasta))
            ->selectRaw('forma_pago, SUM(venta) as venta_total, COUNT(DISTINCT n_compro) as comprobantes')
            ->groupBy('forma_pago')->orderByDesc('venta_total')->get();
    }
}
