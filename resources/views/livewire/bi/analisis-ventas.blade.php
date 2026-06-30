<div class="bi-dashboard flex flex-col gap-6 p-6" x-data="biSales()">
        
        {{-- Header --}}
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <flux:heading size="xl" level="1">📈 Análisis de Ventas Consolidado</flux:heading>
                <flux:subheading>Villa Morra · Histórico de facturación y tickets</flux:subheading>
            </div>
            
            <div class="flex items-center gap-3">
                <flux:button wire:click="$refresh" icon="arrow-path" variant="outline" size="sm">Refrescar</flux:button>
            </div>
        </div>

        {{-- Filters Section --}}
        <flux:card class="p-4">
            <div class="flex flex-col gap-6">
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

                    <!-- Pair 2: Selects -->
                    <div class="flex-1 flex gap-4 w-full">
                        <div class="flex-1">
                            <label class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Origen</label>
                            <select wire:model.live="origen" class="w-full rounded-lg border border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-sm px-3 py-2 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 outline-none shadow-sm appearance-none">
                                <option value="todos">Todos los Orígenes</option>
                                <option value="factura">Sólo Facturas</option>
                                <option value="ticket">Sólo Tickets</option>
                            </select>
                        </div>
                        <div class="flex-1">
                            <label class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Turno</label>
                            <select wire:model.live="turno" class="w-full rounded-lg border border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-sm px-3 py-2 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 outline-none shadow-sm appearance-none">
                                <option value="">Cualquier Turno</option>
                                <option value="MAÑANA">Turno Mañana</option>
                                <option value="TARDE">Turno Tarde</option>
                                <option value="NOCHE">Turno Noche</option>
                            </select>
                        </div>
                    </div>
                </div>
                <flux:button wire:click="$refresh" icon="magnifying-glass" variant="outline" class="w-full py-3 font-bold uppercase tracking-widest text-xs">Filtrar Análisis Consolidado</flux:button>
            </div>
        </flux:card>

        {{-- Trends Section --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <flux:card class="lg:col-span-2">
                <flux:heading size="md" class="mb-4">Evolución de Facturación</flux:heading>
                <div class="relative w-full h-[350px]" wire:ignore>
                    <canvas id="chartEvolucion" x-init="initChartEvolucion($el, {{ $chartEvolucionJson }})" x-effect="updateChartEvolucion({{ $chartEvolucionJson }})"></canvas>
                </div>
            </flux:card>
            
            <flux:card>
                <flux:heading size="md" class="mb-4">Estacionalidad (Día Semana)</flux:heading>
                <div class="relative w-full h-[350px]" wire:ignore>
                    <canvas id="chartEstacionalidad" x-init="initChartEstacionalidad($el, {{ $chartEstacionalidadJson }})"></canvas>
                </div>
            </flux:card>
        </div>

        {{-- Payment Methods Section --}}
        <flux:card>
            <flux:heading size="md" class="mb-6">Ventas por Forma de Pago</flux:heading>
            <div class="relative w-full h-[400px]" wire:ignore>
                <canvas id="chartFormasPago" x-init="initChartFormasPago($el, {{ $chartFormasPagoJson }})" x-effect="updateChartFormasPago({{ $chartFormasPagoJson }})"></canvas>
            </div>
        </flux:card>

        {{-- Tables Section --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <flux:card class="p-0 overflow-hidden border-none shadow-lg">
                <div class="p-4 border-b border-gray-100 dark:border-zinc-800 bg-gradient-to-r from-indigo-50 to-transparent dark:from-indigo-900/20">
                    <div class="flex items-center gap-2">
                        <flux:icon.chart-bar class="text-indigo-600 dark:text-indigo-400" variant="solid" />
                        <flux:heading size="sm" class="!mb-0 font-bold">📈 Evolución Ticket Promedio (GS)</flux:heading>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs font-bold text-gray-400 uppercase bg-gray-50/50 dark:bg-zinc-800/50">
                            <tr>
                                <th class="px-6 py-4">Periodo</th>
                                <th class="px-6 py-4 text-right">Ticket Promedio</th>
                                <th class="px-6 py-4 text-right">Venta Total</th>
                                <th class="px-6 py-4 text-right">Transacciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                            @foreach ($ticketEvo as $evo)
                                @php $evo = is_array($evo) ? (object)$evo : $evo; @endphp
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-zinc-800/50 transition-colors">
                                    <td class="px-6 py-4 font-bold">{{ $evo->periodo }}</td>
                                    <td class="px-6 py-4 text-right font-mono text-indigo-600 dark:text-indigo-400">
                                        Gs. {{ number_format($evo->ticket_promedio ?? 0, 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-gray-500">
                                        Gs. {{ number_format($evo->venta_total ?? 0, 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-right font-mono">{{ number_format($evo->comprobantes ?? 0, 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t border-gray-100 dark:border-zinc-800">
                    {{ $ticketEvo->links() }}
                </div>
            </flux:card>

            <flux:card class="p-0 overflow-hidden border-none shadow-lg">
                <div class="p-4 border-b border-gray-100 dark:border-zinc-800 bg-gradient-to-r from-emerald-50 to-transparent dark:from-emerald-900/20">
                    <div class="flex items-center gap-2">
                        <flux:icon.building-storefront class="text-emerald-600 dark:text-emerald-400" variant="solid" />
                        <flux:heading size="sm" class="!mb-0 font-bold">🏪 Resumen por Origen</flux:heading>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs font-bold text-gray-400 uppercase bg-gray-50/50 dark:bg-zinc-800/50">
                            <tr>
                                <th class="px-6 py-4">Origen</th>
                                <th class="px-6 py-4 text-right">Venta Total</th>
                                <th class="px-6 py-4 text-right">Transacciones</th>
                                <th class="px-6 py-4 text-right">Ticket Avg</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                            @foreach ($porOrigen as $ori)
                                @php $ori = (object)$ori; @endphp
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-zinc-800/50 transition-colors">
                                    <td class="px-6 py-4 font-bold uppercase flex items-center gap-2">
                                        <div class="w-2 h-2 rounded-full {{ ($ori->origen ?? '') === 'factura' ? 'bg-blue-500' : 'bg-emerald-500' }}"></div>
                                        {{ $ori->origen ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 text-right font-mono text-emerald-600 dark:text-emerald-400 font-bold">
                                        Gs. {{ number_format($ori->venta_total ?? 0, 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-right font-mono text-gray-500">{{ number_format($ori->comprobantes ?? 0, 0) }}</td>
                                    <td class="px-6 py-4 text-right font-mono text-gray-400">
                                        {{ ($ori->comprobantes ?? 0) > 0 ? number_format($ori->venta_total / $ori->comprobantes, 0, ',', '.') : 0 }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </flux:card>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
            {{-- Ventas por Caja --}}
            <flux:card class="p-0 overflow-hidden border-none shadow-lg">
                <div class="p-4 border-b border-gray-100 dark:border-zinc-800 bg-gradient-to-r from-amber-50 to-transparent dark:from-amber-900/20">
                    <div class="flex items-center gap-2">
                        <flux:icon.computer-desktop class="text-amber-600 dark:text-amber-400" variant="solid" />
                        <flux:heading size="sm" class="!mb-0 font-bold">🖥️ Ventas por Caja</flux:heading>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs font-bold text-gray-400 uppercase bg-gray-50/50 dark:bg-zinc-800/50">
                            <tr>
                                <th class="px-6 py-4">Caja / Terminal</th>
                                <th class="px-6 py-4 text-right">Venta Total</th>
                                <th class="px-6 py-4 text-right">Transacciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                            @foreach ($porCaja as $caj)
                                @php $caj = (object)$caj; @endphp
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-zinc-800/50 transition-colors">
                                    <td class="px-6 py-4 font-bold">{{ $caj->caja ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 text-right font-mono text-amber-600 dark:text-amber-400 font-bold">
                                        Gs. {{ number_format($caj->venta_total ?? 0, 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-right font-mono text-gray-500">{{ number_format($caj->comprobantes ?? 0, 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </flux:card>

            {{-- Ventas por Turno --}}
            <flux:card class="p-0 overflow-hidden border-none shadow-lg">
                <div class="p-4 border-b border-gray-100 dark:border-zinc-800 bg-gradient-to-r from-rose-50 to-transparent dark:from-rose-900/20">
                    <div class="flex items-center gap-2">
                        <flux:icon.clock class="text-rose-600 dark:text-rose-400" variant="solid" />
                        <flux:heading size="sm" class="!mb-0 font-bold">🕒 Ventas por Turno</flux:heading>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs font-bold text-gray-400 uppercase bg-gray-50/50 dark:bg-zinc-800/50">
                            <tr>
                                <th class="px-6 py-4">Turno</th>
                                <th class="px-6 py-4 text-right">Venta Total</th>
                                <th class="px-6 py-4 text-right">Transacciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                            @foreach ($porTurno as $tur)
                                @php $tur = (object)$tur; @endphp
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-zinc-800/50 transition-colors">
                                    <td class="px-6 py-4 font-bold uppercase">{{ $tur->turno ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 text-right font-mono text-rose-600 dark:text-rose-400 font-bold">
                                        Gs. {{ number_format($tur->venta_total ?? 0, 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-right font-mono text-gray-500">{{ number_format($tur->comprobantes ?? 0, 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </flux:card>
        </div>

        {{-- Scripts --}}
        @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
        <script>
        const SALES_COLORS = {
            indigo:  '#6366f1',
            emerald: '#10b981',
            amber:   '#f59e0b',
            rose:    '#f43f5e',
            alpha: (hex, a) => hex + Math.round(a * 255).toString(16).padStart(2, '0'),
        };

        const globalSalesCharts = {};

        function biSales() {
            return {
                initChartEvolucion(el, data) {
                    globalSalesCharts.evolucion = new Chart(el, {
                        type: 'line',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: 'Venta Total',
                                data: data.ventas,
                                borderColor: SALES_COLORS.indigo,
                                backgroundColor: SALES_COLORS.alpha(SALES_COLORS.indigo, 0.1),
                                fill: true,
                                tension: 0.4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false }
                            },
                            scales: {
                                x: { ticks: { color: '#94a3b8', font: { size: 10 } }, grid: { display: false } },
                                y: { ticks: { color: '#94a3b8', font: { size: 10 } }, grid: { color: '#e2e8f010' } }
                            }
                        }
                    });
                },
                updateChartEvolucion(data) {
                    if (globalSalesCharts.evolucion) {
                        globalSalesCharts.evolucion.data.labels = data.labels;
                        globalSalesCharts.evolucion.data.datasets[0].data = data.ventas;
                        globalSalesCharts.evolucion.update();
                    }
                },
                initChartEstacionalidad(el, data) {
                    const dias = data.dias || ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
                    new Chart(el, {
                        type: 'bar',
                        data: {
                            labels: dias,
                            datasets: [{
                                label: 'Venta Promedio',
                                data: data.factores_dia || [],
                                backgroundColor: SALES_COLORS.amber,
                                borderRadius: 6,
                                barThickness: 30,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false }
                            },
                            scales: {
                                x: { ticks: { color: '#94a3b8', font: { size: 11, weight: '600' } }, grid: { display: false } },
                                y: { 
                                    ticks: { color: '#94a3b8', font: { size: 10 } }, 
                                    grid: { color: '#e2e8f010' },
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                },
                initChartFormasPago(el, data) {
                    globalSalesCharts.formasPago = new Chart(el, {
                        type: 'pie',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                data: data.ventas,
                                backgroundColor: [SALES_COLORS.indigo, SALES_COLORS.emerald, SALES_COLORS.amber, SALES_COLORS.rose, '#8b5cf6', '#06b6d4', '#ec4899', '#84cc16'],
                                borderWidth: 2,
                                borderColor: '#18181b'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { 
                                    position: 'right',
                                    labels: { color: '#94a3b8', font: { size: 12, weight: 'bold' }, padding: 20, usePointStyle: true }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            let label = context.label || '';
                                            let value = context.parsed || 0;
                                            return label + ': Gs. ' + value.toLocaleString();
                                        }
                                    }
                                }
                            }
                        }
                    });
                },
                updateChartFormasPago(data) {
                    if (globalSalesCharts.formasPago) {
                        globalSalesCharts.formasPago.data.labels = data.labels;
                        globalSalesCharts.formasPago.data.datasets[0].data = data.ventas;
                        globalSalesCharts.formasPago.update();
                    }
                }
            }
        }
        </script>
        @endpush
</div>
