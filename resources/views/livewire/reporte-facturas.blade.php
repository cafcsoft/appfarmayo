<div class="invoice-container">
    {{-- Header Bar --}}
    <div class="bg-gray-900 border-b border-gray-800 px-6 py-5 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">
                Listado de <span class="text-indigo-400">Facturas</span>
            </h1>
            <p class="text-gray-400 text-sm mt-0.5">Consulta de comprobantes y documentos electrónicos</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:flex items-end gap-4">
            <div class="w-full sm:w-44">
                <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Desde Fecha</label>
                <input
                    type="date"
                    wire:model="fecha_ini"
                    class="invoice-control-input w-full"
                >
            </div>
            <div class="w-full sm:w-44">
                <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Hasta Fecha</label>
                <input
                    type="date"
                    wire:model="fecha_fin"
                    class="invoice-control-input w-full"
                >
            </div>
            <div class="flex gap-2 mt-2 sm:mt-0">
                <button
                    wire:click="$refresh"
                    class="btn-indigo whitespace-nowrap"
                >
                    Actualizar
                </button>
                <a
                    href="{{ route('dashboard') }}"
                    wire:navigate
                    class="bg-gray-700 hover:bg-gray-600 active:scale-95 transition-all text-white font-semibold px-5 py-2 rounded-lg text-sm whitespace-nowrap"
                >
                    Volver
                </a>
            </div>
        </div>
    </div>

    {{-- Summary Bar --}}
    <div class="px-6 py-4 border-b border-gray-800 bg-gray-900/60 flex flex-wrap items-center justify-between gap-3">
        <div class="text-sm text-gray-300">
            Mostrando Facturas de
            <span class="font-bold text-white">{{ \Carbon\Carbon::parse($fecha_ini)->format('d/m/Y') }}</span>
            a
            <span class="font-bold text-white">{{ \Carbon\Carbon::parse($fecha_fin)->format('d/m/Y') }}</span>
            &nbsp;&nbsp;Total Registros:
            <span class="font-bold text-indigo-400 text-base">{{ count($facturas) }}</span>
        </div>
        <div class="font-bold text-white text-sm">
            Total: <span class="text-green-400 text-base">{{ number_format($total_general, 0, ',', '.') }}</span>
        </div>
    </div>

    {{-- Table Controls --}}
    <div class="px-6 pt-4 pb-2 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2 text-sm text-gray-400">
            Mostrar
            <select wire:model.live="perPage" class="invoice-control-select">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
            registros
        </div>
        <div class="flex items-center gap-2 text-sm text-gray-400">
            <span>Buscar:</span>
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Filtrar registros..."
                class="invoice-control-input w-52"
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
                            <th class="px-2 py-2 text-left whitespace-nowrap">Tipo</th>
                            <th class="px-2 py-2 text-left whitespace-nowrap">Nro. De</th>
                            <th class="px-2 py-2 text-left whitespace-nowrap">Fecha</th>
                            <th class="px-2 py-2 text-right whitespace-nowrap">Total</th>
                            <th class="px-2 py-2 text-left whitespace-nowrap hidden sm:table-cell">RUC</th>
                            <th class="px-2 py-2 text-left whitespace-nowrap">Cliente</th>
                            <th class="px-2 py-2 text-left whitespace-nowrap hidden lg:table-cell">CDC</th>
                            <th class="px-2 py-2 text-left whitespace-nowrap hidden md:table-cell">Forma Pago</th>
                            <th class="px-2 py-2 text-center whitespace-nowrap">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800">
                        @forelse($paginatedFacturas as $factura)
                            <tr class="bg-gray-900 hover:bg-gray-800/70 transition-colors group">
                                {{-- TIPO badge --}}
                                <td class="px-2 py-1.5 whitespace-nowrap">
                                    @if($factura->tipofact === 'CONTADO')
                                        <span class="badge-fco">FCO</span>
                                    @elseif($factura->tipofact === 'CREDITO')
                                        <span class="badge-cre">CRE</span>
                                    @else
                                        <span class="badge-nc">NC</span>
                                    @endif
                                </td>
                                <td class="px-2 py-1.5 font-mono text-gray-200 whitespace-nowrap">{{ $factura->n_factura }}</td>
                                <td class="px-2 py-1.5 text-gray-300 whitespace-nowrap">
                                    {{ \Carbon\Carbon::parse($factura->fecha)->format('d/m/Y') }}
                                </td>
                                <td class="px-2 py-1.5 text-right font-semibold text-white whitespace-nowrap">
                                    {{ number_format($factura->total, 0, ',', '.') }}
                                </td>
                                <td class="px-2 py-1.5 text-gray-300 whitespace-nowrap hidden sm:table-cell font-mono">{{ $factura->ruccedula }}</td>
                                <td class="px-2 py-1.5 text-gray-200 max-w-[120px] sm:max-w-[150px] truncate" title="{{ $factura->nombclie }}">
                                    {{ Str::limit($factura->nombclie, 20) }}
                                </td>
                                <td class="px-2 py-1.5 font-mono text-gray-400 text-[10px] max-w-[100px] truncate hidden lg:table-cell" title="{{ $factura->num_cdc }}">
                                    {{ $factura->num_cdc ? Str::limit($factura->num_cdc, 15) : '—' }}
                                </td>
                                <td class="px-2 py-1.5 text-gray-300 whitespace-nowrap hidden md:table-cell">{{ $factura->formapago }}</td>
                                {{-- PDF Button --}}
                                <td class="px-2 py-1.5 text-center whitespace-nowrap flex items-center justify-center gap-1">
                                    @if($factura->num_cdc)
                                        <a
                                            href="{{ route('reporte.facturas.pdf', ['tipo' => strtolower($factura->tipofact), 'n_compro' => $factura->n_compro]) }}"
                                            target="_blank"
                                            title="Ver PDF Interno"
                                            class="btn-pdf"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17v3a1 1 0 001 1h16a1 1 0 001-1v-3M16 6l-4-4-4 4"/>
                                            </svg>
                                            PDF
                                        </a>
                                        <a
                                            href="{{ route('reporte.facturas.kude', ['cdc' => $factura->num_cdc]) }}"
                                            target="_blank"
                                            title="Descargar KUDE (API Externa)"
                                            class="btn-kude"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                                            </svg>
                                            KUDE
                                        </a>
                                    @else
                                        <span class="text-gray-600 text-[10px]">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-6a2 2 0 012-2h2a2 2 0 012 2v6m-6 0h6m-9 0H5a2 2 0 01-2-2V7a2 2 0 012-2h2M15 3h4a2 2 0 012 2v4"/>
                                        </svg>
                                        <span class="text-sm">No se encontraron facturas para el rango seleccionado.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination Info --}}
        @if(count($facturas) > 0)
        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm text-gray-400">
            <div>
                Mostrando {{ ($currentPage - 1) * $perPage + 1 }} a {{ min($currentPage * $perPage, count($facturas)) }}
                de {{ count($facturas) }} registros
            </div>
            <div class="flex items-center gap-1">
                <button
                    wire:click="previousPage"
                    @if($currentPage <= 1) disabled @endif
                    class="px-3 py-1.5 rounded bg-gray-800 border border-gray-700 hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed transition"
                >‹</button>
                @foreach(range(max(1, $currentPage - 2), min($totalPages, $currentPage + 2)) as $page)
                    <button
                        wire:click="goToPage({{ $page }})"
                        class="px-3 py-1.5 rounded border transition {{ $page === $currentPage ? 'bg-indigo-600 border-indigo-500 text-white font-bold' : 'bg-gray-800 border-gray-700 hover:bg-gray-700' }}"
                    >{{ $page }}</button>
                @endforeach
                <button
                    wire:click="nextPage"
                    @if($currentPage >= $totalPages) disabled @endif
                    class="px-3 py-1.5 rounded bg-gray-800 border border-gray-700 hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed transition"
                >›</button>
            </div>
        </div>
        @endif
    </div>
</div>
