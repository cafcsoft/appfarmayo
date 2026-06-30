<?php

namespace App\Livewire\BI;

use App\Services\BI\ProductosService;
use App\Services\BI\VentasService;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

/**
 * Componente de análisis de productos.
 *
 * Tabs: Top Ventas / Ranking ABC / Márgenes / Tendencias / Oportunidades.
 */
#[Layout('layouts.app', ['title' => 'Análisis de Productos'])]
class AnalisisProductos extends Component
{
    use WithPagination;

    // -----------------------------------------------------------------------
    // Filtros
    // -----------------------------------------------------------------------
    public string $tabActiva    = 'top-ventas';
    public string $fechaDesde   = '';
    public string $fechaHasta   = '';
    public int    $limite       = 20;
    public int    $meses        = 6;

    // -----------------------------------------------------------------------
    // Datos de gráficos (JSON para Chart.js)
    // -----------------------------------------------------------------------
    public string $chartAbcJson     = '{}';
    public string $chartParetoJson  = '{}';
    public string $chartTopJson     = '{}';

    // -----------------------------------------------------------------------
    // Lifecycle
    // -----------------------------------------------------------------------
    public function mount(): void
    {
        $this->fechaDesde = now()->subMonths(12)->toDateString();
        $this->fechaHasta = now()->toDateString();
    }

    public function updatedFechaDesde(): void
    {
        $this->resetPage();
    }

    public function updatedFechaHasta(): void
    {
        $this->resetPage();
    }

    // -----------------------------------------------------------------------
    // Acciones
    // -----------------------------------------------------------------------
    public function setTab(string $tab): void
    {
        $this->tabActiva = $tab;
        $this->resetPage();
    }

    public function aplicarFiltros(): void
    {
        $this->resetPage();
    }

    // -----------------------------------------------------------------------
    // Computed properties (se recalculan en cada render)
    // -----------------------------------------------------------------------
    public function render(ProductosService $productos, VentasService $ventas)
    {
        $data = match ($this->tabActiva) {
            'top-ventas'   => $this->_dataTop($productos),
            'ranking-abc'  => $this->_dataABC($productos),
            'margenes'     => $this->_dataMargenes($productos),
            'tendencias'   => $this->_dataTendencias($productos),
            'oportunidades'=> $this->_dataOportunidades($productos),
            default        => $this->_dataTop($productos),
        };

        // Paginación para la tabla principal según el tab
        $items = match ($this->tabActiva) {
            'top-ventas'   => $data['topFacturacion'],
            'ranking-abc'  => $data['abc'],
            'margenes'     => $data['margenNegativo'],
            'tendencias'   => $data['enCrecimiento'],
            'oportunidades'=> $data['altaVentaBajoMargen'],
            default        => collect(),
        };

        $perPage = 15;
        $page = $this->getPage();
        $paginatedItems = new \Illuminate\Pagination\LengthAwarePaginator(
            $items->forPage($page, $perPage),
            $items->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('livewire.bi.analisis-productos', array_merge($data, [
            'paginatedItems' => $paginatedItems
        ]));
    }

    public function exportarReporte(): void
    {
        // Placeholder
    }

    // -----------------------------------------------------------------------
    // Helpers de datos por tab
    // -----------------------------------------------------------------------
    private function _dataTop(ProductosService $p): array
    {
        $topFacturacion = $p->topPorFacturacion(100, $this->fechaDesde, $this->fechaHasta);
        $topCantidad    = $p->topPorCantidad(100, $this->fechaDesde, $this->fechaHasta);
        $topMargen      = $p->topPorMargen(100, $this->fechaDesde, $this->fechaHasta);

        // Chart: top 10 por facturación
        $top10 = $topFacturacion->take(10);
        $this->chartTopJson = json_encode([
            'labels' => $top10->pluck('nombre')->map(fn ($n) => mb_substr($n, 0, 25))->toArray(),
            'ventas' => $top10->pluck('venta_total')->map(fn ($v) => round($v, 0))->toArray(),
            'margen' => $top10->pluck('margen_total')->map(fn ($v) => round($v, 0))->toArray(),
        ]);

        return compact('topFacturacion', 'topCantidad', 'topMargen');
    }

    private function _dataABC(ProductosService $p): array
    {
        $abc = $p->rankingABC($this->fechaDesde, $this->fechaHasta);

        // Chart de Pareto (curva ABC)
        $muestra = $abc->take(50);
        $this->chartParetoJson = json_encode([
            'labels'      => $muestra->pluck('posicion')->toArray(),
            'pct_acum'    => $muestra->pluck('pct_acumulado')->toArray(),
            'clases'      => $muestra->pluck('clase')->toArray(),
        ]);

        // Distribución por clase
        $distribucion = $abc->groupBy('clase')->map(fn ($g, $k) => [
            'clase'    => $k,
            'cantidad' => $g->count(),
            'venta'    => round($g->sum('venta_total'), 2),
        ])->values();

        $this->chartAbcJson = json_encode([
            'labels' => $distribucion->pluck('clase')->toArray(),
            'ventas' => $distribucion->pluck('venta')->toArray(),
        ]);

        return compact('abc', 'distribucion');
    }

    private function _dataMargenes(ProductosService $p): array
    {
        $margenNegativo = $p->conMargenNegativo($this->fechaDesde, $this->fechaHasta);
        $topMargen      = $p->topPorMargen(100, $this->fechaDesde, $this->fechaHasta);

        return compact('margenNegativo', 'topMargen');
    }

    private function _dataTendencias(ProductosService $p): array
    {
        $enCrecimiento      = $p->enCrecimiento($this->meses, 50, $this->fechaDesde, $this->fechaHasta);
        $enCaida            = $p->enCaida($this->meses, 50, $this->fechaDesde, $this->fechaHasta);
        $discontinuados     = $p->posiblesDiscontinuados($this->meses, $this->fechaDesde, $this->fechaHasta);

        return compact('enCrecimiento', 'enCaida', 'discontinuados');
    }

    private function _dataOportunidades(ProductosService $p): array
    {
        $altaVentaBajoMargen   = $p->altaVentaBajoMargen(30, 15, $this->fechaDesde, $this->fechaHasta);
        $bajoVolumenAltoMargen = $p->bajoVolumenAltoMargen(30, 40, $this->fechaDesde, $this->fechaHasta);

        return compact('altaVentaBajoMargen', 'bajoVolumenAltoMargen');
    }
}
