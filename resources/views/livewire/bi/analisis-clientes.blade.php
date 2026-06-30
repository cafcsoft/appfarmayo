<div class="bi-dashboard flex flex-col gap-6 p-6" x-data="{ tabActiva: 'segmentacion' }">
        
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <flux:heading size="xl" level="1">👥 Análisis de Fidelidad de Clientes</flux:heading>
                <flux:subheading>Segmentación RFM, retención y comportamiento de compra</flux:subheading>
            </div>
            
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2">
                    <label class="text-xs font-semibold text-gray-500 uppercase">Desde:</label>
                    <flux:input type="date" wire:model.live="fechaDesde" size="sm" />
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-xs font-semibold text-gray-500 uppercase">Hasta:</label>
                    <flux:input type="date" wire:model.live="fechaHasta" size="sm" />
                </div>
                <flux:button wire:click="$refresh" icon="arrow-path" variant="outline" size="sm">Actualizar</flux:button>
            </div>
        </div>

        {{-- KPI Row --}}
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <flux:card class="p-5">
                <p class="text-xs font-semibold text-gray-500 uppercase">Total Cartera</p>
                <h3 class="text-2xl font-bold">{{ number_format(count($clientes), 0) }}</h3>
                <p class="text-xs text-gray-400 mt-1">Clientes únicos históricos</p>
            </flux:card>
            <flux:card class="p-5">
                <p class="text-xs font-semibold text-emerald-600 uppercase">Fieles / Recurrentes</p>
                <h3 class="text-2xl font-bold">{{ count($clientes->whereIn('segmento', ['Fiel', 'Recurrente'])) }}</h3>
                <p class="text-xs text-emerald-500 mt-1">Base sólida de ingresos</p>
            </flux:card>
            <flux:card class="p-5">
                <p class="text-xs font-semibold text-amber-600 uppercase">En Riesgo</p>
                <h3 class="text-2xl font-bold">{{ count($clientes->where('segmento', 'En Riesgo')) }}</h3>
                <p class="text-xs text-amber-500 mt-1">Sin compra en 60-120 días</p>
            </flux:card>
            <flux:card class="p-5">
                <p class="text-xs font-semibold text-rose-600 uppercase">Perdidos</p>
                <h3 class="text-2xl font-bold">{{ count($clientes->where('segmento', 'Perdido')) }}</h3>
                <p class="text-xs text-rose-500 mt-1">Inactivos > 120 días</p>
            </flux:card>
        </div>

        {{-- Manual Tabs --}}
        <div class="flex gap-2 p-1 bg-gray-100 dark:bg-zinc-800 rounded-xl w-fit">
            <button @click="tabActiva = 'segmentacion'" :class="tabActiva === 'segmentacion' ? 'bg-white dark:bg-zinc-700 shadow-sm text-indigo-600' : 'text-gray-500'" class="px-4 py-2 rounded-lg text-sm font-semibold">Segmentación</button>
            <button @click="tabActiva = 'ranking'" :class="tabActiva === 'ranking' ? 'bg-white dark:bg-zinc-700 shadow-sm text-indigo-600' : 'text-gray-500'" class="px-4 py-2 rounded-lg text-sm font-semibold">Top Gastadores</button>
        </div>

        <flux:card class="p-0 overflow-hidden mt-4">
            <div class="p-4 border-b border-gray-100 dark:border-zinc-800 bg-gray-50/30 dark:bg-zinc-800/20 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <flux:input wire:model.live="search" placeholder="Buscar cliente..." icon="magnifying-glass" size="sm" class="w-64" />
                    <select wire:model.live="filtroSegmento" class="rounded-lg border border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-sm px-3 py-1.5 text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 w-40">
                        <option value="">Todos los segmentos</option>
                        <option value="Fiel">Fiel</option>
                        <option value="Recurrente">Recurrente</option>
                        <option value="Activo">Activo</option>
                        <option value="En Riesgo">En Riesgo</option>
                        <option value="Perdido">Perdido</option>
                        <option value="Nuevo">Nuevo</option>
                        <option value="Ocasional">Ocasional</option>
                    </select>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs font-bold text-gray-400 uppercase bg-gray-50/50 dark:bg-zinc-800/50">
                        <tr>
                            <th class="px-6 py-4">ID Cliente</th>
                            <th class="px-6 py-4 flex items-center gap-2">
                                <flux:icon.user class="w-3 h-3" />
                                Nombre
                            </th>
                            <th class="px-6 py-4 text-right">Venta Total</th>
                            <th class="px-6 py-4 text-center">Última Compra</th>
                            <th class="px-6 py-4 text-center">Segmento</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                        @foreach ($clientesFiltrados as $cli)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                                <td class="px-6 py-4 font-mono text-xs text-gray-400">{{ $cli->id_cliente }}</td>
                                <td class="px-6 py-4 font-semibold">{{ $cli->nombre }}</td>
                                <td class="px-6 py-4 text-right font-mono text-indigo-600 dark:text-indigo-400">
                                    Gs. {{ number_format($cli->venta_total, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-center text-xs text-gray-500">
                                    {{ \Carbon\Carbon::parse($cli->ultima_compra)->format('d/m/Y') }}
                                    <span class="block text-[10px] text-gray-400">{{ $cli->dias_inactivo }} días inactivo</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <flux:badge size="sm" color="{{ match(strtolower(str_replace(' ', '-', $cli->segmento ?? ''))) {
                                        'fiel' => 'indigo',
                                        'activo' => 'green',
                                        'recurrente' => 'emerald',
                                        'en-riesgo' => 'yellow',
                                        'perdido' => 'red',
                                        default => 'zinc'
                                    } }}">
                                        {{ $cli->segmento }}
                                    </flux:badge>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-gray-100 dark:border-zinc-800">
                {{ $clientesFiltrados->links() }}
            </div>
        </flux:card>
</div>
