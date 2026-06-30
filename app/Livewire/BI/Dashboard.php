<?php

namespace App\Livewire\BI;

use App\Services\BI\VentasService;
use App\Services\BI\ProductosService;
use App\Services\BI\ClientesService;
use App\Services\BI\AnomaliaService;
use App\Services\BI\ForecastingService;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Cache;

/**
 * Dashboard ejecutivo del módulo BI.
 *
 * Muestra KPIs principales, gráficos de tendencia y alertas activas.
 * Los datos se cargan con lazy loading para no bloquear el render inicial.
 */
#[Layout('layouts.app', ['title' => 'Dashboard BI'])]
class Dashboard extends Component
{
    // -----------------------------------------------------------------------
    // Estado del componente
    // -----------------------------------------------------------------------

    /** Número de días para el gráfico de ventas diarias. */
    public int $diasGrafico = 30;

    /** Número de meses para el gráfico de tendencia mensual. */
    public int $mesesGrafico = 12;

    /** KPIs del mes en curso. */
    public array $kpis = [];

    /** KPIs de clientes. */
    public array $kpisClientes = [];

    /** Datos para gráfico de ventas diarias (JSON para Chart.js). */
    public string $chartDiarioJson = '{}';

    /** Datos para gráfico de tendencia mensual (JSON para Chart.js). */
    public string $chartMensualJson = '{}';

    /** Datos para gráfico por origen factura/ticket (JSON). */
    public string $chartOrigenJson = '{}';

    /** Top 5 productos del mes. */
    public array $topProductos = [];

    /** Top 5 clientes del mes. */
    public array $topClientes = [];

    /** Alertas activas del sistema. */
    public array $alertas = [];

    /** Año actual para comparación. */
    public int $anioActual;

    /** Año anterior para comparación. */
    public int $anioAnterior;

    /** Datos de comparación año vs año (JSON para Chart.js). */
    public string $chartComparacionJson = '{}';

    // -----------------------------------------------------------------------
    // Lifecycle
    // -----------------------------------------------------------------------

    public function mount(
        VentasService    $ventas,
        ProductosService $productos,
        ClientesService  $clientes,
        AnomaliaService  $anomalias,
    ): void {
        $this->anioActual   = now()->year;
        $this->anioAnterior = now()->year - 1;

        // KPIs principales
        $this->kpis        = $ventas->kpisMesActual();
        $this->kpisClientes = $clientes->kpisClientes();

        // Alertas
        $this->alertas = $anomalias->resumenAlertas();

        // Top productos del mes
        $this->topProductos = $productos
            ->topPorFacturacion(5, now()->startOfMonth()->toDateString(), now()->toDateString())
            ->toArray();

        // Top clientes del mes
        $this->topClientes = $clientes->rankingPorVenta(5)->toArray();

        // Cargar gráficos
        $this->_cargarCharts($ventas);
    }

    // -----------------------------------------------------------------------
    // Acciones reactivas
    // -----------------------------------------------------------------------

    /** Actualiza el gráfico diario al cambiar el período. */
    public function updatedDiasGrafico(): void
    {
        $ventas = app(VentasService::class);
        $this->_cargarChartDiario($ventas);
    }

    /** Actualiza el gráfico mensual al cambiar el período. */
    public function updatedMesesGrafico(): void
    {
        $ventas = app(VentasService::class);
        $this->_cargarChartMensual($ventas);
    }

    /** Limpia todos los caches del módulo BI y recarga los datos. */
    public function refrescarDatos(): void
    {
        Cache::forget('bi_kpis_mes_actual');
        Cache::forget('bi_kpis_clientes');
        Cache::forget('bi_resumen_clientes_v2');

        foreach ([30, 60, 90] as $d) {
            Cache::forget("bi_ventas_dia_{$d}");
        }

        foreach ([12, 24, 36] as $m) {
            Cache::forget("bi_ventas_mes_{$m}");
        }

        $ventas = app(VentasService::class);
        $this->kpis = $ventas->kpisMesActual();
        $this->_cargarCharts($ventas);

        $this->dispatch('datos-actualizados');
    }

    // -----------------------------------------------------------------------
    // Render
    // -----------------------------------------------------------------------

    public function render()
    {
        return view('livewire.bi.dashboard');
    }

    // -----------------------------------------------------------------------
    // Helpers privados
    // -----------------------------------------------------------------------

    private function _cargarCharts(VentasService $ventas): void
    {
        $this->_cargarChartDiario($ventas);
        $this->_cargarChartMensual($ventas);
        $this->_cargarChartOrigen($ventas);
        $this->_cargarChartComparacion($ventas);
    }

    private function _cargarChartDiario(VentasService $ventas): void
    {
        $datos = $ventas->ventasPorDia($this->diasGrafico);

        $this->chartDiarioJson = json_encode([
            'labels'  => $datos->pluck('fecha')->map(
                fn ($f) => \Carbon\Carbon::parse($f)->format('d/m')
            )->toArray(),
            'ventas'  => $datos->pluck('venta_total')->map(fn ($v) => round($v, 0))->toArray(),
            'margen'  => $datos->pluck('margen_total')->map(fn ($v) => round($v, 0))->toArray(),
        ]);
    }

    private function _cargarChartMensual(VentasService $ventas): void
    {
        $datos = $ventas->ventasPorMes($this->mesesGrafico);

        $this->chartMensualJson = json_encode([
            'labels'       => $datos->pluck('periodo')->toArray(),
            'ventas'       => $datos->pluck('venta_total')->map(fn ($v) => round($v, 0))->toArray(),
            'margen'       => $datos->pluck('margen_total')->map(fn ($v) => round($v, 0))->toArray(),
            'ticket_prom'  => $datos->pluck('ticket_promedio')->map(fn ($v) => round($v, 0))->toArray(),
        ]);
    }

    private function _cargarChartOrigen(VentasService $ventas): void
    {
        $datos = $ventas->ventasPorOrigen(
            now()->subMonths(12)->toDateString(),
            now()->toDateString()
        );

        $this->chartOrigenJson = json_encode([
            'labels' => $datos->pluck('origen')->toArray(),
            'ventas' => $datos->pluck('venta_total')->map(fn ($v) => round($v, 0))->toArray(),
        ]);
    }

    private function _cargarChartComparacion(VentasService $ventas): void
    {
        $datos = $ventas->comparacionAnioVsAnio($this->anioAnterior, $this->anioActual);

        $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

        $anioAct = $datos->where('anio', $this->anioActual)->sortBy('mes');
        $anioAnt = $datos->where('anio', $this->anioAnterior)->sortBy('mes');

        $this->chartComparacionJson = json_encode([
            'labels'    => $meses,
            'actual'    => array_values($anioAct->pluck('venta_total', 'mes')->toArray()),
            'anterior'  => array_values($anioAnt->pluck('venta_total', 'mes')->toArray()),
        ]);
    }
}
