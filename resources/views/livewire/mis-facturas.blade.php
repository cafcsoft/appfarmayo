<div class="invoice-container">
    {{-- Header Bar --}}
    <div class="bg-gray-900 border-b border-gray-800 px-6 py-5 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">
                Mis <span class="text-indigo-400">Documentos</span>
            </h1>
            <p class="text-gray-400 text-sm mt-0.5">Consulta de tus facturas y comprobantes electrónicos</p>
        </div>

        <div class="flex flex-wrap items-center gap-4">
            <div>
                <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Desde Fecha</label>
                <input
                    type="date"
                    wire:model="fecha_ini"
                    class="invoice-control-input w-44"
                >
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Hasta Fecha</label>
                <input
                    type="date"
                    wire:model="fecha_fin"
                    class="invoice-control-input w-44"
                >
            </div>
            <div class="flex gap-2 mt-4">
                <button
                    wire:click="$refresh"
                    class="btn-indigo"
                >
                    Actualizar
                </button>
            </div>
        </div>
    </div>

    {{-- Summary Bar --}}
    <div class="px-6 py-4 border-b border-gray-800 bg-gray-900/60 flex flex-wrap items-center justify-between gap-3 text-xs">
        <div class="text-gray-300 uppercase tracking-widest">
            RUC/Cédula: <span class="font-bold text-white">{{ auth()->user()->ruc_cedula }}</span>
            &nbsp;&nbsp;|&nbsp;&nbsp;
            Total Registros: <span class="font-bold text-indigo-400 text-sm">{{ count($facturas) }}</span>
        </div>
        <div class="font-bold text-white">
            Total Acumulado: <span class="text-green-400 text-sm">{{ number_format($total_general, 0, ',', '.') }} Gs.</span>
        </div>
    </div>

    {{-- Table Controls --}}
    <div class="px-6 pt-4 pb-2 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2 text-xs text-gray-400">
            Mostrar
            <select wire:model.live="perPage" class="invoice-control-select">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </select>
        </div>
        <div class="flex items-center gap-2 text-xs text-gray-400">
            <span>Filtrar:</span>
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Nro Factura..."
                class="invoice-control-input w-44"
            >
        </div>
    </div>

    {{-- Table --}}
    <div class="px-6 pb-6">
        <div class="invoice-card">
            <div class="overflow-x-auto">
                <table class="invoice-table">
                    <thead>
                        <tr>
                            <th class="px-3 py-3 text-left whitespace-nowrap">Tipo</th>
                            <th class="px-3 py-3 text-left whitespace-nowrap">Nro. Comprobante</th>
                            <th class="px-3 py-3 text-left whitespace-nowrap">Fecha</th>
                            <th class="px-3 py-3 text-right whitespace-nowrap">Total</th>
                            <th class="px-3 py-3 text-left whitespace-nowrap">CDC</th>
                            <th class="px-3 py-3 text-center whitespace-nowrap">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800">
                        @forelse($paginatedFacturas as $factura)
                            <tr class="bg-gray-900 hover:bg-gray-800/70 transition-colors group">
                                <td class="px-3 py-2 whitespace-nowrap">
                                    @if($factura->tipofact === 'CONTADO')
                                        <span class="badge-fco">CONTADO</span>
                                    @elseif($factura->tipofact === 'CREDITO')
                                        <span class="badge-cre">CRÉDITO</span>
                                    @else
                                        <span class="badge-nc">NOTA CRÉD.</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 font-mono text-gray-200 whitespace-nowrap">{{ $factura->n_factura }}</td>
                                <td class="px-3 py-2 text-gray-300 whitespace-nowrap">
                                    {{ \Carbon\Carbon::parse($factura->fecha)->format('d/m/Y') }}
                                </td>
                                <td class="px-3 py-2 text-right font-semibold text-white whitespace-nowrap">
                                    {{ number_format($factura->total, 0, ',', '.') }}
                                </td>
                                <td class="px-3 py-2 font-mono text-gray-400 text-[10px] max-w-[200px] truncate" title="{{ $factura->num_cdc }}">
                                    {{ $factura->num_cdc ? Str::limit($factura->num_cdc, 25) : '—' }}
                                </td>
                                <td class="px-3 py-2 text-center whitespace-nowrap flex items-center justify-center gap-1">
                                    @if($factura->num_cdc)
                                        <a
                                            href="{{ route('reporte.facturas.pdf', ['tipo' => strtolower($factura->tipofact), 'n_compro' => $factura->n_compro]) }}"
                                            target="_blank"
                                            class="btn-pdf"
                                        >
                                            PDF
                                        </a>
                                        <a
                                            href="{{ route('reporte.facturas.kude', ['cdc' => $factura->num_cdc]) }}"
                                            target="_blank"
                                            class="btn-kude"
                                        >
                                            KUDE
                                        </a>
                                    @else
                                        <span class="text-gray-600 text-[10px]">Sin CDC</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <span class="text-sm">No tienes facturas registradas para este periodo.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination info --}}
        @if(count($facturas) > 0)
        <div class="mt-4 flex items-center justify-between text-[11px] text-gray-400">
            <div>
                Página {{ $currentPage }} de {{ $totalPages }}
            </div>
            <div class="flex gap-1">
                <button wire:click="previousPage" @if($currentPage <= 1) disabled @endif class="px-2 py-1 rounded bg-gray-800 disabled:opacity-40">Anterior</button>
                <button wire:click="nextPage" @if($currentPage >= $totalPages) disabled @endif class="px-2 py-1 rounded bg-gray-800 disabled:opacity-40">Siguiente</button>
            </div>
        </div>
        @endif
    </div>
</div>
