<?php

namespace App\Livewire;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ReporteFacturas extends Component
{
    public $fecha_ini;

    public $fecha_fin;

    public string $search = '';

    public int $perPage = 10;

    public int $currentPage = 1;

    public function mount()
    {
        $this->fecha_ini = Carbon::today()->format('Y-m-d');
        $this->fecha_fin = Carbon::today()->format('Y-m-d');
    }

    // Reset page on filter change
    public function updatedSearch()
    {
        $this->currentPage = 1;
    }

    public function updatedPerPage()
    {
        $this->currentPage = 1;
    }

    public function updatedFechaIni()
    {
        $this->currentPage = 1;
    }

    public function updatedFechaFin()
    {
        $this->currentPage = 1;
    }

    public function previousPage()
    {
        if ($this->currentPage > 1) {
            $this->currentPage--;
        }
    }

    public function nextPage()
    {
        $this->currentPage++;
    }

    public function goToPage(int $page)
    {
        $this->currentPage = $page;
    }

    private function buildQuery()
    {
        // CONTADO: ticket_cab joined with farmacia.client via IDCLIENTE
        $query1 = DB::table('ticket_cab')
            ->join('ticket_lin', 'ticket_cab.n_compro', '=', 'ticket_lin.n_compro')
            ->leftJoin('farmacia.client', 'farmacia.client.IDCLIENTE', '=', 'ticket_cab.idcliente')
            ->whereDate('ticket_cab.fecha', '>=', $this->fecha_ini)
            ->whereDate('ticket_cab.fecha', '<=', $this->fecha_fin)
            ->groupBy(
                'ticket_cab.n_compro',
                'ticket_cab.n_ticket',
                'ticket_cab.codi_vend',
                'ticket_cab.fecha',
                'ticket_cab.idcliente',
                'ticket_cab.nombclie',
                'ticket_cab.formapago',
                'ticket_cab.numcdc',
                'farmacia.client.email'
            )
            ->select(
                'ticket_cab.n_compro',
                'ticket_cab.n_ticket as n_factura',
                'ticket_cab.codi_vend',
                'ticket_cab.fecha',
                DB::raw('"CONTADO" AS tipofact'),
                'ticket_cab.idcliente AS ruccedula',
                'ticket_cab.nombclie',
                DB::raw('SUM(ticket_lin.total) AS total'),
                DB::raw("IF(ticket_cab.formapago = 'CONTADO', 'EFECTIVO', ticket_cab.formapago) AS formapago"),
                'ticket_cab.numcdc as num_cdc',
                DB::raw('COALESCE(farmacia.client.email, "") AS email')
            );

        // CREDITO: fact_cab joined via farmacia.clientes
        $query2 = DB::table('fact_cab')
            ->join('fact_lin', 'fact_cab.n_compro', '=', 'fact_lin.n_compro')
            ->join('farmacia.clientes', 'farmacia.clientes.codi_clie', '=', 'fact_cab.codi_clie')
            ->whereDate('fact_cab.fecha', '>=', $this->fecha_ini)
            ->whereDate('fact_cab.fecha', '<=', $this->fecha_fin)
            ->groupBy(
                'fact_cab.n_compro',
                'fact_cab.n_factura',
                'fact_cab.codi_vend',
                'fact_cab.fecha',
                'farmacia.clientes.ruc',
                'farmacia.clientes.nomb_clie',
                'farmacia.clientes.apel_clie',
                'fact_cab.numcdc',
                'farmacia.clientes.email'
            )
            ->select(
                'fact_cab.n_compro',
                'fact_cab.n_factura',
                'fact_cab.codi_vend',
                'fact_cab.fecha',
                DB::raw('"CREDITO" AS tipofact'),
                'farmacia.clientes.ruc AS ruccedula',
                DB::raw("CONCAT(TRIM(farmacia.clientes.nomb_clie), ' ', TRIM(farmacia.clientes.apel_clie)) AS nombclie"),
                DB::raw('SUM(fact_lin.total) AS total'),
                DB::raw("'CREDITO' AS formapago"),
                'fact_cab.numcdc as num_cdc',
                DB::raw('COALESCE(farmacia.clientes.email, "") AS email')
            );

        // NOTA CREDITO: notacred_cab joined with farmacia.client via IDCLIENTE
        $query3 = DB::table('notacred_cab')
            ->join('notacred_lin', 'notacred_cab.n_compro', '=', 'notacred_lin.n_compro')
            ->leftJoin('farmacia.client', 'farmacia.client.IDCLIENTE', '=', 'notacred_cab.idcliente')
            ->whereDate('notacred_cab.fecha', '>=', $this->fecha_ini)
            ->whereDate('notacred_cab.fecha', '<=', $this->fecha_fin)
            ->groupBy(
                'notacred_cab.n_compro',
                'notacred_cab.n_nota',
                'notacred_cab.codi_vend',
                'notacred_cab.fecha',
                'notacred_cab.idcliente',
                'notacred_cab.gran_total',
                'notacred_cab.formapago',
                'notacred_cab.numcdc',
                'farmacia.client.NOMBRE',
                'farmacia.client.email'
            )
            ->select(
                'notacred_cab.n_compro',
                'notacred_cab.n_nota AS n_factura',
                'notacred_cab.codi_vend',
                'notacred_cab.fecha',
                DB::raw("'NOTACRED' AS tipofact"),
                'notacred_cab.idcliente AS ruccedula',
                DB::raw('COALESCE(farmacia.client.NOMBRE, "") AS nombclie'),
                'notacred_cab.gran_total as total',
                'notacred_cab.formapago',
                'notacred_cab.numcdc as num_cdc',
                DB::raw('COALESCE(farmacia.client.email, "") AS email')
            );

        return $query1->union($query2)->union($query3)->orderBy('fecha', 'desc')->get();
    }

    public function render()
    {
        $facturas = [];
        $paginatedFacturas = [];
        $totalPages = 1;

        if ($this->fecha_ini && $this->fecha_fin) {
            $all = $this->buildQuery();

            // Client-side search filter
            if ($this->search) {
                $search = strtolower($this->search);
                $all = $all->filter(function ($row) use ($search) {
                    return str_contains(strtolower($row->n_factura ?? ''), $search)
                        || str_contains(strtolower($row->nombclie ?? ''), $search)
                        || str_contains(strtolower($row->ruccedula ?? ''), $search)
                        || str_contains(strtolower($row->tipofact ?? ''), $search)
                        || str_contains(strtolower($row->num_cdc ?? ''), $search)
                        || str_contains(strtolower($row->formapago ?? ''), $search);
                })->values();
            }

            $facturas = $all;
            $totalPages = max(1, (int) ceil($all->count() / $this->perPage));
            $this->currentPage = min($this->currentPage, $totalPages);
            $paginatedFacturas = $all->slice(($this->currentPage - 1) * $this->perPage, $this->perPage)->values();
        }

        return view('livewire.reporte-facturas', [
            'facturas' => $facturas,
            'paginatedFacturas' => $paginatedFacturas,
            'total_general' => collect($facturas)->sum('total'),
            'totalPages' => $totalPages,
        ])->layout('layouts.app');
    }
}
