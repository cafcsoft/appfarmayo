<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura {{ $factura->n_factura }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 13px;
            color: #1a1a2e;
            background: #fff;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 3px solid #4f46e5;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }

        .company-name {
            font-size: 22px;
            font-weight: 800;
            color: #4f46e5;
            letter-spacing: -0.5px;
        }

        .company-sub {
            color: #666;
            font-size: 12px;
            margin-top: 4px;
        }

        .invoice-box {
            text-align: right;
        }

        .invoice-title {
            font-size: 18px;
            font-weight: 700;
            color: #1a1a2e;
        }

        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            margin-top: 4px;
        }

        .badge-contado {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .badge-credito {
            background: #dcfce7;
            color: #15803d;
        }

        .badge-notacred {
            background: #fef9c3;
            color: #854d0e;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }

        .meta-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 14px 16px;
        }

        .meta-label {
            font-size: 10px;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .meta-value {
            font-size: 14px;
            font-weight: 600;
            color: #1e293b;
            margin-top: 3px;
        }

        table.detail {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table.detail thead tr {
            background: #4f46e5;
            color: white;
        }

        table.detail thead th {
            padding: 9px 12px;
            text-align: left;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        table.detail thead th:last-child {
            text-align: right;
        }

        table.detail tbody tr {
            border-bottom: 1px solid #f1f5f9;
        }

        table.detail tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        table.detail tbody td {
            padding: 8px 12px;
            font-size: 12px;
        }

        table.detail tbody td:last-child {
            text-align: right;
            font-weight: 600;
        }

        .totals {
            display: flex;
            justify-content: flex-end;
        }

        .totals-box {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            min-width: 260px;
        }

        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 16px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13px;
        }

        .totals-row:last-child {
            border-bottom: none;
            background: #4f46e5;
            color: white;
            font-weight: 700;
            font-size: 15px;
        }

        .totals-label {
            color: #64748b;
        }

        .totals-row:last-child .totals-label {
            color: rgba(255, 255, 255, 0.8);
        }

        .cdc-box {
            margin-top: 24px;
            background: #f0f4ff;
            border: 1px solid #c7d2fe;
            border-radius: 8px;
            padding: 10px 16px;
            font-size: 11px;
            color: #4338ca;
        }

        .cdc-label {
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .cdc-value {
            font-family: monospace;
            font-size: 12px;
            margin-top: 2px;
            word-break: break-all;
        }

        .footer {
            margin-top: 30px;
            padding-top: 14px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            color: #94a3b8;
            font-size: 11px;
        }

        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-35deg);
            font-size: 110px;
            font-weight: 900;
            color: rgba(239, 68, 68, 0.15); /* Rojo con baja opacidad */
            border: 12px double rgba(239, 68, 68, 0.15);
            padding: 15px 40px;
            border-radius: 16px;
            text-transform: uppercase;
            pointer-events: none;
            z-index: 9999;
            user-select: none;
            white-space: nowrap;
        }

        @media print {
            body {
                padding: 10px;
            }

            .no-print {
                display: none !important;
            }

            @page {
                margin: 1cm;
            }
        }
    </style>
</head>

<body>
    @if ($anulado)
        <div class="watermark">ANULADO</div>
    @endif
    {{-- Print button (hidden on print) --}}
    <div class="no-print" style="text-align:right; margin-bottom:16px;">
        <button onclick="window.print()"
            style="background:#4f46e5;color:white;border:none;padding:8px 20px;border-radius:6px;font-size:13px;font-weight:700;cursor:pointer;">
            🖨 Imprimir / Guardar PDF
        </button>
        <button onclick="window.close()"
            style="background:#e2e8f0;color:#374151;border:none;padding:8px 20px;border-radius:6px;font-size:13px;font-weight:700;cursor:pointer;margin-left:8px;">
            ✕ Cerrar
        </button>
    </div>

    {{-- Header --}}
    <div class="header">
        <div>
            <div class="company-name">gFarmacia</div>
            <div class="company-sub">Sistema de Gestión Farmacéutica</div>
        </div>
        <div class="invoice-box">
            <div class="invoice-title">{{ $factura->n_factura }}</div>
            @if ($factura->tipofact === 'CONTADO')
                <span class="badge badge-contado">CONTADO</span>
            @elseif($factura->tipofact === 'CREDITO')
                <span class="badge badge-credito">CRÉDITO</span>
            @else
                <span class="badge badge-notacred">NOTA DE CRÉDITO</span>
            @endif
        </div>
    </div>

    {{-- Meta --}}
    <div class="meta-grid">
        <div class="meta-box">
            <div class="meta-label">Cliente</div>
            <div class="meta-value">{{ $factura->nombclie ?: 'N/D' }}</div>
            @if ($factura->ruccedula)
                <div style="color:#64748b;font-size:12px;margin-top:2px;">RUC/CI: {{ $factura->ruccedula }}</div>
            @endif
            @if ($factura->email)
                <div style="color:#64748b;font-size:12px;margin-top:2px;">{{ $factura->email }}</div>
            @endif
        </div>
        <div class="meta-box">
            <div class="meta-label">Datos del Comprobante</div>
            <div class="meta-value">Fecha: {{ \Carbon\Carbon::parse($factura->fecha)->format('d/m/Y') }}</div>
            <div style="color:#64748b;font-size:12px;margin-top:2px;">Forma de Pago: {{ $factura->formapago }}</div>
            <div style="color:#64748b;font-size:12px;margin-top:2px;">N° Compro.: {{ $factura->n_compro }}</div>
        </div>
    </div>

    {{-- Detail Lines --}}
    <table class="detail">
        <thead>
            <tr>
                <th>#</th>
                <th>Descripción</th>
                <th>Cantidad</th>
                <th>Precio Unit.</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($detalle as $idx => $item)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ $item->nombre ?? ($item->nombre ?? ($item->nombre ?? 'Ítem')) }}</td>
                    <td>{{ $item->cantidad ?? ($item->cantidad ?? 1) }}</td>
                    <td style="text-align:right;">
                        {{ number_format($item->precio ?? ($item->precio_unit ?? 0), 0, ',', '.') }}
                    </td>
                    <td>{{ number_format($item->total ?? 0, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center;color:#94a3b8;padding:20px;">Sin detalle disponible.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Totals --}}
    <div class="totals">
        <div class="totals-box">
            <div class="totals-row">
                <span class="totals-label">Subtotal</span>
                <span>Gs. {{ number_format($detalle->sum('total'), 0, ',', '.') }}</span>
            </div>
            <div class="totals-row">
                <span>TOTAL</span>
                <span>Gs. {{ number_format($detalle->sum('total'), 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    {{-- CDC --}}
    @if ($factura->num_cdc)
        <div class="cdc-box">
            <div class="cdc-label">Código de Control (CDC)</div>
            <div class="cdc-value">{{ $factura->num_cdc }}</div>
        </div>
    @endif

    <div class="footer">
        Documento generado el {{ now()->format('d/m/Y H:i') }} — gFarmacia Sistema de Gestión
    </div>
</body>

</html>
