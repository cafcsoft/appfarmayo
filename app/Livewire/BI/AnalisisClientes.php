<?php

namespace App\Livewire\BI;

use App\Services\BI\ClientesService;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

/**
 * Componente de análisis de clientes y fidelidad.
 * Tabs: Ranking / Segmentación / Fidelidad / Oportunidades.
 */
#[Layout('layouts.app', ['title' => 'Análisis de Clientes'])]
class AnalisisClientes extends Component
{
    use WithPagination;

    public string $tab      = 'ranking';
    public string $search   = '';
    public string $filtroSegmento = 'todos';
    public string $ordenPor       = 'venta_total';
    public string $ordenDir       = 'desc';
    public int    $limite         = 30;

    public string $fechaDesde = '';
    public string $fechaHasta = '';

    public string $chartSegmentosJson    = '{}';
    public array  $kpisClientes          = [];
    public array  $distribucionSegmentos = [];

    public function mount(ClientesService $clientes): void
    {
        $this->fechaDesde = now()->subYears(5)->startOfYear()->toDateString();
        $this->fechaHasta = now()->toDateString();
        
        $this->actualizarKpis($clientes);
    }

    public function updatedFechaDesde(): void
    {
        $this->resetPage();
    }

    public function updatedFechaHasta(): void
    {
        $this->resetPage();
    }

    public function actualizarKpis(ClientesService $clientes): void
    {
        $this->kpisClientes          = $clientes->kpisClientes($this->fechaDesde, $this->fechaHasta);
        $this->distribucionSegmentos = $clientes->distribucionSegmentos($this->fechaDesde, $this->fechaHasta)->toArray();

        $this->chartSegmentosJson = json_encode([
            'labels' => array_column($this->distribucionSegmentos, 'segmento'),
            'datos'  => array_column($this->distribucionSegmentos, 'cantidad'),
            'ventas' => array_column($this->distribucionSegmentos, 'venta'),
        ]);
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
        $this->resetPage();
    }

    public function ordenar(string $campo): void
    {
        $this->ordenDir = $this->ordenPor === $campo
            ? ($this->ordenDir === 'asc' ? 'desc' : 'asc')
            : 'desc';
        $this->ordenPor = $campo;
    }

    public function render(ClientesService $clientes)
    {
        // Forzar actualización de KPIs si cambian fechas (opcional, o llamar en hooks)
        $this->actualizarKpis($clientes);

        $todos = collect($clientes->resumenPorCliente($this->fechaDesde, $this->fechaHasta));

        if ($this->filtroSegmento !== 'todos') {
            $todos = $todos->filter(fn ($c) => $c->segmento === $this->filtroSegmento);
        }

        if ($this->search) {
            $b = mb_strtolower($this->search);
            $todos = $todos->filter(fn ($c) => str_contains(mb_strtolower($c->nombre), $b));
        }

        $todos = $this->ordenDir === 'desc'
            ? $todos->sortByDesc($this->ordenPor)
            : $todos->sortBy($this->ordenPor);

        $avgTicket = $todos->avg('ticket_promedio');
        $avgVenta  = $todos->avg('venta_total');

        // Paginación real usando la colección
        $perPage = 15;
        $page = $this->getPage();
        $clientesFiltrados = new \Illuminate\Pagination\LengthAwarePaginator(
            $todos->forPage($page, $perPage),
            $todos->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('livewire.bi.analisis-clientes', [
            'clientesFiltrados' => $clientesFiltrados,
            'reactivados' => $clientes->reactivados($this->fechaDesde, $this->fechaHasta),
            'perdidos'    => $clientes->queDejaronDeComprar(120, $this->fechaDesde, $this->fechaHasta),
            'avgTicket'   => $avgTicket,
            'avgVenta'    => $avgVenta,
            'clientes'    => $todos
        ]);
    }
}
