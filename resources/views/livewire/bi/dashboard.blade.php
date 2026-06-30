<div class="bi-dashboard flex flex-col gap-8 p-6" x-data="biDashboard()">

        {{-- Header Section --}}
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <flux:heading size="xl" level="1" class="flex items-center gap-2">
                    <flux:icon name="chart-bar" variant="mini" class="text-indigo-500" />
                    Business Intelligence
                </flux:heading>
                <flux:subheading>
                    Análisis consolidado de ventas y clientes · Villa Morra
                </flux:subheading>
            </div>
            
            <div class="flex items-center gap-6">
                <div class="flex flex-col items-end pr-6 border-r border-gray-200 dark:border-zinc-700 hidden sm:flex">
                    <p class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-0.5">Última actualización</p>
                    <p class="text-sm font-mono font-semibold text-gray-800 dark:text-gray-200">{{ now()->format('d/m/Y H:i') }}</p>
                </div>
                
                <flux:button wire:click="refrescarDatos" icon="arrow-path" variant="outline" class="!px-5 !py-2.5 font-bold uppercase tracking-widest text-xs shadow-sm hover:bg-gray-50 dark:hover:bg-zinc-800" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="refrescarDatos">Actualizar Datos</span>
                    <span wire:loading wire:target="refrescarDatos">Cargando...</span>
                </flux:button>
            </div>
        </div>

        {{-- KPI Cards --}}
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-8">
            {{-- Venta del Mes --}}
            <flux:card class="flex items-center gap-4 p-5">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-500/10 text-indigo-600">
                    <flux:icon name="banknotes" />
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Venta del Mes</p>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                        Gs. {{ number_format($kpis['venta_mes'] ?? 0, 0, ',', '.') }}
                    </h3>
                    <div class="flex items-center gap-1 mt-0.5 text-xs @if(($kpis['variacion_pct'] ?? 0) >= 0) text-emerald-600 @else text-rose-600 @endif">
                        <flux:icon name="{{ ($kpis['variacion_pct'] ?? 0) >= 0 ? 'arrow-trending-up' : 'arrow-trending-down' }}" variant="micro" />
                        <span>{{ abs($kpis['variacion_pct'] ?? 0) }}% vs mes ant.</span>
                    </div>
                </div>
            </flux:card>

            {{-- Margen Bruto --}}
            <flux:card class="flex items-center gap-4 p-5">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600">
                    <flux:icon name="presentation-chart-line" />
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Margen Bruto</p>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                        Gs. {{ number_format($kpis['margen_mes'] ?? 0, 0, ',', '.') }}
                    </h3>
                    <p class="text-xs text-emerald-600 font-medium mt-0.5">
                        Rentabilidad: {{ $kpis['pct_margen'] ?? 0 }}%
                    </p>
                </div>
            </flux:card>

            {{-- Ticket Promedio --}}
            <flux:card class="flex items-center gap-4 p-5">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600">
                    <flux:icon name="receipt-percent" />
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Ticket Promedio</p>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                        Gs. {{ number_format($kpis['ticket_promedio'] ?? 0, 0, ',', '.') }}
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">
                        {{ number_format($kpis['comprobantes'] ?? 0, 0, ',', '.') }} transacciones
                    </p>
                </div>
            </flux:card>

            {{-- Clientes Activos --}}
            <flux:card class="flex items-center gap-4 p-5">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-violet-500/10 text-violet-600">
                    <flux:icon name="users" />
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Clientes Activos</p>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($kpisClientes['activos_30d'] ?? 0, 0, ',', '.') }}
                    </h3>
                    <p class="text-xs text-emerald-600 font-medium mt-0.5">
                        +{{ $kpisClientes['nuevos_30d'] ?? 0 }} nuevos este mes
                    </p>
                </div>
            </flux:card>
        </div>

        {{-- Charts Section --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3 mb-8">
            <flux:card class="lg:col-span-2">
                <div class="flex items-center justify-between mb-4">
                    <flux:heading size="md">Ventas Diarias</flux:heading>
                    <select wire:model.live="diasGrafico" class="rounded-lg border border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-sm px-3 py-1.5 text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 w-32">
                        <option value="14">14 días</option>
                        <option value="30">30 días</option>
                        <option value="60">60 días</option>
                    </select>
                </div>
                <div class="relative w-full h-[300px]" wire:ignore>
                    <canvas id="chartDiario" x-init="initChartDiario($el, {{ $chartDiarioJson }})" x-effect="updateChartDiario({{ $chartDiarioJson }})"></canvas>
                </div>
            </flux:card>

            <flux:card>
                <flux:heading size="md" class="mb-4">Distribución por Origen</flux:heading>
                <div class="relative w-full h-[300px] flex items-center justify-center" wire:ignore>
                    <canvas id="chartOrigen" x-init="initChartOrigen($el, {{ $chartOrigenJson }})"></canvas>
                </div>
            </flux:card>
        </div>

        {{-- Tendencias Section --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 mb-8">
            <flux:card>
                <div class="flex items-center justify-between mb-4">
                    <flux:heading size="md">Tendencia Mensual</flux:heading>
                    <select wire:model.live="mesesGrafico" class="rounded-lg border border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-sm px-3 py-1.5 text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 w-32">
                        <option value="6">6 meses</option>
                        <option value="12">12 meses</option>
                        <option value="24">24 meses</option>
                    </select>
                </div>
                <div class="relative w-full h-[250px]" wire:ignore>
                    <canvas id="chartMensual" x-init="initChartMensual($el, {{ $chartMensualJson }})" x-effect="updateChartMensual({{ $chartMensualJson }})"></canvas>
                </div>
            </flux:card>

            <flux:card>
                <flux:heading size="md" class="mb-4">Año Actual vs Año Anterior</flux:heading>
                <div class="relative w-full h-[250px]" wire:ignore>
                    <canvas id="chartComparacion" x-init="initChartComparacion($el, {{ $chartComparacionJson }})"></canvas>
                </div>
            </flux:card>
        </div>

        {{-- Tables Section --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 mb-8">
            {{-- Top Productos --}}
            <flux:card class="p-0 overflow-hidden">
                <div class="p-5 border-b border-gray-100 dark:border-zinc-800 flex justify-between items-center">
                    <flux:heading size="md">🏆 Top 5 Productos (mes)</flux:heading>
                    <flux:button variant="ghost" size="sm" :href="route('bi.productos')" wire:navigate icon-trailing="chevron-right">Ver todos</flux:button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs font-semibold text-gray-500 uppercase bg-gray-50/50 dark:bg-zinc-800/50">
                            <tr>
                                <th class="px-6 py-3">#</th>
                                <th class="px-6 py-3">Producto</th>
                                <th class="px-6 py-3 text-right">Venta</th>
                                <th class="px-6 py-3 text-center">Margen</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                            @forelse ($topProductos as $i => $prod)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                                    <td class="px-6 py-4 text-gray-400 font-medium">{{ $i + 1 }}</td>
                                    <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">{{ $prod->nombre ?? '—' }}</td>
                                    <td class="px-6 py-4 text-right font-mono text-indigo-600 dark:text-indigo-400">
                                        Gs. {{ number_format($prod->venta_total ?? 0, 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <flux:badge size="sm" variant="subtle" color="{{ ($prod->pct_margen ?? 0) >= 20 ? 'green' : (($prod->pct_margen ?? 0) >= 10 ? 'yellow' : 'red') }}">
                                            {{ round($prod->pct_margen ?? 0, 1) }}%
                                        </flux:badge>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-6 py-10 text-center text-gray-400">Sin datos disponibles</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </flux:card>

            {{-- Top Clientes --}}
            <flux:card class="p-0 overflow-hidden">
                <div class="p-5 border-b border-gray-100 dark:border-zinc-800 flex justify-between items-center">
                    <flux:heading size="md">⭐ Top 5 Clientes</flux:heading>
                    <flux:button variant="ghost" size="sm" :href="route('bi.clientes')" wire:navigate icon-trailing="chevron-right">Ver todos</flux:button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs font-semibold text-gray-500 uppercase bg-gray-50/50 dark:bg-zinc-800/50">
                            <tr>
                                <th class="px-6 py-3">#</th>
                                <th class="px-6 py-3">Cliente</th>
                                <th class="px-6 py-3 text-right">Venta Acum.</th>
                                <th class="px-6 py-3 text-center">Segmento</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                            @forelse ($topClientes as $i => $cli)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                                    <td class="px-6 py-4 text-gray-400 font-medium">{{ $i + 1 }}</td>
                                    <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">{{ $cli->nombre ?? '—' }}</td>
                                    <td class="px-6 py-4 text-right font-mono text-violet-600 dark:text-violet-400">
                                        Gs. {{ number_format($cli->venta_total ?? 0, 0, ',', '.') }}
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
                                            {{ $cli->segmento ?? 'Ocasional' }}
                                        </flux:badge>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-6 py-10 text-center text-gray-400">Sin datos disponibles</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </flux:card>
        </div>

        {{-- Scripts Alpine + Chart.js --}}
        @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
        <script>
        const BI_COLORS = {
            indigo:  '#6366f1',
            emerald: '#10b981',
            amber:   '#f59e0b',
            rose:    '#f43f5e',
            violet:  '#8b5cf6',
            cyan:    '#06b6d4',
            alpha: (hex, a) => hex + Math.round(a * 255).toString(16).padStart(2, '0'),
        };

        const chartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { labels: { color: '#94a3b8', font: { size: 11 } } },
                tooltip: {
                    padding: 12,
                    backgroundColor: '#1e293b',
                    titleColor: '#f8fafc',
                    bodyColor: '#f8fafc',
                    borderColor: '#334155',
                    borderWidth: 1,
                    callbacks: {
                        label: ctx => ' Gs. ' + ctx.parsed.y?.toLocaleString('es-PY') ?? ctx.parsed.y
                    }
                }
            },
            scales: {
                x: { ticks: { color: '#64748b', font: { size: 10 } }, grid: { display: false } },
                y: {
                    ticks: { 
                        color: '#64748b', 
                        font: { size: 10 },
                        callback: v => 'Gs. ' + (v / 1_000_000).toFixed(1) + 'M'
                    },
                    grid: { color: '#e2e8f015' }
                }
            }
        };

        const globalCharts = {};

        function biDashboard() {
            return {
                initChartDiario(el, data) {
                    globalCharts.diario = new Chart(el, {
                        type: 'bar',
                        data: {
                            labels: data.labels,
                            datasets: [
                                {
                                    label: 'Ventas',
                                    data: data.ventas,
                                    backgroundColor: BI_COLORS.alpha(BI_COLORS.indigo, 0.7),
                                    borderRadius: 6,
                                },
                                {
                                    label: 'Margen',
                                    data: data.margen,
                                    type: 'line',
                                    borderColor: BI_COLORS.emerald,
                                    tension: 0.4,
                                    pointRadius: 0,
                                }
                            ]
                        },
                        options: chartOptions
                    });
                },
                updateChartDiario(data) {
                    if (globalCharts.diario) {
                        globalCharts.diario.data.labels = data.labels;
                        globalCharts.diario.data.datasets[0].data = data.ventas;
                        globalCharts.diario.data.datasets[1].data = data.margen;
                        globalCharts.diario.update();
                    }
                },
                initChartOrigen(el, data) {
                    globalCharts.origen = new Chart(el, {
                        type: 'doughnut',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                data: data.ventas,
                                backgroundColor: [BI_COLORS.indigo, BI_COLORS.emerald, BI_COLORS.amber],
                                borderWidth: 0,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '70%',
                            plugins: {
                                legend: { position: 'bottom', labels: { color: '#94a3b8', padding: 20 } }
                            }
                        }
                    });
                },
                initChartMensual(el, data) {
                    globalCharts.mensual = new Chart(el, {
                        type: 'line',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: 'Venta Mensual',
                                data: data.ventas,
                                borderColor: BI_COLORS.indigo,
                                backgroundColor: BI_COLORS.alpha(BI_COLORS.indigo, 0.1),
                                fill: true,
                                tension: 0.4,
                            }]
                        },
                        options: chartOptions
                    });
                },
                updateChartMensual(data) {
                    if (globalCharts.mensual) {
                        globalCharts.mensual.data.labels = data.labels;
                        globalCharts.mensual.data.datasets[0].data = data.ventas;
                        globalCharts.mensual.update();
                    }
                },
                initChartComparacion(el, data) {
                    const meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
                    globalCharts.comp = new Chart(el, {
                        type: 'bar',
                        data: {
                            labels: meses,
                            datasets: [
                                { label: '{{ $anioAnterior }}', data: data.anterior, backgroundColor: BI_COLORS.alpha(BI_COLORS.violet, 0.5), borderRadius: 4 },
                                { label: '{{ $anioActual }}', data: data.actual, backgroundColor: BI_COLORS.alpha(BI_COLORS.indigo, 0.8), borderRadius: 4 }
                            ]
                        },
                        options: chartOptions
                    });
                }
            }
        }
        </script>
        @endpush
</div>
