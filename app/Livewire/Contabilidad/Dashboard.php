<?php

namespace App\Livewire\Contabilidad;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Dashboard extends Component
{
    public float $gravadas10Actual = 0;
    public float $gravadas10Pct = 0;
    public bool $gravadas10Sube = true;

    public float $iva5Actual = 0;
    public float $iva5Pct = 0;
    public bool $iva5Sube = true;

    public float $exentasActual = 0;
    public float $exentasPct = 0;
    public bool $exentasSube = true;

    public int $comprobantesEmitidos = 0;

    public function mount()
    {
        $this->calculateKpis();
    }

    private function calculateKpis()
    {
        $excludedIds = ['13350', '3714389-1', '80051943-4'];

        // Fechas mes actual
        $inicioActual = Carbon::now()->startOfMonth()->toDateString() . ' 00:00:00';
        $finActual    = Carbon::now()->endOfMonth()->toDateString() . ' 23:59:59';
        $finActualMtd = Carbon::now()->toDateString() . ' 23:59:59';

        // Fechas mes anterior (para comparación MTD justa)
        $inicioAnterior = Carbon::now()->subMonth()->startOfMonth()->toDateString() . ' 00:00:00';
        
        $diaDeHoy = Carbon::now()->day;
        $diasMesAnterior = Carbon::now()->subMonth()->daysInMonth;
        $diaFinMesAnterior = min($diaDeHoy, $diasMesAnterior);
        $finAnteriorMtd = Carbon::now()->subMonth()->day($diaFinMesAnterior)->toDateString() . ' 23:59:59';

        // --- COMPROBANTES EMITIDOS (Mes actual completo) ---
        $ticketsEmitidos = DB::table('ticket_cab')
            ->where('anulado', 0)
            ->whereNotIn('idcliente', $excludedIds)
            ->whereBetween('fecha', [$inicioActual, $finActual])
            ->count();

        $facturasEmitidas = DB::table('fact_cab')
            ->where('anulado', 0)
            ->whereNotIn('codi_clie', $excludedIds)
            ->whereBetween('fecha', [$inicioActual, $finActual])
            ->count();

        $notasEmitidas = DB::table('notacred_cab')
            ->whereNotIn('idcliente', $excludedIds)
            ->whereBetween('fecha', [$inicioActual, $finActual])
            ->count();

        $this->comprobantesEmitidos = $ticketsEmitidos + $facturasEmitidas + $notasEmitidas;

        // --- MONTOS (MTD actual vs MTD anterior) ---
        // Actual MTD
        $actualTotals = $this->queryDetailedTotals($inicioActual, $finActualMtd, $excludedIds);
        $this->gravadas10Actual = $actualTotals['grav10'];
        $this->iva5Actual       = $actualTotals['iva5'];
        $this->exentasActual    = $actualTotals['exento'];

        // Anterior MTD
        $anteriorTotals = $this->queryDetailedTotals($inicioAnterior, $finAnteriorMtd, $excludedIds);
        $grav10Anterior = $anteriorTotals['grav10'];
        $iva5Anterior   = $anteriorTotals['iva5'];
        $exentoAnterior = $anteriorTotals['exento'];

        // Calcular porcentajes
        $this->gravadas10Pct = 0;
        $this->gravadas10Sube = true;
        if ($grav10Anterior > 0) {
            $diff = (($this->gravadas10Actual - $grav10Anterior) / $grav10Anterior) * 100;
            $this->gravadas10Pct = abs($diff);
            $this->gravadas10Sube = $diff >= 0;
        }

        $this->iva5Pct = 0;
        $this->iva5Sube = true;
        if ($iva5Anterior > 0) {
            $diff = (($this->iva5Actual - $iva5Anterior) / $iva5Anterior) * 100;
            $this->iva5Pct = abs($diff);
            $this->iva5Sube = $diff >= 0;
        }

        $this->exentasPct = 0;
        $this->exentasSube = true;
        if ($exentoAnterior > 0) {
            $diff = (($this->exentasActual - $exentoAnterior) / $exentoAnterior) * 100;
            $this->exentasPct = abs($diff);
            $this->exentasSube = $diff >= 0;
        }
    }

    private function queryDetailedTotals(string $desde, string $hasta, array $excludedIds): array
    {
        // 1. Sumatoria de Líneas de Tickets
        $ticketLines = DB::table('ticket_cab as tc')
            ->join('ticket_lin as tl', 'tc.n_compro', '=', 'tl.n_compro')
            ->where('tc.anulado', 0)
            ->where('tl.anulado', 0)
            ->whereNotIn('tc.idcliente', $excludedIds)
            ->whereBetween('tc.fecha', [$desde, $hasta])
            ->select('tl.ivaporc', DB::raw('SUM(tl.total) as total_monto'))
            ->groupBy('tl.ivaporc')
            ->get();

        // 2. Sumatoria de Líneas de Facturas
        $facturaLines = DB::table('fact_cab as fc')
            ->join('fact_lin as fl', 'fc.n_compro', '=', 'fl.n_compro')
            ->where('fc.anulado', 0)
            ->where('fl.anulado', 0)
            ->whereNotIn('fc.codi_clie', $excludedIds)
            ->whereBetween('fc.fecha', [$desde, $hasta])
            ->select('fl.ivaporc', DB::raw('SUM(fl.total) as total_monto'))
            ->groupBy('fl.ivaporc')
            ->get();

        // 3. Sumatoria de Líneas de Notas de Crédito (restan del débito)
        $notaLines = DB::table('notacred_cab as nc')
            ->join('notacred_lin as nl', 'nc.n_compro', '=', 'nl.n_compro')
            ->whereNotIn('nc.idcliente', $excludedIds)
            ->whereBetween('nc.fecha', [$desde, $hasta])
            ->select('nl.ivaporc', DB::raw('SUM(nl.total) as total_monto'))
            ->groupBy('nl.ivaporc')
            ->get();

        // Agrupación y clasificación defensiva
        $totals = [10 => 0.0, 5 => 0.0, 0 => 0.0];

        foreach ($ticketLines as $l) {
            $porc = (int) $l->ivaporc;
            if ($porc === 10) $totals[10] += (float)$l->total_monto;
            elseif ($porc === 5) $totals[5] += (float)$l->total_monto;
            else $totals[0] += (float)$l->total_monto;
        }

        foreach ($facturaLines as $l) {
            $porc = (int) $l->ivaporc;
            if ($porc === 10) $totals[10] += (float)$l->total_monto;
            elseif ($porc === 5) $totals[5] += (float)$l->total_monto;
            else $totals[0] += (float)$l->total_monto;
        }

        foreach ($notaLines as $l) {
            $porc = (int) $l->ivaporc;
            if ($porc === 10) $totals[10] -= (float)$l->total_monto;
            elseif ($porc === 5) $totals[5] -= (float)$l->total_monto;
            else $totals[0] -= (float)$l->total_monto;
        }

        return [
            'grav10' => max(0.0, $totals[10] / 1.1),  // Base imponible 10%
            'iva5'   => max(0.0, $totals[5] / 21),    // IVA 5%
            'exento' => max(0.0, $totals[0]),         // Monto exento
        ];
    }

    public function render()
    {
        return view('livewire.contabilidad.dashboard')
            ->layout('layouts.app', ['title' => __('Dashboard Contabilidad')]);
    }
}
