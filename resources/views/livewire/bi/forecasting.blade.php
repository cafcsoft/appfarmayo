<div class="bi-dashboard flex flex-col gap-6 p-6" x-data="biForecasting()">
        
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <flux:heading size="xl" level="1">✨ Proyección de Ventas (Forecasting)</flux:heading>
                <flux:subheading>Modelado estadístico basado en histórico de 5 años</flux:subheading>
            </div>
            
            <div class="flex items-center gap-3">
                <flux:button wire:click="$refresh" icon="arrow-path" variant="outline" size="sm" />
            </div>
        </div>

        {{-- Configuration --}}
        <flux:card class="p-4 grid grid-cols-1 md:grid-cols-3 gap-6">
            <flux:field>
                <flux:label>Horizonte de Proyección</flux:label>
                <select wire:model.live="horizonteMeses" class="w-full rounded-lg border border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-sm px-3 py-2 text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="3">Próximos 3 meses</option>
                    <option value="6">Próximos 6 meses</option>
                    <option value="12">Próximos 12 meses</option>
                </select>
            </flux:field>
            
            <flux:field>
                <flux:label>Métrica a Proyectar</flux:label>
                <select wire:model.live="metrica" class="w-full rounded-lg border border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-sm px-3 py-2 text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="venta">Monto de Venta (Gs.)</option>
                    <option value="unidades">Unidades Vendidas</option>
                    <option value="margen">Margen Bruto (Gs.)</option>
                </select>
            </flux:field>
            
            <div class="flex flex-col justify-end">
                <flux:badge color="indigo" class="py-2 px-4 justify-center cursor-help" title="El modelo Holt-Winters permite capturar tanto la tendencia como la estacionalidad de los datos para proyecciones más precisas.">Modelo: Suavizado Exponencial Holt-Winters</flux:badge>
            </div>
        </flux:card>

        {{-- Forecast Chart --}}
        <flux:card class="mb-8">
            <flux:heading size="md" class="mb-6">Histórico vs Proyección (con intervalo de confianza 95%)</flux:heading>
            <div class="relative w-full h-[400px]" wire:ignore>
                <canvas id="chartForecast" x-init="initChartForecast($el, {{ $chartForecastJson }})" x-effect="updateChartForecast({{ $chartForecastJson }})"></canvas>
            </div>
        </flux:card>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Proyectado Table --}}
            <flux:card class="p-0 overflow-hidden">
                <div class="p-4 bg-gray-100 dark:bg-zinc-800 border-b border-gray-200 dark:border-zinc-700">
                    <span class="text-sm font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">📅 Valores Proyectados</span>
                </div>
                <table class="w-full text-sm text-left">
                    <thead class="text-xs font-semibold text-gray-500 uppercase">
                        <tr>
                            <th class="px-6 py-3">Periodo</th>
                            <th class="px-6 py-3 text-right">Proyección</th>
                            <th class="px-6 py-3 text-right text-gray-400">Rango (Mín/Máx)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                        @foreach ($proyeccion as $p)
                            @php $p = is_array($p) ? (object)$p : $p; @endphp
                            <tr>
                                <td class="px-6 py-4 font-semibold">{{ $p->periodo }}</td>
                                <td class="px-6 py-4 text-right font-mono text-indigo-600 dark:text-indigo-400">
                                    Gs. {{ number_format($p->proyectado, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-right text-xs text-gray-500">
                                    {{ number_format($p->limite_inf, 0, ',', '.') }} - {{ number_format($p->limite_sup, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </flux:card>

            {{-- Factores Estacionales --}}
            <flux:card>
                <flux:heading size="sm" class="mb-4">Factores de Estacionalidad Mensual</flux:heading>
                <div class="grid grid-cols-4 gap-2">
                    @foreach($factoresEstacionales as $mes => $factor)
                        <div class="p-2 rounded border border-gray-100 dark:border-zinc-800 text-center">
                            <p class="text-[10px] uppercase text-gray-500 font-bold">Mes {{ $mes }}</p>
                            <p class="text-sm font-mono @if($factor > 1) text-emerald-600 @else text-rose-600 @endif">
                                {{ round(($factor - 1) * 100, 1) }}%
                            </p>
                        </div>
                    @endforeach
                </div>
                <p class="mt-4 text-[10px] text-gray-400 italic">
                    * El % indica cuánto por encima o debajo de la tendencia base se vende en ese mes específico.
                </p>
            </flux:card>
        </div>

        @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
        <script>
        const FORECAST_COLORS = {
            indigo:  '#6366f1',
            emerald: '#10b981',
            zinc:    '#71717a',
            alpha: (hex, a) => hex + Math.round(a * 255).toString(16).padStart(2, '0'),
        };

        const globalForecastCharts = {};

        function biForecasting() {
            return {
                initChartForecast(el, data) {
                    if (!data || !data.labelsHist || !data.labelsProy) return;

                    const ctx = el.getContext('2d');
                    if (globalForecastCharts.forecast) {
                        globalForecastCharts.forecast.destroy();
                    }

                    globalForecastCharts.forecast = new Chart(el, {
                        type: 'line',
                        data: {
                            labels: [...(data.labelsHist || []), ...(data.labelsProy || [])],
                            datasets: [
                                {
                                    label: 'Histórico',
                                    data: [...(data.historico || []), ...Array(data.labelsProy?.length || 0).fill(null)],
                                    borderColor: FORECAST_COLORS.zinc,
                                    pointRadius: 2,
                                    borderWidth: 2
                                },
                                {
                                    label: 'Proyección',
                                    data: [
                                        ...Array(Math.max(0, (data.labelsHist?.length || 1) - 1)).fill(null),
                                        (data.historico && data.historico.length > 0) ? data.historico[data.historico.length - 1] : null,
                                        ...(data.proyectado || [])
                                    ],
                                    borderColor: FORECAST_COLORS.indigo,
                                    backgroundColor: FORECAST_COLORS.alpha(FORECAST_COLORS.indigo, 0.1),
                                    fill: false,
                                    borderDash: [5, 5],
                                    pointRadius: 4,
                                    pointBackgroundColor: FORECAST_COLORS.indigo
                                },
                                {
                                    label: 'Confianza Sup',
                                    data: [...Array(data.labelsHist?.length || 0).fill(null), ...(data.limSup || [])],
                                    borderColor: 'transparent',
                                    backgroundColor: FORECAST_COLORS.alpha(FORECAST_COLORS.indigo, 0.05),
                                    fill: 3, 
                                    pointRadius: 0
                                },
                                {
                                    label: 'Confianza Inf',
                                    data: [...Array(data.labelsHist?.length || 0).fill(null), ...(data.limInf || [])],
                                    borderColor: 'transparent',
                                    fill: false,
                                    pointRadius: 0
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { position: 'bottom' }
                            },
                            scales: {
                                y: { 
                                    beginAtZero: false,
                                    ticks: { font: { size: 10 } }, 
                                    grid: { color: '#e2e8f010' } 
                                },
                                x: { 
                                    ticks: { font: { size: 10 }, maxRotation: 45, minRotation: 45 }, 
                                    grid: { display: false } 
                                }
                            }
                        }
                    });
                },
                updateChartForecast(data) {
                    if (globalForecastCharts.forecast && data) {
                        const chart = globalForecastCharts.forecast;
                        chart.data.labels = [...(data.labelsHist || []), ...(data.labelsProy || [])];
                        chart.data.datasets[0].data = [...(data.historico || []), ...Array(data.labelsProy?.length || 0).fill(null)];
                        chart.data.datasets[1].data = [
                            ...Array(Math.max(0, (data.labelsHist?.length || 1) - 1)).fill(null),
                            (data.historico && data.historico.length > 0) ? data.historico[data.historico.length - 1] : null,
                            ...(data.proyectado || [])
                        ];
                        chart.data.datasets[2].data = [...Array(data.labelsHist?.length || 0).fill(null), ...(data.limSup || [])];
                        chart.data.datasets[3].data = [...Array(data.labelsHist?.length || 0).fill(null), ...(data.limInf || [])];
                        chart.update();
                    }
                }
            }
        }
        </script>
        @endpush
</div>
