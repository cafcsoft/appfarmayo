<?php

namespace App\Livewire\Contabilidad;

use Carbon\Carbon;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Component;

class LibroVentasIva extends Component
{
    public string $fecha_ini = '';
    public string $fecha_fin = '';

    /** @var array<int,array<string,mixed>> */
    public array $libroRows = [];

    public bool $generado = false;
    public bool $processing = false;
    public string $errorMsg = '';

    // Totales para el resumen
    public float $totalGeneral    = 0;
    public float $totalGrav10     = 0;
    public float $totalGrav05     = 0;
    public float $totalIva10      = 0;
    public float $totalIva05      = 0;
    public float $totalExentas    = 0;
    public int   $totalRegistros  = 0;

    // Paginación
    public int $perPage     = 50;
    public int $currentPage = 1;

    // Búsqueda
    public string $search = '';

    /** Cantidad de requests concurrentes al pool de HTTP */
    private const BATCH_SIZE = 15;

    public function mount(): void
    {
        $this->fecha_ini = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->fecha_fin = Carbon::now()->format('Y-m-d');
    }

    public function updatedSearch(): void
    {
        $this->currentPage = 1;
    }

    // ──────────────────────────────────────────────────────────────────────
    //  Acción principal: genera el libro consultando BD + API
    // ──────────────────────────────────────────────────────────────────────

    /** Diagnóstico visible para depuración */
    public array $diag = [];

    public function generarLibro(): void
    {
        $this->errorMsg  = '';
        $this->libroRows = [];
        $this->generado  = false;
        $this->diag      = [];

        if (!$this->fecha_ini || !$this->fecha_fin) {
            $this->errorMsg = 'Por favor selecciona el rango de fechas.';
            return;
        }

        // 1. Obtener todas las facturas con CDC del período
        $facturas = $this->buildQuery();
        $this->diag['paso1_total_bd'] = $facturas->count();
        $this->diag['paso1_con_cdc']  = $facturas->filter(fn($f) => !empty($f->numcdc))->count();

        if ($facturas->isEmpty()) {
            $this->errorMsg = 'No se encontraron comprobantes en el período (ni siquiera sin CDC). Verifique las fechas.';
            $this->generado = true;
            return;
        }

        if ($this->diag['paso1_con_cdc'] === 0) {
            $this->errorMsg = 'Se encontraron ' . $this->diag['paso1_total_bd'] . ' comprobantes pero ninguno tiene CDC registrado en la BD.';
            $this->generado = true;
            return;
        }

        // 2. Separar las que están en caché de las que hay que consultar a la API
        $cdcs        = $facturas->pluck('numcdc')->filter()->unique()->values()->toArray();
        $cachedItems = DB::table('cdc_cache')->whereIn('cdc', $cdcs)->get()->keyBy('cdc');

        $toFetch = $facturas->filter(
            fn($f) => !empty($f->numcdc) && !isset($cachedItems[$f->numcdc])
        )->values()->toArray();

        $this->diag['paso2_en_cache']    = count($cachedItems);
        $this->diag['paso2_a_consultar'] = count($toFetch);

        // 3. Llamar a la API en lotes concurrentes para las no cacheadas
        if (!empty($toFetch)) {
            $apiErrors = $this->fetchAndCache($toFetch);
            // Recargar cache después de insertar
            $cachedItems = DB::table('cdc_cache')->whereIn('cdc', $cdcs)->get()->keyBy('cdc');
            $this->diag['paso3_guardados_cache'] = count($cachedItems);
            $this->diag['paso3_errores_api']     = $apiErrors;
        }

        // 4. Construir filas del libro
        $rows = [];
        $sinCache = [];
        foreach ($facturas as $factura) {
            if (empty($factura->numcdc)) {
                continue;
            }
            $cached = $cachedItems[$factura->numcdc] ?? null;
            if ($cached && $cached->success) {
                $payload  = json_decode($cached->payload, true);
                $rows[]   = $this->buildRow($factura, $payload);
            } elseif ($cached && !$cached->success) {
                $sinCache[] = $factura->numcdc . ' (API: error)';
            } elseif (!$cached) {
                $sinCache[] = $factura->numcdc . ' (sin caché)';
            }
        }

        $this->diag['paso4_filas_construidas'] = count($rows);
        $this->diag['paso4_sin_datos']         = $sinCache;

        // 5. Ordenar por fecha
        usort($rows, fn($a, $b) => strcmp($a['fechahora'], $b['fechahora']));

        $this->libroRows = $rows;

        // 6. Calcular totales
        $this->totalGeneral   = collect($rows)->sum('total');
        $this->totalGrav10    = collect($rows)->sum('gravadas10');
        $this->totalGrav05    = collect($rows)->sum('gravadas05');
        $this->totalIva10     = collect($rows)->sum('iva10');
        $this->totalIva05     = collect($rows)->sum('iva05');
        $this->totalExentas   = collect($rows)->sum('exentas');
        $this->totalRegistros = count($rows);

        $this->generado = true;
    }

    // ──────────────────────────────────────────────────────────────────────
    //  Consulta a BD local (ticket_cab + fact_cab + notacred_cab)
    // ──────────────────────────────────────────────────────────────────────

    private function buildQuery()
    {
        $excludedIds = ['13350', '3714389-1', '80051943-4'];

        // ── CONTADO: ticket_cab ──────────────────────────────────────────
        $q1 = DB::table('ticket_cab')
            ->join('ticket_lin', 'ticket_cab.n_compro', '=', 'ticket_lin.n_compro')
            ->leftJoin('farmacia.client', 'farmacia.client.IDCLIENTE', '=', 'ticket_cab.idcliente')
            ->whereDate('ticket_cab.fecha', '>=', $this->fecha_ini)
            ->whereDate('ticket_cab.fecha', '<=', $this->fecha_fin)
            ->whereNotIn('ticket_cab.idcliente', $excludedIds)
            ->whereNotNull('ticket_cab.numcdc')
            ->where('ticket_cab.numcdc', '!=', '')
            ->groupBy(
                'ticket_cab.n_compro', 'ticket_cab.n_ticket', 'ticket_cab.codi_vend',
                'ticket_cab.fecha', 'ticket_cab.idcliente', 'ticket_cab.nombclie',
                'ticket_cab.formapago', 'ticket_cab.numcdc', 'farmacia.client.email'
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
                DB::raw("IF(ticket_cab.formapago = 'CONTADO','EFECTIVO',ticket_cab.formapago) AS formapago"),
                'ticket_cab.numcdc',
                DB::raw('COALESCE(farmacia.client.email,"") AS email')
            );

        // ── CRÉDITO: fact_cab ────────────────────────────────────────────
        $q2 = DB::table('fact_cab')
            ->join('fact_lin', 'fact_cab.n_compro', '=', 'fact_lin.n_compro')
            ->join('farmacia.clientes', 'farmacia.clientes.codi_clie', '=', 'fact_cab.codi_clie')
            ->whereDate('fact_cab.fecha', '>=', $this->fecha_ini)
            ->whereDate('fact_cab.fecha', '<=', $this->fecha_fin)
            ->whereNotIn('fact_cab.codi_clie', $excludedIds)
            ->whereNotNull('fact_cab.numcdc')
            ->where('fact_cab.numcdc', '!=', '')
            ->groupBy(
                'fact_cab.n_compro', 'fact_cab.n_factura', 'fact_cab.codi_vend',
                'fact_cab.fecha', 'farmacia.clientes.ruc', 'farmacia.clientes.nomb_clie',
                'farmacia.clientes.apel_clie', 'fact_cab.numcdc', 'farmacia.clientes.email'
            )
            ->select(
                'fact_cab.n_compro',
                'fact_cab.n_factura',
                'fact_cab.codi_vend',
                'fact_cab.fecha',
                DB::raw('"CREDITO" AS tipofact'),
                'farmacia.clientes.ruc AS ruccedula',
                DB::raw("CONCAT(TRIM(farmacia.clientes.nomb_clie),' ',TRIM(farmacia.clientes.apel_clie)) AS nombclie"),
                DB::raw('SUM(fact_lin.total) AS total'),
                DB::raw("'CREDITO' AS formapago"),
                'fact_cab.numcdc',
                DB::raw('COALESCE(farmacia.clientes.email,"") AS email')
            );

        // ── NOTA CRÉDITO: notacred_cab ───────────────────────────────────
        $q3 = DB::table('notacred_cab')
            ->join('notacred_lin', 'notacred_cab.n_compro', '=', 'notacred_lin.n_compro')
            ->leftJoin('farmacia.client', 'farmacia.client.IDCLIENTE', '=', 'notacred_cab.idcliente')
            ->whereDate('notacred_cab.fecha', '>=', $this->fecha_ini)
            ->whereDate('notacred_cab.fecha', '<=', $this->fecha_fin)
            ->whereNotIn('notacred_cab.idcliente', $excludedIds)
            ->whereNotNull('notacred_cab.numcdc')
            ->where('notacred_cab.numcdc', '!=', '')
            ->groupBy(
                'notacred_cab.n_compro', 'notacred_cab.n_nota', 'notacred_cab.codi_vend',
                'notacred_cab.fecha', 'notacred_cab.idcliente', 'notacred_cab.gran_total',
                'notacred_cab.formapago', 'notacred_cab.numcdc',
                'farmacia.client.NOMBRE', 'farmacia.client.email'
            )
            ->select(
                'notacred_cab.n_compro',
                'notacred_cab.n_nota AS n_factura',
                'notacred_cab.codi_vend',
                'notacred_cab.fecha',
                DB::raw("'NOTACRED' AS tipofact"),
                'notacred_cab.idcliente AS ruccedula',
                DB::raw('COALESCE(farmacia.client.NOMBRE,"") AS nombclie'),
                'notacred_cab.gran_total AS total',
                'notacred_cab.formapago',
                'notacred_cab.numcdc',
                DB::raw('COALESCE(farmacia.client.email,"") AS email')
            );

        return $q1->union($q2)->union($q3)->orderBy('fecha')->get();
    }

    // ──────────────────────────────────────────────────────────────────────
    //  Llamada a la API en lotes concurrentes y guardado en caché
    // ──────────────────────────────────────────────────────────────────────

    private function fetchAndCache(array $facturas): array
    {
        $batches   = array_chunk($facturas, self::BATCH_SIZE);
        $allErrors = [];

        foreach ($batches as $batch) {
            // Mapeo índice → CDC para acceder a los resultados del pool
            $indexMap = [];
            foreach ($batch as $idx => $factura) {
                $indexMap[$idx] = $factura->numcdc;
            }

            try {
                $apiBase = config('services.sifen.cdc_url');
                $apiKey  = config('services.sifen.api_key');

                if (empty($apiBase)) {
                    $allErrors[] = 'La variable SIFEN_CDC_URL no está configurada en .env o el caché de configuración es antiguo. Ejecuta: php artisan config:clear && php artisan optimize';
                    break;
                }

                $responses = Http::pool(function (Pool $pool) use ($batch, $apiBase, $apiKey) {
                    foreach ($batch as $idx => $factura) {
                        $pool->as((string) $idx)
                            ->timeout(20)
                            ->withHeaders([
                                'Authorization' => 'Bearer ' . $apiKey,
                                'Accept'        => 'application/json',
                            ])
                            ->get($apiBase . $factura->numcdc);
                    }
                });

                $inserts = [];
                foreach ($indexMap as $idx => $cdc) {
                    $response = $responses[(string) $idx];

                    if ($response instanceof \Throwable) {
                        $allErrors[] = "CDC {$cdc}: " . $response->getMessage();
                        continue;
                    }

                    if (!$response->successful()) {
                        $allErrors[] = "CDC {$cdc}: HTTP {$response->status()}";
                        continue;
                    }

                    $payload = $response->json();
                    $success = (bool) ($payload['success'] ?? false);

                    if (!$success) {
                        $allErrors[] = "CDC {$cdc}: API retornó success=false — " . ($payload['message'] ?? '');
                    }

                    $inserts[] = [
                        'cdc'        => $cdc,
                        'payload'    => json_encode($payload),
                        'success'    => $success,
                        'created_at' => now(),
                    ];
                }

                if (!empty($inserts)) {
                    // insertOrIgnore evita duplicados si el CDC ya existe
                    DB::table('cdc_cache')->insertOrIgnore($inserts);
                }
            } catch (\Throwable $e) {
                $allErrors[] = 'Excepción en lote: ' . $e->getMessage();
                report($e);
            }
        }

        return $allErrors;
    }

    // ──────────────────────────────────────────────────────────────────────
    //  Construcción de una fila del libro desde el payload de la API
    // ──────────────────────────────────────────────────────────────────────

    private function buildRow(object $factura, array $payload): array
    {
        $result  = $payload['result']                       ?? [];
        $de      = $result['rde']['DE']                     ?? [];
        $totSub  = $de['gTotSub']                           ?? [];
        $datRec  = $de['gDatGralOpe']['gDatRec']            ?? [];
        $feEmi   = $de['gDatGralOpe']['dFeEmiDE']           ?? '';

        $rucSinDv = $datRec['dRucRec'] ?? $factura->ruccedula ?? '';
        $dv       = $datRec['dDVRec']  ?? '';
        $ruc      = $dv !== '' ? "{$rucSinDv}-{$dv}" : $rucSinDv;
        $cliente  = $datRec['dNomRec'] ?? $factura->nombclie ?? '';

        $situacion = (int) ($result['situacion'] ?? ($payload['situacion'] ?? 0));
        $anulado   = $situacion === 99;

        return [
            'n_compro'   => $factura->n_compro,
            'numebolo'   => $factura->n_factura,
            'fechahora'  => $result['fecha'] ?? $factura->fecha,
            'fecha'      => $feEmi          ?: $factura->fecha,
            'total'      => $anulado ? 0.0 : (float) ($totSub['dTotGralOpe'] ?? 0),
            'gravadas10' => $anulado ? 0.0 : (float) ($totSub['dSub10']      ?? 0),
            'gravadas05' => $anulado ? 0.0 : (float) ($totSub['dSub5']       ?? 0),
            'iva10'      => $anulado ? 0.0 : (float) ($totSub['dIVA10']      ?? 0),
            'iva05'      => $anulado ? 0.0 : (float) ($totSub['dIVA5']       ?? 0),
            'exentas'    => $anulado ? 0.0 : (float) ($totSub['dSubExe']     ?? 0),
            'tipoboleta' => $factura->tipofact ?? $factura->formapago ?? '',
            'ruc'        => $ruc,
            'ruc_sin_dv' => $rucSinDv,
            'dv'         => $dv,
            'cliente'    => $cliente,
            'numcdc'     => $factura->numcdc,
            'anulado'    => $anulado,
            'situacion'  => $situacion,
        ];
    }

    public function getFilteredRows(): array
    {
        $search = trim($this->search);
        if ($search === '') {
            return $this->libroRows;
        }

        return array_filter($this->libroRows, function ($row) use ($search) {
            $upperSearch = strtoupper($search);
            return stripos((string)($row['numebolo'] ?? ''), $search) !== false
                || stripos((string)($row['cliente'] ?? ''), $search) !== false
                || stripos((string)($row['ruc'] ?? ''), $search) !== false
                || stripos((string)($row['tipoboleta'] ?? ''), $search) !== false
                || stripos((string)($row['numcdc'] ?? ''), $search) !== false
                || ($upperSearch === 'ANULADO' && ($row['anulado'] ?? false));
        });
    }

    // ──────────────────────────────────────────────────────────────────────
    //  Exportación (usa libroRows COMPLETO y filtrado, no solo la página actual)
    // ──────────────────────────────────────────────────────────────────────

    public function exportExcel(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $rowsToExport = $this->getFilteredRows();
        if (empty($rowsToExport)) {
            return response()->streamDownload(fn() => '', 'vacio.csv');
        }

        $filename = 'libro_iva_' . $this->fecha_ini . '_' . $this->fecha_fin . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
        ];

        return response()->streamDownload(function () use ($rowsToExport) {
            // BOM UTF-8 para que Excel lo abra correctamente
            echo "\xEF\xBB\xBF";

            $out = fopen('php://output', 'w');

            // Cabecera
            fputcsv($out, [
                'Numebolo', 'Fechahora', 'Fecha', 'Total',
                'Gravadas10', 'Gravadas05', 'Iva10', 'Iva05', 'Exentas',
                'Tipoboleta', 'Ruc', 'Ruc_sin_dv', 'Dv', 'Cliente', 'numcdc',
            ], ';');

            $expTotalGeneral = 0;
            $expTotalGrav10  = 0;
            $expTotalGrav05  = 0;
            $expTotalIva10   = 0;
            $expTotalIva05   = 0;
            $expTotalExentas = 0;

            // Filas — dataset filtrado completo
            foreach ($rowsToExport as $row) {
                $expTotalGeneral += (float) $row['total'];
                $expTotalGrav10  += (float) $row['gravadas10'];
                $expTotalGrav05  += (float) $row['gravadas05'];
                $expTotalIva10   += (float) $row['iva10'];
                $expTotalIva05   += (float) $row['iva05'];
                $expTotalExentas += (float) $row['exentas'];

                fputcsv($out, [
                    $row['numebolo'],
                    $row['fechahora'],
                    $row['fecha'],
                    number_format((float) $row['total'],      0, ',', ''),
                    number_format((float) $row['gravadas10'], 2, ',', ''),
                    number_format((float) $row['gravadas05'], 2, ',', ''),
                    number_format((float) $row['iva10'],      2, ',', ''),
                    number_format((float) $row['iva05'],      2, ',', ''),
                    number_format((float) $row['exentas'],    2, ',', ''),
                    $row['tipoboleta'],
                    $row['ruc'],
                    $row['ruc_sin_dv'],
                    $row['dv'],
                    $row['cliente'],
                    $row['numcdc'],
                ], ';');
            }

            // Fila de totales
            fputcsv($out, [
                'TOTALES', '', '', number_format($expTotalGeneral, 0, ',', ''),
                number_format($expTotalGrav10, 2, ',', ''),
                number_format($expTotalGrav05, 2, ',', ''),
                number_format($expTotalIva10,  2, ',', ''),
                number_format($expTotalIva05,  2, ',', ''),
                number_format($expTotalExentas, 2, ',', ''),
                '', '', '', '', '', '',
            ], ';');

            fclose($out);
        }, $filename, $headers);
    }

    public function exportPdf(): void
    {
        // Genera un token firmado con los parámetros y redirige a la ruta de impresión
        $params = http_build_query([
            'fecha_ini' => $this->fecha_ini,
            'fecha_fin' => $this->fecha_fin,
            'search'    => $this->search,
        ]);

        $this->redirect(route('contabilidad.libro-iva.print') . '?' . $params);
    }

    // ──────────────────────────────────────────────────────────────────────
    //  Paginación
    // ──────────────────────────────────────────────────────────────────────

    public function previousPage(): void
    {
        if ($this->currentPage > 1) {
            $this->currentPage--;
        }
    }

    public function nextPage(): void
    {
        $filteredRows = $this->getFilteredRows();
        $totalPages = (int) ceil(count($filteredRows) / $this->perPage);
        if ($this->currentPage < $totalPages) {
            $this->currentPage++;
        }
    }

    public function goToPage(int $page): void
    {
        $this->currentPage = $page;
    }

    // ──────────────────────────────────────────────────────────────────────
    //  Formato numérico para la vista
    // ──────────────────────────────────────────────────────────────────────

    public function fmt(float $value): string
    {
        return number_format($value, 0, ',', '.');
    }

    public function render()
    {
        $filteredRows = $this->getFilteredRows();
        $total        = count($filteredRows);
        $totalPages   = $total > 0 ? (int) ceil($total / $this->perPage) : 1;
        $this->currentPage = min($this->currentPage, $totalPages);

        $paginatedRows = array_slice(
            $filteredRows,
            ($this->currentPage - 1) * $this->perPage,
            $this->perPage
        );

        // Recalcular totales dinámicamente según la búsqueda
        $this->totalGeneral   = collect($filteredRows)->sum('total');
        $this->totalGrav10    = collect($filteredRows)->sum('gravadas10');
        $this->totalGrav05    = collect($filteredRows)->sum('gravadas05');
        $this->totalIva10     = collect($filteredRows)->sum('iva10');
        $this->totalIva05     = collect($filteredRows)->sum('iva05');
        $this->totalExentas   = collect($filteredRows)->sum('exentas');
        $this->totalRegistros = $total;

        return view('livewire.contabilidad.libro-ventas-iva', [
            'paginatedRows' => $paginatedRows,
            'totalPages'    => $totalPages,
        ])->layout('layouts.app', ['title' => __('Libro de Ventas IVA')]);
    }
}
