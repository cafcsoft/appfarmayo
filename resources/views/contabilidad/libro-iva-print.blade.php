<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Libro de Ventas IVA — {{ $fecha_ini }} al {{ $fecha_fin }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: Arial, sans-serif;
            font-size: 9px;
            color: #111;
            background: #fff;
        }

        /* ── Header ────────────────────────────────── */
        .header {
            text-align: center;
            padding: 12px 0 8px;
            border-bottom: 2px solid #1e3a5f;
            margin-bottom: 8px;
        }
        .header h1 { font-size: 14px; font-weight: bold; color: #1e3a5f; }
        .header p  { font-size: 9px; color: #555; margin-top: 2px; }

        /* ── Totales ────────────────────────────────── */
        .totales {
            display: flex;
            gap: 6px;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }
        .total-card {
            flex: 1;
            min-width: 90px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            padding: 5px 8px;
            text-align: center;
        }
        .total-card .label { font-size: 7px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; }
        .total-card .value { font-size: 10px; font-weight: bold; color: #111; margin-top: 2px; }

        /* ── Tabla ──────────────────────────────────── */
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: auto;
        }
        thead tr {
            background-color: #1e3a5f;
            color: #fff;
        }
        thead th {
            padding: 4px 3px;
            font-size: 8px;
            font-weight: bold;
            text-align: left;
            white-space: nowrap;
        }
        thead th.right { text-align: right; }
        thead th.center { text-align: center; }

        tbody tr:nth-child(even) { background-color: #f3f4f6; }
        tbody tr.notacred { background-color: #fef2f2; }
        tbody tr.anulado { background-color: #e5e7eb; text-decoration: line-through; color: #888; }

        tbody td {
            padding: 3px 3px;
            font-size: 8px;
            vertical-align: middle;
            border-bottom: 1px solid #e5e7eb;
            white-space: nowrap;
        }
        tbody td.right  { text-align: right; }
        tbody td.center { text-align: center; }
        tbody td.mono   { font-family: monospace; }
        tbody td.truncate { max-width: 100px; overflow: hidden; text-overflow: ellipsis; }

        /* ── Footer / totales ───────────────────────── */
        tfoot tr {
            background-color: #1e3a5f;
            color: #fff;
            font-weight: bold;
        }
        tfoot td {
            padding: 4px 3px;
            font-size: 8px;
            white-space: nowrap;
        }
        tfoot td.right { text-align: right; }

        /* ── Print ──────────────────────────────────── */
        @media print {
            body { font-size: 8px; }
            .no-print { display: none !important; }
            table { page-break-inside: auto; }
            tr    { page-break-inside: avoid; }
            thead { display: table-header-group; }
            tfoot { display: table-footer-group; }

            @page {
                size: A4 landscape;
                margin: 10mm 8mm;
            }
        }

        /* ── Print button ───────────────────────────── */
        .print-bar {
            position: fixed;
            top: 0; left: 0; right: 0;
            background: #1e3a5f;
            color: #fff;
            padding: 8px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 999;
        }
        .print-bar span { font-size: 11px; font-weight: bold; }
        .print-bar button {
            background: #fff;
            color: #1e3a5f;
            border: none;
            border-radius: 4px;
            padding: 5px 14px;
            font-size: 11px;
            font-weight: bold;
            cursor: pointer;
        }
        .print-bar button:hover { background: #e5e7eb; }

        .content { margin-top: 44px; padding: 8px 12px; }
    </style>
</head>
<body>

    {{-- Barra de impresión --}}
    <div class="print-bar no-print">
        <span>Libro de Ventas IVA — {{ \Carbon\Carbon::parse($fecha_ini)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($fecha_fin)->format('d/m/Y') }}</span>
        <button onclick="window.print()">🖨️ Imprimir / Guardar PDF</button>
    </div>

    <div class="content">
        {{-- Header --}}
        <div class="header">
            <h1>LIBRO DE VENTAS IVA</h1>
            <p>
                Período: {{ \Carbon\Carbon::parse($fecha_ini)->format('d/m/Y') }}
                al {{ \Carbon\Carbon::parse($fecha_fin)->format('d/m/Y') }}
                — {{ count($rows) }} comprobantes
            </p>
        </div>

        {{-- Totales --}}
        <div class="totales">
            <div class="total-card">
                <div class="label">Total General</div>
                <div class="value">{{ number_format($totalGeneral, 0, ',', '.') }}</div>
            </div>
            <div class="total-card">
                <div class="label">Gravadas 10%</div>
                <div class="value">{{ number_format($totalGrav10, 2, ',', '.') }}</div>
            </div>
            <div class="total-card">
                <div class="label">Gravadas 5%</div>
                <div class="value">{{ number_format($totalGrav05, 2, ',', '.') }}</div>
            </div>
            <div class="total-card">
                <div class="label">IVA 10%</div>
                <div class="value">{{ number_format($totalIva10, 2, ',', '.') }}</div>
            </div>
            <div class="total-card">
                <div class="label">IVA 5%</div>
                <div class="value">{{ number_format($totalIva05, 2, ',', '.') }}</div>
            </div>
            <div class="total-card">
                <div class="label">Exentas</div>
                <div class="value">{{ number_format($totalExentas, 2, ',', '.') }}</div>
            </div>
        </div>

        {{-- Tabla completa --}}
        <table>
            <thead>
                <tr>
                    <th>Numebolo</th>
                    <th>Fechahora</th>
                    <th>Fecha</th>
                    <th class="right">Total</th>
                    <th class="right">Gravadas10</th>
                    <th class="right">Gravadas05</th>
                    <th class="right">Iva10</th>
                    <th class="right">Iva05</th>
                    <th class="right">Exentas</th>
                    <th class="center">Tipoboleta</th>
                    <th>Ruc</th>
                    <th>Ruc_sin_dv</th>
                    <th class="center">Dv</th>
                    <th>Cliente</th>
                    <th>numcdc</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr class="{{ $row['anulado'] ? 'anulado' : ($row['tipoboleta'] === 'NOTACRED' ? 'notacred' : '') }}">
                        <td class="mono">{{ $row['numebolo'] }}</td>
                        <td>{{ $row['fechahora'] ? \Carbon\Carbon::parse($row['fechahora'])->format('d/m/Y H:i') : '-' }}</td>
                        <td>{{ $row['fecha'] ? \Carbon\Carbon::parse($row['fecha'])->format('d/m/Y') : '-' }}</td>
                        <td class="right">{{ number_format((float) $row['total'],      0, ',', '.') }}</td>
                        <td class="right">{{ number_format((float) $row['gravadas10'], 2, ',', '.') }}</td>
                        <td class="right">{{ number_format((float) $row['gravadas05'], 2, ',', '.') }}</td>
                        <td class="right">{{ number_format((float) $row['iva10'],      2, ',', '.') }}</td>
                        <td class="right">{{ number_format((float) $row['iva05'],      2, ',', '.') }}</td>
                        <td class="right">{{ number_format((float) $row['exentas'],    2, ',', '.') }}</td>
                        <td class="center">{{ $row['anulado'] ? 'ANULADO' : $row['tipoboleta'] }}</td>
                        <td class="mono">{{ $row['ruc'] }}</td>
                        <td class="mono">{{ $row['ruc_sin_dv'] }}</td>
                        <td class="center mono">{{ $row['dv'] }}</td>
                        <td class="truncate">{{ $row['cliente'] }}</td>
                        <td class="mono" style="max-width:80px; overflow:hidden; text-overflow:ellipsis;" title="{{ $row['numcdc'] }}">{{ $row['numcdc'] }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3">TOTALES — {{ count($rows) }} registros</td>
                    <td class="right">{{ number_format($totalGeneral, 0, ',', '.') }}</td>
                    <td class="right">{{ number_format($totalGrav10,  2, ',', '.') }}</td>
                    <td class="right">{{ number_format($totalGrav05,  2, ',', '.') }}</td>
                    <td class="right">{{ number_format($totalIva10,   2, ',', '.') }}</td>
                    <td class="right">{{ number_format($totalIva05,   2, ',', '.') }}</td>
                    <td class="right">{{ number_format($totalExentas, 2, ',', '.') }}</td>
                    <td colspan="6"></td>
                </tr>
            </tfoot>
        </table>
    </div>

</body>
</html>
