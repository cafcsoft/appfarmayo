<?php

namespace App\Http\Controllers\Contabilidad;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class LibroIvaPrintController extends Controller
{
    private const BATCH_SIZE = 15;

    public function __invoke(Request $request)
    {
        $fechaIni = $request->get('fecha_ini', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $fechaFin = $request->get('fecha_fin', Carbon::now()->format('Y-m-d'));

        // 1. Obtener facturas con CDC del período
        $facturas = $this->buildQuery($fechaIni, $fechaFin);

        // 2. Resolver caché y consultas API
        $cdcs        = $facturas->pluck('numcdc')->filter()->unique()->values()->toArray();
        $cachedItems = DB::table('cdc_cache')->whereIn('cdc', $cdcs)->get()->keyBy('cdc');

        $toFetch = $facturas->filter(
            fn($f) => !empty($f->numcdc) && !isset($cachedItems[$f->numcdc])
        )->values()->toArray();

        if (!empty($toFetch)) {
            $this->fetchAndCache($toFetch);
            $cachedItems = DB::table('cdc_cache')->whereIn('cdc', $cdcs)->get()->keyBy('cdc');
        }

        // 3. Construir filas
        $rows = [];
        foreach ($facturas as $factura) {
            if (empty($factura->numcdc)) continue;
            $cached = $cachedItems[$factura->numcdc] ?? null;
            if ($cached && $cached->success) {
                $payload = json_decode($cached->payload, true);
                $rows[]  = $this->buildRow($factura, $payload);
            }
        }

        usort($rows, fn($a, $b) => strcmp($a['fechahora'], $b['fechahora']));

        // Filtrar según el parámetro 'search' si existe
        $search = trim($request->get('search', ''));
        if ($search !== '') {
            $rows = array_filter($rows, function ($row) use ($search) {
                return stripos((string)($row['numebolo'] ?? ''), $search) !== false
                    || stripos((string)($row['cliente'] ?? ''), $search) !== false
                    || stripos((string)($row['ruc'] ?? ''), $search) !== false
                    || stripos((string)($row['tipoboleta'] ?? ''), $search) !== false
                    || stripos((string)($row['numcdc'] ?? ''), $search) !== false;
            });
        }

        // 4. Calcular totales
        $total    = collect($rows);
        $viewData = [
            'rows'         => $rows,
            'fecha_ini'    => $fechaIni,
            'fecha_fin'    => $fechaFin,
            'totalGeneral' => $total->sum('total'),
            'totalGrav10'  => $total->sum('gravadas10'),
            'totalGrav05'  => $total->sum('gravadas05'),
            'totalIva10'   => $total->sum('iva10'),
            'totalIva05'   => $total->sum('iva05'),
            'totalExentas' => $total->sum('exentas'),
        ];

        return view('contabilidad.libro-iva-print', $viewData);
    }

    private function buildQuery(string $fechaIni, string $fechaFin)
    {
        $excludedIds = ['13350', '3714389-1', '80051943-4'];

        $q1 = DB::table('ticket_cab')
            ->join('ticket_lin', 'ticket_cab.n_compro', '=', 'ticket_lin.n_compro')
            ->leftJoin('farmacia.client', 'farmacia.client.IDCLIENTE', '=', 'ticket_cab.idcliente')
            ->whereDate('ticket_cab.fecha', '>=', $fechaIni)
            ->whereDate('ticket_cab.fecha', '<=', $fechaFin)
            ->whereNotIn('ticket_cab.idcliente', $excludedIds)
            ->whereNotNull('ticket_cab.numcdc')->where('ticket_cab.numcdc', '!=', '')
            ->groupBy('ticket_cab.n_compro','ticket_cab.n_ticket','ticket_cab.codi_vend','ticket_cab.fecha','ticket_cab.idcliente','ticket_cab.nombclie','ticket_cab.formapago','ticket_cab.numcdc','farmacia.client.email')
            ->select('ticket_cab.n_compro','ticket_cab.n_ticket as n_factura','ticket_cab.codi_vend','ticket_cab.fecha',DB::raw('"CONTADO" AS tipofact'),'ticket_cab.idcliente AS ruccedula','ticket_cab.nombclie',DB::raw('SUM(ticket_lin.total) AS total'),DB::raw("IF(ticket_cab.formapago='CONTADO','EFECTIVO',ticket_cab.formapago) AS formapago"),'ticket_cab.numcdc',DB::raw('COALESCE(farmacia.client.email,"") AS email'));

        $q2 = DB::table('fact_cab')
            ->join('fact_lin', 'fact_cab.n_compro', '=', 'fact_lin.n_compro')
            ->join('farmacia.clientes', 'farmacia.clientes.codi_clie', '=', 'fact_cab.codi_clie')
            ->whereDate('fact_cab.fecha', '>=', $fechaIni)
            ->whereDate('fact_cab.fecha', '<=', $fechaFin)
            ->whereNotIn('fact_cab.codi_clie', $excludedIds)
            ->whereNotNull('fact_cab.numcdc')->where('fact_cab.numcdc', '!=', '')
            ->groupBy('fact_cab.n_compro','fact_cab.n_factura','fact_cab.codi_vend','fact_cab.fecha','farmacia.clientes.ruc','farmacia.clientes.nomb_clie','farmacia.clientes.apel_clie','fact_cab.numcdc','farmacia.clientes.email')
            ->select('fact_cab.n_compro','fact_cab.n_factura','fact_cab.codi_vend','fact_cab.fecha',DB::raw('"CREDITO" AS tipofact'),'farmacia.clientes.ruc AS ruccedula',DB::raw("CONCAT(TRIM(farmacia.clientes.nomb_clie),' ',TRIM(farmacia.clientes.apel_clie)) AS nombclie"),DB::raw('SUM(fact_lin.total) AS total'),DB::raw("'CREDITO' AS formapago"),'fact_cab.numcdc',DB::raw('COALESCE(farmacia.clientes.email,"") AS email'));

        $q3 = DB::table('notacred_cab')
            ->join('notacred_lin', 'notacred_cab.n_compro', '=', 'notacred_lin.n_compro')
            ->leftJoin('farmacia.client', 'farmacia.client.IDCLIENTE', '=', 'notacred_cab.idcliente')
            ->whereDate('notacred_cab.fecha', '>=', $fechaIni)
            ->whereDate('notacred_cab.fecha', '<=', $fechaFin)
            ->whereNotIn('notacred_cab.idcliente', $excludedIds)
            ->whereNotNull('notacred_cab.numcdc')->where('notacred_cab.numcdc', '!=', '')
            ->groupBy('notacred_cab.n_compro','notacred_cab.n_nota','notacred_cab.codi_vend','notacred_cab.fecha','notacred_cab.idcliente','notacred_cab.gran_total','notacred_cab.formapago','notacred_cab.numcdc','farmacia.client.NOMBRE','farmacia.client.email')
            ->select('notacred_cab.n_compro','notacred_cab.n_nota AS n_factura','notacred_cab.codi_vend','notacred_cab.fecha',DB::raw("'NOTACRED' AS tipofact"),'notacred_cab.idcliente AS ruccedula',DB::raw('COALESCE(farmacia.client.NOMBRE,"") AS nombclie'),'notacred_cab.gran_total AS total','notacred_cab.formapago','notacred_cab.numcdc',DB::raw('COALESCE(farmacia.client.email,"") AS email'));

        return $q1->union($q2)->union($q3)->orderBy('fecha')->get();
    }

    private function fetchAndCache(array $facturas): void
    {
        foreach (array_chunk($facturas, self::BATCH_SIZE) as $batch) {
            $indexMap = [];
            foreach ($batch as $idx => $f) { $indexMap[$idx] = $f->numcdc; }
            try {
                $apiBase = config('services.sifen.cdc_url');
                $apiKey  = config('services.sifen.api_key');
                if (empty($apiBase)) continue;

                $responses = Http::pool(function (Pool $pool) use ($batch, $apiBase, $apiKey) {
                    foreach ($batch as $idx => $f) {
                        $pool->as((string)$idx)->timeout(20)
                            ->withHeaders(['Authorization' => 'Bearer '.$apiKey, 'Accept' => 'application/json'])
                            ->get($apiBase.$f->numcdc);
                    }
                });

                $inserts = [];
                foreach ($indexMap as $idx => $cdc) {
                    $r = $responses[(string)$idx];
                    if ($r instanceof \Throwable || !$r->successful()) continue;
                    $payload = $r->json();
                    $inserts[] = ['cdc' => $cdc, 'payload' => json_encode($payload), 'success' => (bool)($payload['success'] ?? false), 'created_at' => now()];
                }
                if ($inserts) DB::table('cdc_cache')->insertOrIgnore($inserts);
            } catch (\Throwable $e) { report($e); }
        }
    }

    private function buildRow(object $factura, array $payload): array
    {
        $result  = $payload['result'] ?? [];
        $de      = $result['rde']['DE'] ?? [];
        $totSub  = $de['gTotSub'] ?? [];
        $datRec  = $de['gDatGralOpe']['gDatRec'] ?? [];
        $feEmi   = $de['gDatGralOpe']['dFeEmiDE'] ?? '';
        $rucSinDv = $datRec['dRucRec'] ?? $factura->ruccedula ?? '';
        $dv       = $datRec['dDVRec'] ?? '';
        $situacion = (int) ($result['situacion'] ?? ($payload['situacion'] ?? 0));
        $anulado   = $situacion === 99;

        return [
            'numebolo'   => $factura->n_factura,
            'fechahora'  => $result['fecha'] ?? $factura->fecha,
            'fecha'      => $feEmi ?: $factura->fecha,
            'total'      => $anulado ? 0.0 : (float)($totSub['dTotGralOpe'] ?? 0),
            'gravadas10' => $anulado ? 0.0 : (float)($totSub['dSub10'] ?? 0),
            'gravadas05' => $anulado ? 0.0 : (float)($totSub['dSub5']  ?? 0),
            'iva10'      => $anulado ? 0.0 : (float)($totSub['dIVA10'] ?? 0),
            'iva05'      => $anulado ? 0.0 : (float)($totSub['dIVA5']  ?? 0),
            'exentas'    => $anulado ? 0.0 : (float)($totSub['dSubExe'] ?? 0),
            'tipoboleta' => $factura->tipofact ?? '',
            'ruc'        => $dv !== '' ? "{$rucSinDv}-{$dv}" : $rucSinDv,
            'ruc_sin_dv' => $rucSinDv,
            'dv'         => $dv,
            'cliente'    => $datRec['dNomRec'] ?? $factura->nombclie ?? '',
            'numcdc'     => $factura->numcdc,
            'anulado'    => $anulado,
            'situacion'  => $situacion,
        ];
    }
}
