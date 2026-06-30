<?php

namespace App\Services\BI;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * AnomaliaService — Detección de anomalías y oportunidades comerciales.
 *
 * Detecta inconsistencias en precios, costos y descuentos que pueden
 * indicar errores de carga, fraudes o ineficiencias comerciales.
 */
class AnomaliaService
{
    public function __construct(private VentasService $ventas) {}

    // =========================================================================
    // ANOMALÍAS DE PRECIOS
    // =========================================================================

    /**
     * Detecta productos donde el precio de venta varía más del 30%
     * entre registros del mismo período (posibles errores de carga).
     */
    public function anomaliasPrecios(int $meses = 3): Collection
    {
        $desde = now()->subMonths($meses)->toDateString();
        $hasta = now()->toDateString();

        // Facturas
        $f = DB::table('fact_lin as fl')
            ->join('fact_cab as fc', 'fc.n_compro', '=', 'fl.n_compro')
            ->where('fc.anulado', 0)->where('fl.anulado', 0)
            ->where('fl.codigo', '!=', '146211')
            ->where('fc.codi_clie', '!=', '13350')
            ->where('fc.codi_clie', '!=', '3714389-1')
            ->where('fc.codi_clie', '!=', '80051943-4')
            ->whereBetween('fc.fecha', [$desde, $hasta])
            ->where('fl.cantidad', '>', 0)
            ->selectRaw("
                fl.codigo AS cod_producto,
                MAX(fl.nombre) AS nombre,
                MIN(fl.precio) AS precio_min,
                MAX(fl.precio) AS precio_max,
                AVG(fl.precio) AS precio_avg,
                COUNT(*) AS registros
            ")
            ->groupBy('fl.codigo')
            ->havingRaw('MAX(fl.precio) > 0 AND (MAX(fl.precio) / NULLIF(MIN(fl.precio), 0)) > 1.30');

        // Tickets
        $t = DB::table('ticket_lin as tl')
            ->join('ticket_cab as tc', 'tc.n_compro', '=', 'tl.n_compro')
            ->where('tc.anulado', 0)->where('tl.anulado', 0)
            ->where('tl.codigo', '!=', '146211')
            ->where('tc.idcliente', '!=', '13350')
            ->where('tc.idcliente', '!=', '3714389-1')
            ->where('tc.idcliente', '!=', '80051943-4')
            ->whereBetween('tc.fecha', [$desde, $hasta])
            ->where('tl.cantidad', '>', 0)
            ->selectRaw("
                tl.codigo AS cod_producto,
                MAX(tl.nombre) AS nombre,
                MIN(tl.precio) AS precio_min,
                MAX(tl.precio) AS precio_max,
                AVG(tl.precio) AS precio_avg,
                COUNT(*) AS registros
            ")
            ->groupBy('tl.codigo')
            ->havingRaw('MAX(tl.precio) > 0 AND (MAX(tl.precio) / NULLIF(MIN(tl.precio), 0)) > 1.30');

        return $f->unionAll($t)->get()
            ->map(fn ($r) => (object) array_merge((array) $r, [
                'variacion_pct' => round((($r->precio_max / max($r->precio_min, 0.01)) - 1) * 100, 1),
            ]))
            ->sortByDesc('variacion_pct')
            ->values();
    }

    /**
     * Detecta líneas con descuentos inusualmente altos (> 50% del precio).
     */
    public function anomaliasDescuentos(float $umbraDescuentoPct = 50, int $meses = 3): Collection
    {
        $desde = now()->subMonths($meses)->toDateString();
        $hasta = now()->toDateString();

        $f = DB::table('fact_lin as fl')
            ->join('fact_cab as fc', 'fc.n_compro', '=', 'fl.n_compro')
            ->where('fc.anulado', 0)->where('fl.anulado', 0)
            ->where('fl.codigo', '!=', '146211')
            ->where('fc.codi_clie', '!=', '13350')
            ->where('fc.codi_clie', '!=', '3714389-1')
            ->where('fc.codi_clie', '!=', '80051943-4')
            ->whereBetween('fc.fecha', [$desde, $hasta])
            ->where('fl.precio', '>', 0)
            ->whereRaw('(fl.descuento / fl.precio) * 100 > ?', [$umbraDescuentoPct])
            ->selectRaw("
                fl.codigo AS cod_producto, MAX(fl.nombre) AS nombre,
                SUM(fl.descuento) AS descuento_total,
                AVG((fl.descuento / NULLIF(fl.precio, 0)) * 100) AS pct_desc_avg,
                COUNT(*) AS lineas,
                'factura' AS origen
            ")
            ->groupBy('fl.codigo');

        $t = DB::table('ticket_lin as tl')
            ->join('ticket_cab as tc', 'tc.n_compro', '=', 'tl.n_compro')
            ->where('tc.anulado', 0)->where('tl.anulado', 0)
            ->where('tl.codigo', '!=', '146211')
            ->where('tc.idcliente', '!=', '13350')
            ->where('tc.idcliente', '!=', '3714389-1')
            ->where('tc.idcliente', '!=', '80051943-4')
            ->whereBetween('tc.fecha', [$desde, $hasta])
            ->where('tl.precio', '>', 0)
            ->whereRaw('(tl.descuento / tl.precio) * 100 > ?', [$umbraDescuentoPct])
            ->selectRaw("
                tl.codigo AS cod_producto, MAX(tl.nombre) AS nombre,
                SUM(tl.descuento) AS descuento_total,
                AVG((tl.descuento / NULLIF(tl.precio, 0)) * 100) AS pct_desc_avg,
                COUNT(*) AS lineas,
                'ticket' AS origen
            ")
            ->groupBy('tl.codigo');

        return $f->unionAll($t)->get()
            ->sortByDesc('pct_desc_avg')
            ->values();
    }

    /**
     * Detecta productos donde el costo supera el precio de venta
     * (margen negativo por problema de costos, no por precio).
     */
    public function anomaliasCostos(int $meses = 3): Collection
    {
        $desde = now()->subMonths($meses)->toDateString();
        $hasta = now()->toDateString();

        $f = DB::table('fact_lin as fl')
            ->join('fact_cab as fc', 'fc.n_compro', '=', 'fl.n_compro')
            ->where('fc.anulado', 0)->where('fl.anulado', 0)
            ->where('fl.codigo', '!=', '146211')
            ->where('fc.codi_clie', '!=', '13350')
            ->where('fc.codi_clie', '!=', '3714389-1')
            ->where('fc.codi_clie', '!=', '80051943-4')
            ->whereBetween('fc.fecha', [$desde, $hasta])
            ->where('fl.costo1', '>', 0)->where('fl.precio', '>', 0)
            ->whereRaw('fl.costo1 > fl.precio')
            ->selectRaw("
                fl.codigo AS cod_producto, MAX(fl.nombre) AS nombre,
                AVG(fl.precio) AS precio_avg,
                AVG(fl.costo1) AS costo_avg,
                SUM(CAST(fl.total AS SIGNED) - CAST((fl.costo1 * fl.cantidad) AS SIGNED)) AS margen_total,
                COUNT(*) AS lineas
            ")
            ->groupBy('fl.codigo');

        $t = DB::table('ticket_lin as tl')
            ->join('ticket_cab as tc', 'tc.n_compro', '=', 'tl.n_compro')
            ->where('tc.anulado', 0)->where('tl.anulado', 0)
            ->where('tl.codigo', '!=', '146211')
            ->where('tc.idcliente', '!=', '13350')
            ->where('tc.idcliente', '!=', '3714389-1')
            ->where('tc.idcliente', '!=', '80051943-4')
            ->whereBetween('tc.fecha', [$desde, $hasta])
            ->where('tl.costo1', '>', 0)->where('tl.precio', '>', 0)
            ->whereRaw('tl.costo1 > tl.precio')
            ->selectRaw("
                tl.codigo AS cod_producto, MAX(tl.nombre) AS nombre,
                AVG(tl.precio) AS precio_avg,
                AVG(tl.costo1) AS costo_avg,
                SUM(CAST(tl.total AS SIGNED) - CAST((tl.costo1 * tl.cantidad) AS SIGNED)) AS margen_total,
                COUNT(*) AS lineas
            ")
            ->groupBy('tl.codigo');

        return $f->unionAll($t)->get()
            ->sortBy('margen_total')
            ->values();
    }

    // =========================================================================
    // CONCENTRACIÓN Y DEPENDENCIA
    // =========================================================================

    /**
     * Índice de concentración: qué porcentaje de la venta
     * depende del top N de productos.
     *
     * Un HHI alto indica dependencia excesiva de pocos productos.
     */
    public function concentracionProductos(int $meses = 12): array
    {
        $desde = now()->subMonths($meses)->toDateString();

        $datos = DB::table(DB::raw('(' . $this->ventas->unionQuery($desde)->toSql() . ') as v'))
            ->mergeBindings($this->ventas->unionQuery($desde))
            ->selectRaw('cod_producto, SUM(venta) AS venta_total')
            ->groupBy('cod_producto')
            ->orderByDesc('venta_total')
            ->get();

        $ventaTotal = $datos->sum('venta_total');

        // Índice HHI: suma de cuadrados de cuotas de mercado
        $hhi = $datos->reduce(fn ($carry, $p) => $carry +
            pow(($p->venta_total / max($ventaTotal, 1)) * 100, 2), 0);

        $top5Pct  = $ventaTotal > 0
            ? ($datos->take(5)->sum('venta_total') / $ventaTotal) * 100 : 0;
        $top10Pct = $ventaTotal > 0
            ? ($datos->take(10)->sum('venta_total') / $ventaTotal) * 100 : 0;

        return [
            'total_productos' => $datos->count(),
            'hhi'             => round($hhi, 2),
            'nivel_hhi'       => $hhi < 1500 ? 'Baja concentración' :
                ($hhi < 2500 ? 'Concentración moderada' : 'Alta concentración'),
            'top5_pct'        => round($top5Pct, 1),
            'top10_pct'       => round($top10Pct, 1),
        ];
    }

    // =========================================================================
    // RESUMEN EJECUTIVO DE ALERTAS
    // =========================================================================

    /**
     * Resumen ejecutivo de todas las alertas activas.
     */
    public function resumenAlertas(): array
    {
        $anomaliasPrecios    = $this->anomaliasPrecios(3);
        $anomaliasDescuentos = $this->anomaliasDescuentos(50, 3);
        $anomaliasCostos     = $this->anomaliasCostos(3);
        $concentracion       = $this->concentracionProductos(12);

        $alertas = [];

        if ($anomaliasPrecios->count() > 0) {
            $alertas[] = [
                'tipo'        => 'warning',
                'icono'       => 'exclamation-triangle',
                'titulo'      => 'Variaciones de precio inusuales',
                'descripcion' => "Se detectaron {$anomaliasPrecios->count()} productos con variaciones de precio > 30% en los últimos 3 meses.",
                'accion'      => 'Ver anomalías de precios',
            ];
        }

        if ($anomaliasDescuentos->count() > 0) {
            $alertas[] = [
                'tipo'        => 'warning',
                'icono'       => 'tag',
                'titulo'      => 'Descuentos excesivos detectados',
                'descripcion' => "Hay {$anomaliasDescuentos->count()} productos con descuentos promedio superiores al 50%.",
                'accion'      => 'Ver anomalías de descuentos',
            ];
        }

        if ($anomaliasCostos->count() > 0) {
            $alertas[] = [
                'tipo'        => 'danger',
                'icono'       => 'alert-circle',
                'titulo'      => 'Costo mayor que precio de venta',
                'descripcion' => "¡Crítico! {$anomaliasCostos->count()} productos se venden por debajo del costo.",
                'accion'      => 'Ver anomalías de costos',
            ];
        }

        if ($concentracion['hhi'] > 2500) {
            $alertas[] = [
                'tipo'        => 'info',
                'icono'       => 'pie-chart',
                'titulo'      => 'Alta concentración de ventas',
                'descripcion' => "El {$concentracion['top10_pct']}% de las ventas depende solo de los 10 principales productos (HHI: {$concentracion['hhi']}).",
                'accion'      => 'Ver análisis de concentración',
            ];
        }

        return $alertas;
    }
}
