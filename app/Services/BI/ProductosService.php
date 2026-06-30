<?php

namespace App\Services\BI;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

/**
 * ProductosService — Análisis de rendimiento de productos.
 *
 * Rankings, márgenes, clasificación ABC, tendencias y oportunidades.
 */
class ProductosService
{
    use CacheHelper;

    public function __construct(private VentasService $ventas) {}

    // =========================================================================
    // RANKINGS
    // =========================================================================

    /** Top N productos por unidades vendidas. */
    public function topPorCantidad(int $limite = 20, string $desde = null, string $hasta = null): Collection
    {
        $desde ??= now()->subMonths(12)->toDateString();
        $hasta ??= now()->toDateString();

        return DB::table(DB::raw('(' . $this->ventas->unionQuery($desde, $hasta)->toSql() . ') as v'))
            ->mergeBindings($this->ventas->unionQuery($desde, $hasta))
            ->selectRaw('
                cod_producto, MAX(producto) AS nombre,
                SUM(cantidad) AS total_unidades,
                SUM(venta) AS venta_total,
                SUM(margen) AS margen_total,
                COUNT(DISTINCT n_compro) AS transacciones
            ')
            ->groupBy('cod_producto')
            ->orderByDesc('total_unidades')
            ->limit($limite)
            ->get();
    }

    /** Top N productos por facturación. */
    public function topPorFacturacion(int $limite = 20, string $desde = null, string $hasta = null): Collection
    {
        $desde ??= now()->subMonths(12)->toDateString();
        $hasta ??= now()->toDateString();

        return DB::table(DB::raw('(' . $this->ventas->unionQuery($desde, $hasta)->toSql() . ') as v'))
            ->mergeBindings($this->ventas->unionQuery($desde, $hasta))
            ->selectRaw('
                cod_producto, MAX(producto) AS nombre,
                SUM(venta) AS venta_total,
                SUM(cantidad) AS total_unidades,
                SUM(margen) AS margen_total,
                (SUM(margen) / NULLIF(SUM(venta), 0)) * 100 AS pct_margen,
                COUNT(DISTINCT n_compro) AS transacciones
            ')
            ->groupBy('cod_producto')
            ->orderByDesc('venta_total')
            ->limit($limite)
            ->get();
    }

    /** Top N productos por margen bruto. */
    public function topPorMargen(int $limite = 20, string $desde = null, string $hasta = null): Collection
    {
        $desde ??= now()->subMonths(12)->toDateString();
        $hasta ??= now()->toDateString();

        return DB::table(DB::raw('(' . $this->ventas->unionQuery($desde, $hasta)->toSql() . ') as v'))
            ->mergeBindings($this->ventas->unionQuery($desde, $hasta))
            ->selectRaw('
                cod_producto, MAX(producto) AS nombre,
                SUM(margen) AS margen_total,
                SUM(venta) AS venta_total,
                (SUM(margen) / NULLIF(SUM(venta), 0)) * 100 AS pct_margen
            ')
            ->groupBy('cod_producto')
            ->orderByDesc('margen_total')
            ->limit($limite)
            ->get();
    }

    /** Productos con margen negativo. */
    public function conMargenNegativo(string $desde = null, string $hasta = null): Collection
    {
        $desde ??= now()->subMonths(3)->toDateString();
        $hasta ??= now()->toDateString();

        return DB::table(DB::raw('(' . $this->ventas->unionQuery($desde, $hasta)->toSql() . ') as v'))
            ->mergeBindings($this->ventas->unionQuery($desde, $hasta))
            ->selectRaw('
                cod_producto, MAX(producto) AS nombre,
                SUM(margen) AS margen_total,
                SUM(venta) AS venta_total,
                (SUM(margen) / NULLIF(SUM(venta), 0)) * 100 AS pct_margen
            ')
            ->groupBy('cod_producto')
            ->havingRaw('SUM(margen) < 0')
            ->orderBy('margen_total')
            ->get();
    }

    // =========================================================================
    // RANKING ABC
    // =========================================================================

    /**
     * Clasificación ABC: A=80% venta, B=15%, C=5%.
     */
    public function rankingABC(string $desde = null, string $hasta = null): Collection
    {
        $desde ??= now()->subMonths(12)->toDateString();
        $hasta ??= now()->toDateString();

        return $this->cacheJson("bi_abc_{$desde}_{$hasta}", 3600 * 6, function () use ($desde, $hasta) {
            $productos = DB::table(DB::raw('(' . $this->ventas->unionQuery($desde, $hasta)->toSql() . ') as v'))
                ->mergeBindings($this->ventas->unionQuery($desde, $hasta))
                ->selectRaw('
                    cod_producto, MAX(producto) AS nombre,
                    SUM(venta) AS venta_total,
                    SUM(cantidad) AS total_unidades,
                    SUM(margen) AS margen_total
                ')
                ->groupBy('cod_producto')
                ->orderByDesc('venta_total')
                ->get();

            $ventaGlobal = $productos->sum('venta_total');
            $acumulado   = 0;
            $posicion    = 0;

            return $productos->map(function ($p) use ($ventaGlobal, &$acumulado, &$posicion) {
                $posicion++;
                $acumulado += $p->venta_total;
                $pctAcum    = $ventaGlobal > 0 ? ($acumulado / $ventaGlobal) * 100 : 0;
                $pct        = $ventaGlobal > 0 ? ($p->venta_total / $ventaGlobal) * 100 : 0;

                $clase = ($pctAcum - $pct) < 80 ? 'A' : (($pctAcum - $pct) < 95 ? 'B' : 'C');

                return (object) [
                    'posicion'       => $posicion,
                    'cod_producto'   => $p->cod_producto,
                    'nombre'         => $p->nombre,
                    'venta_total'    => round($p->venta_total, 2),
                    'total_unidades' => round($p->total_unidades, 0),
                    'margen_total'   => round($p->margen_total, 2),
                    'pct_venta'      => round($pct, 2),
                    'pct_acumulado'  => round($pctAcum, 2),
                    'clase'          => $clase,
                ];
            });
        });
    }

    // =========================================================================
    // TENDENCIAS
    // =========================================================================

    /** Productos en crecimiento (compara primera mitad vs segunda mitad del período). */
    public function enCrecimiento(int $meses = 6, int $limite = 15, string $desde = null, string $hasta = null): Collection
    {
        $inicio = $desde ?? now()->subMonths($meses)->toDateString();
        $fin    = $hasta ?? now()->toDateString();
        
        $dias   = Carbon::parse($inicio)->diffInDays(Carbon::parse($fin));
        $mitad  = (int) ($dias / 2);
        $corte  = Carbon::parse($inicio)->addDays($mitad)->toDateString();

        $p1 = $this->_ventasPorProducto($inicio, $corte);
        $p2 = $this->_ventasPorProducto($corte, $fin);

        return $p2->map(function ($prod) use ($p1) {
            $anterior    = $p1->firstWhere('cod_producto', $prod->cod_producto);
            $ventaAnt    = $anterior?->venta_total ?? 0;
            $crecimiento = $ventaAnt > 0
                ? (($prod->venta_total - $ventaAnt) / $ventaAnt) * 100 : 0;

            return (object) [
                'cod_producto' => $prod->cod_producto,
                'nombre'       => $prod->nombre,
                'venta_p1'     => round($ventaAnt, 2),
                'venta_p2'     => round($prod->venta_total, 2),
                'crecimiento'  => round($crecimiento, 1),
            ];
        })->filter(fn ($p) => $p->crecimiento > 5)->sortByDesc('crecimiento')->take($limite)->values();
    }

    /** Productos en caída. */
    public function enCaida(int $meses = 6, int $limite = 15, string $desde = null, string $hasta = null): Collection
    {
        $inicio = $desde ?? now()->subMonths($meses)->toDateString();
        $fin    = $hasta ?? now()->toDateString();
        
        $dias   = Carbon::parse($inicio)->diffInDays(Carbon::parse($fin));
        $mitad  = (int) ($dias / 2);
        $corte  = Carbon::parse($inicio)->addDays($mitad)->toDateString();

        $p1 = $this->_ventasPorProducto($inicio, $corte);
        $p2 = $this->_ventasPorProducto($corte, $fin);

        return $p1->map(function ($anterior) use ($p2) {
            $actual   = $p2->firstWhere('cod_producto', $anterior->cod_producto);
            $ventaAct = $actual?->venta_total ?? 0;
            $caida    = $anterior->venta_total > 0
                ? (($ventaAct - $anterior->venta_total) / $anterior->venta_total) * 100 : 0;

            return (object) [
                'cod_producto' => $anterior->cod_producto,
                'nombre'       => $anterior->nombre,
                'venta_p1'     => round($anterior->venta_total, 2),
                'venta_p2'     => round($ventaAct, 2),
                'caida'        => round($caida, 1),
            ];
        })->filter(fn ($p) => $p->caida < -5)->sortBy('caida')->take($limite)->values();
    }

    /** Posibles discontinuados: historial anterior pero sin ventas recientes. */
    public function posiblesDiscontinuados(int $meses = 3, string $desde = null, string $hasta = null): Collection
    {
        $corte     = $desde ?? now()->subMonths($meses)->toDateString();
        $desdeHist = now()->subYears(5)->toDateString();
        $fin       = $hasta ?? now()->toDateString();

        $historicos  = $this->_ventasPorProducto($desdeHist, $corte)->pluck('cod_producto');
        $recientes   = $this->_ventasPorProducto($corte, $fin)->pluck('cod_producto');
        $discontinuados = $historicos->diff($recientes);

        return $this->_ventasPorProducto($desdeHist, $corte)
            ->whereIn('cod_producto', $discontinuados->all())
            ->sortByDesc('venta_total')
            ->take(30)
            ->values();
    }

    // =========================================================================
    // OPORTUNIDADES COMERCIALES
    // =========================================================================

    /** Alta venta pero bajo margen: candidatos a revisión de precio/costo. */
    public function altaVentaBajoMargen(float $topPct = 30, float $maxMargenPct = 15, string $desde = null, string $hasta = null): Collection
    {
        $desde ??= now()->subMonths(12)->toDateString();
        $hasta ??= now()->toDateString();
        
        $todos = DB::table(DB::raw('(' . $this->ventas->unionQuery($desde, $hasta)->toSql() . ') as v'))
            ->mergeBindings($this->ventas->unionQuery($desde, $hasta))
            ->selectRaw('
                cod_producto, MAX(producto) AS nombre,
                SUM(venta) AS venta_total,
                SUM(margen) AS margen_total,
                (SUM(margen) / NULLIF(SUM(venta), 0)) * 100 AS pct_margen
            ')
            ->groupBy('cod_producto')
            ->orderByDesc('venta_total')
            ->get();

        $topN = (int) ceil($todos->count() * ($topPct / 100));

        return $todos->take($topN)->filter(fn ($p) => $p->pct_margen < $maxMargenPct)->values();
    }

    /** Bajo volumen pero alto margen: oportunidades de promoción activa. */
    public function bajoVolumenAltoMargen(float $bottomPct = 30, float $minMargenPct = 40, string $desde = null, string $hasta = null): Collection
    {
        $desde ??= now()->subMonths(12)->toDateString();
        $hasta ??= now()->toDateString();
        
        $todos = DB::table(DB::raw('(' . $this->ventas->unionQuery($desde, $hasta)->toSql() . ') as v'))
            ->mergeBindings($this->ventas->unionQuery($desde, $hasta))
            ->selectRaw('
                cod_producto, MAX(producto) AS nombre,
                SUM(venta) AS venta_total,
                SUM(cantidad) AS total_unidades,
                SUM(margen) AS margen_total,
                (SUM(margen) / NULLIF(SUM(venta), 0)) * 100 AS pct_margen
            ')
            ->groupBy('cod_producto')
            ->orderBy('venta_total')
            ->get();

        $bottomN = (int) ceil($todos->count() * ($bottomPct / 100));

        return $todos->take($bottomN)
            ->filter(fn ($p) => $p->pct_margen > $minMargenPct && $p->venta_total > 0)
            ->sortByDesc('pct_margen')
            ->values();
    }

    // =========================================================================
    // HELPERS INTERNOS
    // =========================================================================

    private function _ventasPorProducto(string $desde, string $hasta): Collection
    {
        return DB::table(DB::raw('(' . $this->ventas->unionQuery($desde, $hasta)->toSql() . ') as v'))
            ->mergeBindings($this->ventas->unionQuery($desde, $hasta))
            ->selectRaw('
                cod_producto, MAX(producto) AS nombre,
                SUM(venta) AS venta_total,
                SUM(cantidad) AS total_unidades,
                SUM(margen) AS margen_total
            ')
            ->groupBy('cod_producto')
            ->get();
    }
}
