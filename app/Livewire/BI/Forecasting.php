<?php

namespace App\Livewire\BI;

use App\Services\BI\ForecastingService;
use App\Services\BI\VentasService;
use Livewire\Component;
use Livewire\Attributes\Layout;

/**
 * Componente de forecasting / proyección de ventas.
 *
 * Muestra proyección mensual para 3, 6 o 12 meses con intervalos de confianza.
 * Gráfico de líneas con histórico + proyección en Chart.js.
 */
#[Layout('layouts.app', ['title' => 'Forecasting BI'])]
class Forecasting extends Component
{
    public int    $horizonteMeses = 6;
    public string $metrica        = 'venta';

    public string $chartForecastJson    = '{}';
    public string $chartOutliersJson    = '{}';
    public array  $factoresEstacionales = [];
    public array  $proyeccion           = [];
    public array  $outliers             = [];

    public function mount(): void
    {
        $this->_cargarDatos();
    }

    public function updatedHorizonteMeses(): void
    {
        $this->_cargarDatos();
    }

    public function updatedMetrica(): void
    {
        $this->_cargarDatos();
    }

    public function render()
    {
        return view('livewire.bi.forecasting');
    }

    private function _cargarDatos(): void
    {
        $forecast = app(ForecastingService::class);

        $resultado = match ($this->metrica) {
            'unidades' => $forecast->proyectarUnidades($this->horizonteMeses),
            'margen'   => $forecast->proyectarMargen($this->horizonteMeses),
            default    => $forecast->proyectarMensual($this->horizonteMeses),
        };

        $this->proyeccion           = collect($resultado['proyeccion'])->all();
        $this->factoresEstacionales = (array) ($resultado['factores_estacionales'] ?? []);

        $historico = collect($resultado['historico']);
        $proyeccion = collect($resultado['proyeccion']);

        // Combinar histórico + proyección para el gráfico
        $campo = match ($this->metrica) {
            'unidades' => 'unidades',
            'margen'   => 'margen_total',
            default    => 'venta_total',
        };

        $labelsHist = $historico->pluck('periodo')->toArray();
        $valHist    = $historico->pluck($campo)->map(fn ($v) => round($v, 0))->toArray();
        $labelsProy = $proyeccion->pluck('periodo')->toArray();
        $valProy    = $proyeccion->pluck('proyectado')->map(fn ($v) => round($v, 0))->toArray();
        $limInf     = $proyeccion->pluck('limite_inf')->map(fn ($v) => round($v, 0))->toArray();
        $limSup     = $proyeccion->pluck('limite_sup')->map(fn ($v) => round($v, 0))->toArray();

        $this->chartForecastJson = json_encode([
            'labelsHist' => $labelsHist,
            'labelsProy' => $labelsProy,
            'historico'  => $valHist,
            'proyectado' => $valProy,
            'limInf'     => $limInf,
            'limSup'     => $limSup,
        ]);

        // Outliers
        $outliers       = $forecast->detectarOutliers();
        $this->outliers = $outliers->toArray();

        $this->chartOutliersJson = json_encode([
            'labels' => $historico->pluck('periodo')->toArray(),
            'ventas' => $historico->pluck($campo)->map(fn ($v) => round($v, 0))->toArray(),
            'outlier_labels' => $outliers->pluck('periodo')->toArray(),
            'outlier_vals'   => $outliers->pluck('venta_total')->map(fn ($v) => round($v, 0))->toArray(),
        ]);
    }
}
