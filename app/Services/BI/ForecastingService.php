<?php

namespace App\Services\BI;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * ForecastingService — Proyección de ventas futuras.
 *
 * Algoritmo: Descomposición Estacional Multiplicativa (Trend × Seasonal × Residual).
 *
 * Pasos:
 *  1. Obtener histórico mensual (últimos 5 años)
 *  2. Calcular tendencia con media móvil de 12 meses
 *  3. Calcular factores estacionales por mes (promedio de real/tendencia)
 *  4. Detectar y excluir outliers por IQR antes de proyectar
 *  5. Proyectar: tendencia extrapolada × factor estacional del mes
 *  6. Ajustar el mes actual si está incompleto
 */
class ForecastingService
{
    use CacheHelper;

    public function __construct(private VentasService $ventas) {}

    // =========================================================================
    // PROYECCIÓN MENSUAL PRINCIPAL
    // =========================================================================

    /**
     * Proyecta ventas mensuales para los próximos N meses.
     *
     * @param int $meses  3, 6 o 12 meses hacia adelante
     * @return array{historico, proyeccion, factores_estacionales}
     */
    public function proyectarMensual(int $meses = 6): array
    {
        return (array) $this->cacheJson("bi_forecast_mensual_{$meses}", 3600 * 6, function () use ($meses) {
            // 1. Histórico mensual (sin outliers)
            $historico = $this->_historicoLimpio();

            // 2. Factores estacionales (1–12)
            $factores = $this->_factoresEstacionales($historico);

            // 3. Tendencia: regresión lineal simple sobre el histórico sin estacionalidad
            $desestacionalizado = $historico->map(fn ($r) => (object) [
                'periodo'    => $r->periodo,
                'anio'       => $r->anio,
                'mes'        => $r->mes,
                'venta_dese' => $r->venta_total / max($factores[$r->mes] ?? 1, 0.01),
            ]);

            [$pendiente, $intercepto] = $this->_regresionLineal($desestacionalizado);

            // 4. Proyectar
            $n          = $historico->count();
            $proyeccion = collect();

            for ($i = 1; $i <= $meses; $i++) {
                $fecha = now()->addMonths($i)->startOfMonth();
                $x     = $n + $i;
                $tendencia = $intercepto + $pendiente * $x;
                $factor    = $factores[$fecha->month] ?? 1;
                $proyectado = max(0, $tendencia * $factor);

                $proyeccion->push((object) [
                    'periodo'      => $fecha->format('Y-m'),
                    'anio'         => $fecha->year,
                    'mes'          => $fecha->month,
                    'tendencia'    => round($tendencia, 2),
                    'factor_est'   => round($factor, 4),
                    'proyectado'   => round($proyectado, 2),
                    'limite_inf'   => round($proyectado * 0.85, 2),
                    'limite_sup'   => round($proyectado * 1.15, 2),
                ]);
            }

            return [
                'historico'            => $historico->take(-24)->values(),
                'proyeccion'           => $proyeccion,
                'factores_estacionales' => $factores,
                'tendencia_mensual'    => round($pendiente, 2),
            ];
        });
    }

    /**
     * Proyección de unidades vendidas.
     */
    public function proyectarUnidades(int $meses = 6): array
    {
        return (array) $this->cacheJson("bi_forecast_unidades_{$meses}", 3600 * 6, function () use ($meses) {
            $historico = $this->ventas->ventasPorMes(60);
            $historico = $this->_limpiarOutliersCollection($historico, 'unidades');
            $factores  = $this->_factoresEstacionalesFromField($historico, 'unidades');

            [$pendiente, $intercepto] = $this->_regresionLinealFromField(
                $historico->map(fn ($r) => (object) [
                    'venta_dese' => $r->unidades / max($factores[$r->mes] ?? 1, 0.01),
                ])
            );

            $n          = $historico->count();
            $proyeccion = collect();

            for ($i = 1; $i <= $meses; $i++) {
                $fecha     = now()->addMonths($i)->startOfMonth();
                $x         = $n + $i;
                $tendencia = $intercepto + $pendiente * $x;
                $factor    = $factores[$fecha->month] ?? 1;
                $proyectado = max(0, $tendencia * $factor);

                $proyeccion->push((object) [
                    'periodo'    => $fecha->format('Y-m'),
                    'proyectado' => round($proyectado, 0),
                    'limite_inf' => round($proyectado * 0.85, 0),
                    'limite_sup' => round($proyectado * 1.15, 0),
                ]);
            }

            return ['historico' => $historico->take(-24)->values(), 'proyeccion' => $proyeccion];
        });
    }

    /**
     * Proyección de margen bruto.
     */
    public function proyectarMargen(int $meses = 6): array
    {
        return (array) $this->cacheJson("bi_forecast_margen_{$meses}", 3600 * 6, function () use ($meses) {
            $historico = $this->ventas->ventasPorMes(60);
            $historico = $this->_limpiarOutliersCollection($historico, 'margen_total');
            $factores  = $this->_factoresEstacionalesFromField($historico, 'margen_total');

            [$pendiente, $intercepto] = $this->_regresionLinealFromField(
                $historico->map(fn ($r) => (object) [
                    'venta_dese' => $r->margen_total / max($factores[$r->mes] ?? 1, 0.01),
                ])
            );

            $n = $historico->count();
            $proyeccion = collect();

            for ($i = 1; $i <= $meses; $i++) {
                $fecha      = now()->addMonths($i)->startOfMonth();
                $factor     = $factores[$fecha->month] ?? 1;
                $proyectado = max(0, ($intercepto + $pendiente * ($n + $i)) * $factor);

                $proyeccion->push((object) [
                    'periodo'    => $fecha->format('Y-m'),
                    'proyectado' => round($proyectado, 2),
                    'limite_inf' => round($proyectado * 0.85, 2),
                    'limite_sup' => round($proyectado * 1.15, 2),
                ]);
            }

            return ['historico' => $historico->take(-24)->values(), 'proyeccion' => $proyeccion];
        });
    }

    // =========================================================================
    // DETECCIÓN DE OUTLIERS (Método IQR)
    // =========================================================================

    /**
     * Detecta meses con ventas atípicas usando el método del rango intercuartílico.
     *
     * Un mes es outlier si su venta está fuera de: Q1 - 1.5×IQR … Q3 + 1.5×IQR
     */
    public function detectarOutliers(): Collection
    {
        $historico = $this->ventas->ventasPorMes(60)->sortBy('venta_total')->values();
        $n         = $historico->count();

        if ($n < 4) {
            return collect();
        }

        $q1   = $historico[(int) floor($n * 0.25)]->venta_total;
        $q3   = $historico[(int) floor($n * 0.75)]->venta_total;
        $iqr  = $q3 - $q1;
        $limInf = $q1 - 1.5 * $iqr;
        $limSup = $q3 + 1.5 * $iqr;

        return $historico->filter(
            fn ($r) => $r->venta_total < $limInf || $r->venta_total > $limSup
        )->map(fn ($r) => (object) [
            'periodo'     => $r->periodo,
            'venta_total' => $r->venta_total,
            'tipo'        => $r->venta_total < $limInf ? 'mínimo atípico' : 'máximo atípico',
        ])->values();
    }

    // =========================================================================
    // HELPERS INTERNOS
    // =========================================================================

    /** Obtiene histórico mensual limpio (sin outliers y ajustando mes actual parcial). */
    private function _historicoLimpio(): Collection
    {
        $datos = $this->ventas->ventasPorMes(60);

        // Ajustar mes actual (parcial): extrapolación lineal por días transcurridos
        $mesActual = $datos->last();
        if ($mesActual && (int) $mesActual->anio === now()->year && (int) $mesActual->mes === now()->month) {
            $diasTranscurridos = now()->day;
            $diasMes           = now()->daysInMonth;
            if ($diasTranscurridos < $diasMes) {
                $ventaAjustada = ($mesActual->venta_total / $diasTranscurridos) * $diasMes;
                $datos         = $datos->map(function ($r) use ($mesActual, $ventaAjustada) {
                    if ($r->periodo === $mesActual->periodo) {
                        $r->venta_total    = $ventaAjustada;
                        $r->es_proyectado  = true;
                    }

                    return $r;
                });
            }
        }

        return $this->_limpiarOutliersCollection($datos, 'venta_total');
    }

    /** Elimina outliers de una colección dado un campo. */
    private function _limpiarOutliersCollection(Collection $datos, string $campo): Collection
    {
        $sorted = $datos->sortBy($campo)->values();
        $n      = $sorted->count();
        if ($n < 4) {
            return $datos;
        }

        $q1      = $sorted[(int) floor($n * 0.25)]->$campo;
        $q3      = $sorted[(int) floor($n * 0.75)]->$campo;
        $iqr     = $q3 - $q1;
        $limInf  = $q1 - 1.5 * $iqr;
        $limSup  = $q3 + 1.5 * $iqr;

        return $datos->filter(fn ($r) => $r->$campo >= $limInf && $r->$campo <= $limSup)->values();
    }

    /** Calcula factores estacionales (1–12) para el campo venta_total. */
    private function _factoresEstacionales(Collection $historico): array
    {
        return $this->_factoresEstacionalesFromField($historico, 'venta_total');
    }

    /** Calcula factores estacionales para cualquier campo numérico. */
    private function _factoresEstacionalesFromField(Collection $historico, string $campo): array
    {
        $porMes = $historico->groupBy('mes');
        $media  = $historico->avg($campo);

        $factores = [];
        for ($m = 1; $m <= 12; $m++) {
            $grupo = $porMes->get($m) ?? collect();
            $factores[$m] = $media > 0 && $grupo->count() > 0
                ? $grupo->avg($campo) / $media
                : 1.0;
        }

        return $factores;
    }

    /**
     * Regresión lineal simple sobre la columna venta_dese de una colección.
     * Retorna [pendiente, intercepto].
     */
    private function _regresionLineal(Collection $datos): array
    {
        return $this->_regresionLinealFromField($datos);
    }

    private function _regresionLinealFromField(Collection $datos): array
    {
        $n    = $datos->count();
        if ($n < 2) {
            return [0, $datos->first()?->venta_dese ?? 0];
        }

        $sumX  = 0;
        $sumY  = 0;
        $sumXY = 0;
        $sumX2 = 0;

        foreach ($datos as $i => $r) {
            $x      = $i + 1;
            $y      = $r->venta_dese;
            $sumX  += $x;
            $sumY  += $y;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        }

        $denom     = ($n * $sumX2 - $sumX * $sumX);
        $pendiente = $denom != 0 ? ($n * $sumXY - $sumX * $sumY) / $denom : 0;
        $intercepto = ($sumY - $pendiente * $sumX) / $n;

        return [$pendiente, $intercepto];
    }
}
