<div class="invoice-container min-h-screen">

    {{-- ── HEADER ──────────────────────────────────────────────────────────── --}}
    <div class="bg-gray-900 border-b border-gray-800 px-6 py-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold">
                    Libro de Ventas <span class="text-indigo-400">IVA</span>
                </h1>
                <p class="text-gray-400 text-sm mt-0.5">
                    Comprobantes electrónicos del período — datos extraídos de la API de facturación electrónica (SET).
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span
                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                    Módulo Contabilidad
                </span>
            </div>
        </div>
    </div>

    {{-- ── FILTROS ─────────────────────────────────────────────────────────── --}}
    <div class="px-6 py-4 border-b border-gray-800 bg-gray-900/50">
        <div class="flex flex-wrap items-end gap-4">

            <div>
                <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Desde
                    Fecha</label>
                <input type="date" wire:model="fecha_ini" class="invoice-control-input w-44" />
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Hasta
                    Fecha</label>
                <input type="date" wire:model="fecha_fin" class="invoice-control-input w-44" />
            </div>

            {{-- Accesos rápidos a mes / semana --}}
            <div class="flex gap-2 flex-wrap">
                <button type="button"
                    wire:click="$set('fecha_ini', '{{ \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d') }}')"
                    class="text-xs px-3 py-1.5 rounded-lg bg-zinc-800 hover:bg-zinc-700 text-zinc-300 border border-zinc-700 transition">
                    Este mes
                </button>
                <button type="button"
                    wire:click="$set('fecha_ini', '{{ \Carbon\Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d') }}')"
                    wire:click.once
                    onclick="this.closest('[wire\\:id]').__livewire.$call('$set', 'fecha_ini', '{{ \Carbon\Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d') }}'); this.closest('[wire\\:id]').__livewire.$call('$set', 'fecha_fin', '{{ \Carbon\Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d') }}');"
                    class="text-xs px-3 py-1.5 rounded-lg bg-zinc-800 hover:bg-zinc-700 text-zinc-300 border border-zinc-700 transition">
                    Mes anterior
                </button>
            </div>

            <button type="button" wire:click="generarLibro" class="btn-indigo flex items-center gap-2 whitespace-nowrap"
                wire:loading.attr="disabled" wire:target="generarLibro">
                <svg wire:loading.remove wire:target="generarLibro" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                    stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                <svg wire:loading wire:target="generarLibro" class="w-4 h-4 animate-spin" fill="none"
                    viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span wire:loading.remove wire:target="generarLibro">Generar Libro</span>
                <span wire:loading wire:target="generarLibro">Consultando API…</span>
            </button>

            @if ($generado && count($libroRows) > 0)
            <div class="flex-1 min-w-[200px] md:max-w-xs ml-auto">
                <flux:input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Factura, cliente, RUC o CDC..."
                    icon="magnifying-glass"
                    label="Buscar"
                />
            </div>
            @endif
        </div>
    </div>

    {{-- ── LOADING OVERLAY ─────────────────────────────────────────────────── --}}
    <div wire:loading wire:target="generarLibro"
        class="mx-6 mt-4 p-5 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center gap-4">
        <svg class="w-6 h-6 text-indigo-400 animate-spin shrink-0" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
        <div>
            <p class="text-indigo-300 font-semibold">Procesando comprobantes…</p>
            <p class="text-indigo-400/70 text-sm mt-0.5">
                Consultando la API de facturación electrónica (SET). La primera generación puede tardar algunos segundos
                mientras se construye el caché local.
            </p>
        </div>
    </div>

    {{-- ── ERROR ───────────────────────────────────────────────────────────── --}}
    @if ($errorMsg)
    <div class="mx-6 mt-4 p-4 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
        {{ $errorMsg }}
    </div>
    @endif

    {{-- ── RESUMEN TOTALES ─────────────────────────────────────────────────── --}}
    @if ($generado && count($libroRows) > 0)
    <div class="px-6 pt-5">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">

            <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-4 text-center">
                <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Total General</p>
                <p class="text-base font-bold text-white">{{ $this->fmt($totalGeneral) }}</p>
                <p class="text-xs text-gray-500 mt-0.5">Gs.</p>
            </div>

            <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-4 text-center">
                <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Gravadas 10%</p>
                <p class="text-base font-bold text-amber-400">{{ $this->fmt($totalGrav10) }}</p>
                <p class="text-xs text-gray-500 mt-0.5">Gs.</p>
            </div>

            <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-4 text-center">
                <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Gravadas 5%</p>
                <p class="text-base font-bold text-orange-400">{{ $this->fmt($totalGrav05) }}</p>
                <p class="text-xs text-gray-500 mt-0.5">Gs.</p>
            </div>

            <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-4 text-center">
                <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">IVA 10%</p>
                <p class="text-base font-bold text-indigo-400">{{ $this->fmt($totalIva10) }}</p>
                <p class="text-xs text-gray-500 mt-0.5">Gs.</p>
            </div>

            <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-4 text-center">
                <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">IVA 5%</p>
                <p class="text-base font-bold text-purple-400">{{ $this->fmt($totalIva05) }}</p>
                <p class="text-xs text-gray-500 mt-0.5">Gs.</p>
            </div>

            <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-4 text-center">
                <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Exentas</p>
                <p class="text-base font-bold text-green-400">{{ $this->fmt($totalExentas) }}</p>
                <p class="text-xs text-gray-500 mt-0.5">Gs.</p>
            </div>

        </div>

        <div class="flex items-center justify-between mt-3 mb-2 flex-wrap gap-3">
            <p class="text-sm text-gray-400">
                <span class="font-semibold text-white">{{ $totalRegistros }}</span> comprobantes en el período
                <span class="text-gray-600 mx-2">|</span>
                <span class="font-semibold text-gray-300">{{ \Carbon\Carbon::parse($fecha_ini)->format('d/m/Y')
                    }}</span>
                al
                <span class="font-semibold text-gray-300">{{ \Carbon\Carbon::parse($fecha_fin)->format('d/m/Y')
                    }}</span>
            </p>

            {{-- Botones de exportación --}}
            <div class="flex items-center gap-2">
                <button type="button" wire:click="exportExcel" wire:loading.attr="disabled" wire:target="exportExcel"
                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-semibold
                               bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-400 border border-emerald-600/30
                               transition disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg wire:loading.remove wire:target="exportExcel" class="w-3.5 h-3.5" fill="none"
                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    <svg wire:loading wire:target="exportExcel" class="w-3.5 h-3.5 animate-spin" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z">
                        </path>
                    </svg>
                    Exportar Excel
                </button>

                <button type="button" wire:click="exportPdf" wire:loading.attr="disabled" wire:target="exportPdf" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-semibold
                               bg-red-600/20 hover:bg-red-600/30 text-red-400 border border-red-600/30
                               transition disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg wire:loading.remove wire:target="exportPdf" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"
                        stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                    <svg wire:loading wire:target="exportPdf" class="w-3.5 h-3.5 animate-spin" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z">
                        </path>
                    </svg>
                    Exportar PDF
                </button>
            </div>
        </div>
    </div>
    @endif


    {{-- ── TABLA DEL LIBRO ─────────────────────────────────────────────────── --}}
    @if ($generado)
    <div class="px-6 pb-8 mt-3">
        <div class="invoice-card">
            <div class="overflow-x-auto">
                <table class="invoice-table text-xs">
                    <thead>
                        <tr>
                            <th class="px-2 py-3 text-left whitespace-nowrap">Numebolo</th>
                            <th class="px-2 py-3 text-left whitespace-nowrap">Fechahora</th>
                            <th class="px-2 py-3 text-left whitespace-nowrap">Fecha</th>
                            <th class="px-2 py-3 text-right whitespace-nowrap">Total</th>
                            <th class="px-2 py-3 text-right whitespace-nowrap">Gravadas10</th>
                            <th class="px-2 py-3 text-right whitespace-nowrap">Gravadas05</th>
                            <th class="px-2 py-3 text-right whitespace-nowrap">Iva10</th>
                            <th class="px-2 py-3 text-right whitespace-nowrap">Iva05</th>
                            <th class="px-2 py-3 text-right whitespace-nowrap">Exentas</th>
                            <th class="px-2 py-3 text-center whitespace-nowrap">Tipoboleta</th>
                            <th class="px-2 py-3 text-left whitespace-nowrap">Ruc</th>
                            <th class="px-2 py-3 text-left whitespace-nowrap">Ruc_sin_dv</th>
                            <th class="px-2 py-3 text-center whitespace-nowrap">Dv</th>
                            <th class="px-2 py-3 text-left whitespace-nowrap">Cliente</th>
                            <th class="px-2 py-3 text-left whitespace-nowrap">numcdc</th>
                            <th class="px-2 py-3 text-center whitespace-nowrap">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800">
                        @forelse ($paginatedRows as $row)
                        <tr class="hover:bg-gray-800/60 transition-colors
                                    {{ $row['anulado'] ? 'bg-zinc-950/80 text-zinc-500 opacity-60' : ($row['tipoboleta'] === 'NOTACRED' ? 'bg-red-950/20' : 'bg-gray-900') }}">

                            {{-- Numebolo --}}
                            <td class="px-2 py-2 font-mono font-semibold text-white whitespace-nowrap">
                                {{ $row['numebolo'] }}
                            </td>

                            {{-- Fechahora --}}
                            <td class="px-2 py-2 text-gray-400 whitespace-nowrap">
                                {{ $row['fechahora'] ? \Carbon\Carbon::parse($row['fechahora'])->format('d/m/Y H:i') :
                                '-' }}
                            </td>

                            {{-- Fecha --}}
                            <td class="px-2 py-2 text-gray-300 whitespace-nowrap">
                                {{ $row['fecha'] ? \Carbon\Carbon::parse($row['fecha'])->format('d/m/Y') : '-' }}
                            </td>

                            {{-- Total --}}
                            <td class="px-2 py-2 text-right font-semibold text-white whitespace-nowrap">
                                {{ number_format($row['total'], 0, ',', '.') }}
                            </td>

                            {{-- Gravadas 10% --}}
                            <td class="px-2 py-2 text-right text-amber-400 whitespace-nowrap">
                                {{ $row['gravadas10'] != 0 ? number_format($row['gravadas10'], 2, ',', '.') : '0.00' }}
                            </td>

                            {{-- Gravadas 5% --}}
                            <td class="px-2 py-2 text-right text-orange-400 whitespace-nowrap">
                                {{ $row['gravadas05'] != 0 ? number_format($row['gravadas05'], 2, ',', '.') : '0.00' }}
                            </td>

                            {{-- IVA 10% --}}
                            <td class="px-2 py-2 text-right text-indigo-400 whitespace-nowrap">
                                {{ $row['iva10'] != 0 ? number_format($row['iva10'], 2, ',', '.') : '0.00' }}
                            </td>

                            {{-- IVA 5% --}}
                            <td class="px-2 py-2 text-right text-purple-400 whitespace-nowrap">
                                {{ $row['iva05'] != 0 ? number_format($row['iva05'], 2, ',', '.') : '0.00' }}
                            </td>

                            {{-- Exentas --}}
                            <td class="px-2 py-2 text-right text-green-400 whitespace-nowrap">
                                {{ $row['exentas'] != 0 ? number_format($row['exentas'], 2, ',', '.') : '0.00' }}
                            </td>

                            {{-- Tipoboleta --}}
                            <td class="px-2 py-2 text-center whitespace-nowrap">
                                @if ($row['anulado'])
                                <span
                                    class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold bg-gray-500/10 text-gray-400 border border-gray-500/20">
                                    ANULADO
                                </span>
                                @elseif ($row['tipoboleta'] === 'NOTACRED')
                                <span
                                    class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold bg-red-500/10 text-red-400 border border-red-500/20">
                                    NOTACRED
                                </span>
                                @elseif ($row['tipoboleta'] === 'CREDITO')
                                <span
                                    class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold bg-blue-500/10 text-blue-400 border border-blue-500/20">
                                    CREDITO
                                </span>
                                @else
                                <span
                                    class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                    CONTADO
                                </span>
                                @endif
                            </td>

                            {{-- Ruc (con DV) --}}
                            <td class="px-2 py-2 font-mono text-gray-200 whitespace-nowrap">
                                {{ $row['ruc'] }}
                            </td>

                            {{-- Ruc sin DV --}}
                            <td class="px-2 py-2 font-mono text-gray-400 whitespace-nowrap">
                                {{ $row['ruc_sin_dv'] }}
                            </td>

                            {{-- DV --}}
                            <td class="px-2 py-2 text-center font-mono text-gray-400 whitespace-nowrap">
                                {{ $row['dv'] }}
                            </td>

                            {{-- Cliente --}}
                            <td class="px-2 py-2 text-gray-200 max-w-xs truncate">
                                {{ $row['cliente'] }}
                            </td>

                            {{-- numcdc --}}
                            <td class="px-2 py-2 font-mono text-indigo-300/70 text-xs whitespace-nowrap max-w-xs truncate"
                                title="{{ $row['numcdc'] }}">
                                {{ $row['numcdc'] }}
                            </td>

                            {{-- Acciones (PDF & KUDE) --}}
                            <td class="px-2 py-1.5 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1.5">
                                    @if ($row['numcdc'])
                                        <a href="{{ route('reporte.facturas.pdf', ['tipo' => strtolower($row['tipoboleta']), 'n_compro' => $row['n_compro']]) }}"
                                            target="_blank"
                                            title="Ver PDF Interno"
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded bg-indigo-600/10 hover:bg-indigo-600/20 text-indigo-400 border border-indigo-500/20 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17v3a1 1 0 001 1h16a1 1 0 001-1v-3M16 6l-4-4-4 4"/>
                                            </svg>
                                            <span>PDF</span>
                                        </a>
                                        <a href="{{ route('reporte.facturas.kude', ['cdc' => $row['numcdc']]) }}"
                                            target="_blank"
                                            title="Descargar KUDE (API Externa)"
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded bg-amber-600/10 hover:bg-amber-600/20 text-amber-400 border border-amber-500/20 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                                            </svg>
                                            <span>KUDE</span>
                                        </a>
                                    @else
                                        <span class="text-zinc-600">—</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="16" class="px-4 py-10 text-center text-gray-500 italic">
                                No se encontraron comprobantes electrónicos con CDC en el período seleccionado.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>

                    {{-- ── FILA DE TOTALES ──────────────────────────────── --}}
                    @if ($totalRegistros > 0)
                    <tfoot>
                        <tr class="bg-zinc-800/80 font-bold text-white border-t-2 border-zinc-600">
                            <td class="px-2 py-3" colspan="3">
                                TOTALES — {{ $totalRegistros }} registros
                            </td>
                            <td class="px-2 py-3 text-right">
                                {{ number_format($totalGeneral, 0, ',', '.') }}
                            </td>
                            <td class="px-2 py-3 text-right text-amber-400">
                                {{ number_format($totalGrav10, 2, ',', '.') }}
                            </td>
                            <td class="px-2 py-3 text-right text-orange-400">
                                {{ number_format($totalGrav05, 2, ',', '.') }}
                            </td>
                            <td class="px-2 py-3 text-right text-indigo-400">
                                {{ number_format($totalIva10, 2, ',', '.') }}
                            </td>
                            <td class="px-2 py-3 text-right text-purple-400">
                                {{ number_format($totalIva05, 2, ',', '.') }}
                            </td>
                            <td class="px-2 py-3 text-right text-green-400">
                                {{ number_format($totalExentas, 2, ',', '.') }}
                            </td>
                            <td colspan="7"></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>

        {{-- ── PAGINACIÓN ───────────────────────────────────────────────── --}}
        @if ($totalRegistros > 0)
        <div class="mt-4 flex flex-wrap items-center justify-between gap-4">

            {{-- Info + selector perPage --}}
            <div class="flex items-center gap-3 text-sm text-gray-400">
                <span>
                    Mostrando
                    <span class="text-white font-semibold">{{ (($currentPage - 1) * $perPage) + 1 }}</span>
                    –
                    <span class="text-white font-semibold">{{ min($currentPage * $perPage, $totalRegistros) }}</span>
                    de
                    <span class="text-white font-semibold">{{ $totalRegistros }}</span>
                    registros
                </span>
                <span class="text-gray-600">|</span>
                <label class="flex items-center gap-2">
                    <span>Filas:</span>
                    <select wire:model.live="perPage"
                        class="bg-zinc-800 border border-zinc-700 text-white text-xs rounded-lg px-2 py-1 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="200">200</option>
                    </select>
                </label>
            </div>

            {{-- Controles de página --}}
            <div class="flex items-center gap-1.5">
                {{-- Primera página --}}
                <button type="button" wire:click="goToPage(1)" @disabled($currentPage <=1)
                    class="px-2.5 py-1.5 rounded-lg text-xs font-medium transition
                                {{ $currentPage <= 1 ? 'bg-zinc-800/40 text-zinc-600 cursor-not-allowed' : 'bg-zinc-800 text-zinc-300 hover:bg-zinc-700 border border-zinc-700' }}">
                    «
                </button>

                {{-- Anterior --}}
                <button type="button" wire:click="previousPage" @disabled($currentPage <=1)
                    class="px-3 py-1.5 rounded-lg text-xs font-medium transition
                                {{ $currentPage <= 1 ? 'bg-zinc-800/40 text-zinc-600 cursor-not-allowed' : 'bg-zinc-800 text-zinc-300 hover:bg-zinc-700 border border-zinc-700' }}">
                    ‹ Anterior
                </button>

                {{-- Números de página (máx 7 botones) --}}
                @php
                $range = 3;
                $start = max(1, $currentPage - $range);
                $end = min($totalPages, $currentPage + $range);
                @endphp

                @if ($start > 1)
                <span class="px-2 text-zinc-600">…</span>
                @endif

                @for ($p = $start; $p <= $end; $p++) <button type="button" wire:click="goToPage({{ $p }})" class="px-3 py-1.5 rounded-lg text-xs font-medium transition border
                                    {{ $p === $currentPage
                                        ? 'bg-indigo-600 border-indigo-500 text-white shadow-lg shadow-indigo-500/20'
                                        : 'bg-zinc-800 border-zinc-700 text-zinc-300 hover:bg-zinc-700' }}">
                    {{ $p }}
                    </button>
                    @endfor

                    @if ($end < $totalPages) <span class="px-2 text-zinc-600">…</span>
                        @endif

                        {{-- Siguiente --}}
                        <button type="button" wire:click="nextPage" @disabled($currentPage>= $totalPages)
                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition
                            {{ $currentPage >= $totalPages ? 'bg-zinc-800/40 text-zinc-600 cursor-not-allowed' :
                            'bg-zinc-800 text-zinc-300 hover:bg-zinc-700 border border-zinc-700' }}">
                            Siguiente ›
                        </button>

                        {{-- Última página --}}
                        <button type="button" wire:click="goToPage({{ $totalPages }})" @disabled($currentPage>=
                            $totalPages)
                            class="px-2.5 py-1.5 rounded-lg text-xs font-medium transition
                            {{ $currentPage >= $totalPages ? 'bg-zinc-800/40 text-zinc-600 cursor-not-allowed' :
                            'bg-zinc-800 text-zinc-300 hover:bg-zinc-700 border border-zinc-700' }}">
                            »
                        </button>
            </div>
        </div>
        @endif

    </div>

    @elseif (!$generado)
    {{-- Estado inicial: invitación a generar --}}
    <div class="flex flex-col items-center justify-center py-24 text-center px-6">
        <div class="w-20 h-20 rounded-2xl bg-indigo-500/10 flex items-center justify-center mb-6">
            <flux:icon name="calculator" class="w-10 h-10 text-indigo-400" />
        </div>
        <h3 class="text-xl font-bold text-white mb-2">Libro de Ventas IVA</h3>
        <p class="text-gray-400 max-w-sm">
            Selecciona el rango de fechas y presiona <strong class="text-white">Generar Libro</strong>.
            Se consultará la API de facturación electrónica (SET) para obtener los datos fiscales de cada comprobante.
        </p>
        <!-- <p class="text-gray-500 text-sm mt-4">
                Los datos se almacenan en caché local para agilizar consultas futuras.
            </p> -->
    </div>
    @endif

</div>