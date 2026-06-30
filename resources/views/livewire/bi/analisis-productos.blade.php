<div class="bi-dashboard flex flex-col gap-6 p-6" x-data="{ tabActiva: @entangle('tabActiva') }">
        
        {{-- Header --}}
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <flux:heading size="xl" level="1">🏷️ Análisis de Productos</flux:heading>
                <flux:subheading>Rankings, ABC, márgenes y tendencias por SKU</flux:subheading>
            </div>
            
            <div class="flex items-center gap-3">
                <flux:button wire:click="$refresh" icon="arrow-path" variant="ghost" />
            </div>
        </div>

        {{-- Filters Section --}}
        <flux:card class="p-4">
            <div class="flex flex-col lg:flex-row gap-6 items-end">
                <!-- Pair 1: Dates -->
                <div class="flex-1 flex gap-4 w-full">
                    <div class="flex-1">
                        <label class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Desde</label>
                        <input type="date" wire:model.live="fechaDesde" class="w-full rounded-lg border border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-sm px-3 py-2 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 outline-none shadow-sm" />
                    </div>
                    <div class="flex-1">
                        <label class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Hasta</label>
                        <input type="date" wire:model.live="fechaHasta" class="w-full rounded-lg border border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-sm px-3 py-2 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 outline-none shadow-sm" />
                    </div>
                </div>

                <!-- Pair 2: Limit & Export -->
                <div class="flex-1 flex gap-4 w-full">
                    <div class="flex-1">
                        <label class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Límite</label>
                        <select wire:model.live="limite" class="w-full rounded-lg border border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-sm px-3 py-2 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 outline-none shadow-sm appearance-none">
                            <option value="10">Top 10</option>
                            <option value="20">Top 20</option>
                            <option value="50">Top 50</option>
                            <option value="100">Top 100</option>
                        </select>
                    </div>
                    <button wire:click="exportarReporte" class="flex-1 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded-lg py-2 text-[10px] font-bold uppercase tracking-widest text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-zinc-800 transition-colors shadow-sm flex items-center justify-center gap-2">
                        <flux:icon name="arrow-down-tray" class="size-4" />
                        Exportar
                    </button>
                </div>
            </div>
        </flux:card>

        {{-- Manual Tabs --}}
        <div class="flex flex-wrap gap-2 p-1 bg-gray-100 dark:bg-zinc-800 rounded-xl w-fit">
            @foreach(['top-ventas' => 'Top Ventas', 'ranking-abc' => 'Ranking ABC', 'margenes' => 'Márgenes', 'tendencias' => 'Tendencias', 'oportunidades' => 'Oportunidades'] as $key => $label)
                <button 
                    wire:click="setTab('{{ $key }}')"
                    class="px-4 py-2 rounded-lg text-sm font-semibold transition-all {{ $tabActiva === $key ? 'bg-white dark:bg-zinc-700 shadow-sm text-indigo-600 dark:text-indigo-400' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}"
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- Main Data Table (Paginated) --}}
        <flux:card class="p-0 overflow-hidden mt-4">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs font-semibold text-gray-500 uppercase bg-gray-50/50 dark:bg-zinc-800/50">
                        <tr>
                            <th class="px-6 py-3">#</th>
                            <th class="px-6 py-3">Producto</th>
                            @if($tabActiva === 'top-ventas')
                                <th class="px-6 py-3 text-right">Facturación</th>
                                <th class="px-6 py-3 text-right">Cant.</th>
                                <th class="px-6 py-3 text-right">Margen</th>
                            @elseif($tabActiva === 'ranking-abc')
                                <th class="px-6 py-3 text-right">Venta</th>
                                <th class="px-6 py-3 text-center">Clase</th>
                                <th class="px-6 py-3 text-right">% Acum.</th>
                            @elseif($tabActiva === 'margenes')
                                <th class="px-6 py-3 text-right">Margen</th>
                                <th class="px-6 py-3 text-right">Venta</th>
                                <th class="px-6 py-3 text-right">Pct.</th>
                            @elseif($tabActiva === 'tendencias')
                                <th class="px-6 py-3 text-right">Venta P1</th>
                                <th class="px-6 py-3 text-right">Venta P2</th>
                                <th class="px-6 py-3 text-right">Var.</th>
                            @elseif($tabActiva === 'oportunidades')
                                <th class="px-6 py-3 text-right">Venta</th>
                                <th class="px-6 py-3 text-right">Margen %</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                        @foreach ($paginatedItems as $i => $p)
                            @php $p = is_array($p) ? (object) $p : $p; @endphp
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                                <td class="px-6 py-4 text-gray-400 font-mono text-xs">{{ $paginatedItems->firstItem() + $i }}</td>
                                <td class="px-6 py-4 font-semibold text-xs truncate max-w-xs">{{ $p->nombre ?? '—' }}</td>
                                
                                @if($tabActiva === 'top-ventas')
                                    <td class="px-6 py-4 text-right font-mono text-indigo-600 dark:text-indigo-400">Gs. {{ number_format($p->venta_total, 0, ',', '.') }}</td>
                                    <td class="px-6 py-4 text-right font-mono text-gray-500">{{ number_format($p->total_unidades ?? 0, 0) }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <flux:badge size="sm" color="{{ ($p->pct_margen ?? 0) >= 20 ? 'green' : 'yellow' }}">{{ round($p->pct_margen ?? 0, 1) }}%</flux:badge>
                                    </td>
                                @elseif($tabActiva === 'ranking-abc')
                                    <td class="px-6 py-4 text-right font-mono">Gs. {{ number_format($p->venta_total, 0, ',', '.') }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <flux:badge size="sm" color="{{ $p->clase === 'A' ? 'indigo' : ($p->clase === 'B' ? 'emerald' : 'zinc') }}">{{ $p->clase }}</flux:badge>
                                    </td>
                                    <td class="px-6 py-4 text-right text-xs text-gray-400">{{ round($p->pct_acumulado, 1) }}%</td>
                                @elseif($tabActiva === 'margenes')
                                    <td class="px-6 py-4 text-right font-mono {{ $p->margen_total < 0 ? 'text-red-500' : '' }}">Gs. {{ number_format($p->margen_total, 0, ',', '.') }}</td>
                                    <td class="px-6 py-4 text-right font-mono text-gray-400">Gs. {{ number_format($p->venta_total, 0, ',', '.') }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <flux:badge size="sm" color="{{ $p->pct_margen < 0 ? 'red' : 'green' }}">{{ round($p->pct_margen, 1) }}%</flux:badge>
                                    </td>
                                @elseif($tabActiva === 'tendencias')
                                    <td class="px-6 py-4 text-right font-mono text-gray-400">Gs. {{ number_format($p->venta_p1, 0, ',', '.') }}</td>
                                    <td class="px-6 py-4 text-right font-mono text-indigo-600 dark:text-indigo-400">Gs. {{ number_format($p->venta_p2, 0, ',', '.') }}</td>
                                    <td class="px-6 py-4 text-right">
                                        @php $var = $p->crecimiento ?? ($p->caida ?? 0); @endphp
                                        <span class="font-bold {{ $var >= 0 ? 'text-green-500' : 'text-red-500' }}">
                                            {{ $var >= 0 ? '↑' : '↓' }} {{ abs($var) }}%
                                        </span>
                                    </td>
                                @elseif($tabActiva === 'oportunidades')
                                    <td class="px-6 py-4 text-right font-mono">Gs. {{ number_format($p->venta_total, 0, ',', '.') }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <flux:badge size="sm" color="{{ $p->pct_margen < 20 ? 'red' : 'green' }}">{{ round($p->pct_margen, 1) }}%</flux:badge>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-gray-100 dark:border-zinc-800">
                {{ $paginatedItems->links() }}
            </div>
        </flux:card>
</div>

