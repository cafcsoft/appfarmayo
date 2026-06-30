<?php

namespace App\Livewire\BI;

use App\Services\BI\VentasService;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

/**
 * Componente de análisis de ventas.
 *
 * Filtros dinámicos por fecha, vendedor, turno, caja y origen.
 * Gráficos de evolución, comparación año vs año y estacionalidad.
 */
#[Layout('layouts.app', ['title' => 'Análisis de Ventas'])]
class AnalisisVentas extends Component
{
    use WithPagination;

    // -----------------------------------------------------------------------
    // Filtros
    // -----------------------------------------------------------------------
    public string $fechaDesde = '';
    public string $fechaHasta = '';
    public string $origen      = 'todos';
    public string $turno       = '';
    public string $tab         = 'evolucion';

    // -----------------------------------------------------------------------
    // Datos JSON para Chart.js
    // -----------------------------------------------------------------------
    public string $chartEvolucionJson       = '{}';
    public string $chartVendedoresJson      = '{}';
    public string $chartEstacionalidadJson  = '{}';
    public string $chartComparacionJson     = '{}';
    public string $chartFormasPagoJson      = '{}';

    public int $anioComp1;
    public int $anioComp2;

    // -----------------------------------------------------------------------
    // Lifecycle
    // -----------------------------------------------------------------------
    public function mount(): void
    {
        $this->fechaDesde = now()->subMonths(12)->toDateString();
        $this->fechaHasta = now()->toDateString();
        $this->anioComp1 = now()->year - 1;
        $this->anioComp2 = now()->year;
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
        $this->tab = $tab;
        $this->resetPage();
    }

    public function aplicarFiltros(): void
    {
        $this->resetPage();
    }

    // -----------------------------------------------------------------------
    // Render
    // -----------------------------------------------------------------------
    public function render(VentasService $ventas)
    {
        // Gráfico de evolución mensual
        $diffMonths = max(1, (int) round(\Carbon\Carbon::parse($this->fechaDesde)->diffInMonths(\Carbon\Carbon::parse($this->fechaHasta))));
        $evolucion = collect($ventas->ventasPorMes($diffMonths + 1));
        
        $this->chartEvolucionJson = json_encode([
            'labels'  => $evolucion->pluck('periodo')->toArray(),
            'ventas'  => $evolucion->pluck('venta_total')->map(fn ($v) => round($v, 0))->toArray(),
            'margen'  => $evolucion->pluck('margen_total')->map(fn ($v) => round($v, 0))->toArray(),
            'tickets' => $evolucion->pluck('ticket_promedio')->map(fn ($v) => round($v, 0))->toArray(),
        ]);

        // Vendedores
        $vendedores = collect($ventas->ventasPorVendedor($this->fechaDesde, $this->fechaHasta));
        $this->chartVendedoresJson = json_encode([
            'labels' => $vendedores->pluck('vendedor')->toArray(),
            'ventas' => $vendedores->pluck('venta_total')->map(fn ($v) => round($v, 0))->toArray(),
        ]);

        // Estacionalidad
        $estacMes  = collect($ventas->estacionalidadMensual());
        $estacSem  = collect($ventas->estacionalidadSemanal());
        $this->chartEstacionalidadJson = json_encode([
            'meses'   => $estacMes->pluck('mes')->toArray(),
            'factores_mes' => $estacMes->pluck('factor')->toArray(),
            'dias'    => $estacSem->pluck('dia_nombre')->toArray(),
            'factores_dia' => $estacSem->pluck('factor')->toArray(),
        ]);

        // Formas de Pago
        $formasPago = collect($ventas->ventasPorFormaPago($this->fechaDesde, $this->fechaHasta));
        $this->chartFormasPagoJson = json_encode([
            'labels' => $formasPago->pluck('forma_pago')->toArray(),
            'ventas' => $formasPago->pluck('venta_total')->map(fn ($v) => round($v, 0))->toArray(),
        ]);

        // Comparación año vs año
        $comp = collect($ventas->comparacionAnioVsAnio($this->anioComp1, $this->anioComp2));
        $mesesNombres = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        $this->chartComparacionJson = json_encode([
            'labels'   => $mesesNombres,
            'anio1'    => array_values($comp->where('anio', $this->anioComp1)->sortBy('mes')->pluck('venta_total', 'mes')->toArray()),
            'anio2'    => array_values($comp->where('anio', $this->anioComp2)->sortBy('mes')->pluck('venta_total', 'mes')->toArray()),
        ]);

        // Tablas de datos (paginadas)
        $porCaja    = $ventas->ventasPorCaja($this->fechaDesde, $this->fechaHasta);
        $porTurno   = $ventas->ventasPorTurno($this->fechaDesde, $this->fechaHasta);
        $porOrigen  = $ventas->ventasPorOrigen($this->fechaDesde, $this->fechaHasta);
        $variacion  = $ventas->variacionMensual($this->anioComp2);
        
        // Paginamos el ticket promedio
        $ticketEvoRaw = $ventas->evolucionTicketPromedio(24);
        $perPage = 8;
        $page = $this->getPage();
        $ticketEvo = new \Illuminate\Pagination\LengthAwarePaginator(
            $ticketEvoRaw->forPage($page, $perPage),
            $ticketEvoRaw->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('livewire.bi.analisis-ventas', compact(
            'vendedores', 'estacMes', 'estacSem',
            'porCaja', 'porTurno', 'porOrigen', 'variacion', 'ticketEvo'
        ));
    }

    public function exportarVentas(): void
    {
        // Placeholder para evitar errores en la vista
    }
}
