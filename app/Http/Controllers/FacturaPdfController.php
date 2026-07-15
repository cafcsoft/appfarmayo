<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class FacturaPdfController extends Controller
{
    public function show(Request $request, string $tipo, int $n_compro)
    {
        $factura = null;
        $detalle = [];

        if ($tipo === 'contado') {
            $factura = DB::table('ticket_cab')
                ->leftJoin('farmacia.client', 'farmacia.client.IDCLIENTE', '=', 'ticket_cab.idcliente')
                ->where('ticket_cab.n_compro', $n_compro)
                ->where('ticket_cab.idcliente', '!=', '13350')
                ->where('ticket_cab.idcliente', '!=', '3714389-1')
                ->where('ticket_cab.idcliente', '!=', '80051943-4')
                ->select(
                    'ticket_cab.n_compro',
                    'ticket_cab.n_ticket as n_factura',
                    'ticket_cab.codi_vend',
                    'ticket_cab.fecha',
                    DB::raw('"CONTADO" as tipofact'),
                    'ticket_cab.idcliente as ruccedula',
                    'ticket_cab.nombclie',
                    DB::raw("IF(ticket_cab.formapago = 'CONTADO', 'EFECTIVO', ticket_cab.formapago) AS formapago"),
                    'ticket_cab.numcdc as num_cdc',
                    DB::raw('COALESCE(farmacia.client.email, "") AS email')
                )
                ->first();

            $detalle = DB::table('ticket_lin')
                ->where('n_compro', $n_compro)
                ->get();

        } elseif ($tipo === 'credito') {
            $factura = DB::table('fact_cab')
                ->join('farmacia.clientes', 'farmacia.clientes.codi_clie', '=', 'fact_cab.codi_clie')
                ->where('fact_cab.n_compro', $n_compro)
                ->where('fact_cab.codi_clie', '!=', '13350')
                ->where('fact_cab.codi_clie', '!=', '3714389-1')
                ->where('fact_cab.codi_clie', '!=', '80051943-4')
                ->select(
                    'fact_cab.n_compro',
                    'fact_cab.n_factura',
                    'fact_cab.codi_vend',
                    'fact_cab.fecha',
                    DB::raw('"CREDITO" as tipofact'),
                    'farmacia.clientes.ruc as ruccedula',
                    DB::raw("COALESCE(CONCAT(TRIM(farmacia.clientes.nomb_clie), ' ', TRIM(farmacia.clientes.apel_clie)), fact_cab.nombreTxt, '') AS nombclie"),
                    DB::raw("'CREDITO' AS formapago"),
                    'fact_cab.numcdc as num_cdc',
                    DB::raw('COALESCE(farmacia.clientes.email, "") AS email')
                )
                ->first();

            $detalle = DB::table('fact_lin')
                ->where('n_compro', $n_compro)
                ->get();
        } elseif ($tipo === 'notacred') {
            $factura = DB::table('notacred_cab')
                ->leftJoin('farmacia.client', 'farmacia.client.IDCLIENTE', '=', 'notacred_cab.idcliente')
                ->where('notacred_cab.n_compro', $n_compro)
                ->where('notacred_cab.idcliente', '!=', '13350')
                ->where('notacred_cab.idcliente', '!=', '3714389-1')
                ->where('notacred_cab.idcliente', '!=', '80051943-4')
                ->select(
                    'notacred_cab.n_compro',
                    'notacred_cab.n_nota AS n_factura',
                    'notacred_cab.codi_vend',
                    'notacred_cab.fecha',
                    DB::raw("'NOTACRED' as tipofact"),
                    'notacred_cab.idcliente as ruccedula',
                    DB::raw('COALESCE(farmacia.client.NOMBRE, "") AS nombclie'),
                    'notacred_cab.formapago',
                    'notacred_cab.numcdc as num_cdc',
                    DB::raw('COALESCE(farmacia.client.email, "") AS email')
                )
                ->first();

            $detalle = DB::table('notacred_lin')
                ->where('n_compro', $n_compro)
                ->get();
        }

        if (! $factura) {
            abort(404, 'Factura no encontrada');
        }

        $anulado = false;
        if (!empty($factura->num_cdc)) {
            $cached = DB::table('cdc_cache')->where('cdc', $factura->num_cdc)->first();
            if ($cached && $cached->success) {
                $payload = json_decode($cached->payload, true);
                $situacion = (int) ($payload['result']['situacion'] ?? ($payload['situacion'] ?? 0));
                if ($situacion === 99) {
                    $anulado = true;
                }
            } else {
                try {
                    $apiBase = config('services.sifen.cdc_url');
                    $apiKey  = config('services.sifen.api_key');
                    if (!empty($apiBase) && !empty($apiKey)) {
                        $res = Http::timeout(5)
                            ->withHeaders([
                                'Authorization' => 'Bearer ' . $apiKey,
                                'Accept'        => 'application/json',
                            ])
                            ->get($apiBase . $factura->num_cdc);
                        
                        if ($res->successful()) {
                            $payload = $res->json();
                            $success = (bool) ($payload['success'] ?? false);
                            
                            DB::table('cdc_cache')->insertOrIgnore([
                                'cdc'        => $factura->num_cdc,
                                'payload'    => json_encode($payload),
                                'success'    => $success,
                                'created_at' => now(),
                            ]);
                            
                            $situacion = (int) ($payload['result']['situacion'] ?? ($payload['situacion'] ?? 0));
                            if ($situacion === 99) {
                                $anulado = true;
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        return view('pdf.factura', compact('factura', 'detalle', 'anulado'));
    }

    public function downloadKude(Request $request)
    {
        $cdc = $request->get('cdc');
        $cdc = trim($cdc);

        if (! $cdc) {
            return response()->json(['error' => 'CDC no proporcionado'], 400);
        }

        $apiKey = config('services.sifen.api_key');

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$apiKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post(config('services.sifen.api_url'), [
            'cdcList' => [
                ['cdc' => $cdc],
            ],
            'type' => 'base64',
            'format' => 'factura',
        ]);


        if ($response->successful()) {
            $data = $response->json();
            $pdfData = null;

            // Case 1: JSON array with first element having base64
            if (is_array($data) && isset($data[0]['base64'])) {
                $pdfData = base64_decode($data[0]['base64']);
            }
            // Case 2: JSON object with base64 property
            elseif (is_array($data) && isset($data['base64'])) {
                $pdfData = base64_decode($data['base64']);
            }
            // Case 3: The body might be the raw base64 or binary data
            else {
                $body = $response->body();

                // Check if it's already binary PDF
                if (str_starts_with($body, '%PDF-')) {
                    $pdfData = $body;
                } else {
                    // Try decoding it as base64
                    $decoded = base64_decode($body, true);
                    if ($decoded !== false && str_starts_with($decoded, '%PDF-')) {
                        $pdfData = $decoded;
                    }
                }
            }

            if ($pdfData) {
                return response($pdfData)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', 'inline; filename="kude_'.$cdc.'.pdf"');
            }
        }

        return response()->json([
            'error' => 'No se pudo obtener el PDF o la respuesta no tiene el formato esperado',
            'status' => $response->status(),
            'api_response' => $response->json() ?: $response->body(),
        ], 500);
    }
}
