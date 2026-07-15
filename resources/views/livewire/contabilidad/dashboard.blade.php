<div class="invoice-container">
    {{-- Header Bar --}}
    <div class="bg-gray-900 border-b border-gray-800 px-6 py-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">
                Panel <span class="text-indigo-400">Contable</span>
            </h1>
            <p class="text-gray-400 text-sm mt-0.5">{{ __('Bienvenido al Módulo de Contabilidad, :name', ['name' =>
                auth()->user()->name]) }}</p>
        </div>

        <div class="flex items-center gap-2">
            <span
                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                Rol: Contador
            </span>
            <span class="text-xs text-gray-500">Última sync: Hace 5 min</span>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="p-6 flex flex-col gap-6">

        {{-- KPI Cards Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

            {{-- Card 1 --}}
            <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5 shadow-sm hover:border-zinc-700 transition">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Ventas Gravadas
                        10%</span>
                    <div class="p-2 rounded-lg bg-green-500/10 text-green-400">
                        <flux:icon name="banknotes" class="w-5 h-5" />
                    </div>
                </div>
                <div class="mt-4">
                    <h3 class="text-2xl font-bold text-white">{{ number_format($gravadas10Actual, 0, ',', '.') }} Gs.
                    </h3>
                    <p
                        class="text-xs mt-1 flex items-center gap-1 {{ $gravadas10Sube ? 'text-green-400' : 'text-red-400' }}">
                        <span>{{ $gravadas10Sube ? '↑' : '↓' }} {{ number_format($gravadas10Pct, 1, ',', '.') }}%</span>
                        <span class="text-zinc-500">vs mes anterior</span>
                    </p>
                </div>
            </div>

            {{-- Card 2 --}}
            <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5 shadow-sm hover:border-zinc-700 transition">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Ventas Gravadas 5%</span>
                    <div class="p-2 rounded-lg bg-indigo-500/10 text-indigo-400">
                        <flux:icon name="calculator" class="w-5 h-5" />
                    </div>
                </div>
                <div class="mt-4">
                    <h3 class="text-2xl font-bold text-white">{{ number_format($iva5Actual, 0, ',', '.') }} Gs.</h3>
                    <p class="text-xs mt-1 flex items-center gap-1 {{ $iva5Sube ? 'text-green-400' : 'text-red-400' }}">
                        <span>{{ $iva5Sube ? '↑' : '↓' }} {{ number_format($iva5Pct, 1, ',', '.') }}%</span>
                        <span class="text-zinc-500">vs mes anterior</span>
                    </p>
                </div>
            </div>

            {{-- Card 3 --}}
            <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5 shadow-sm hover:border-zinc-700 transition">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Ventas Exentas</span>
                    <div class="p-2 rounded-lg bg-teal-500/10 text-teal-400">
                        <flux:icon name="banknotes" class="w-5 h-5" />
                    </div>
                </div>
                <div class="mt-4">
                    <h3 class="text-2xl font-bold text-white">{{ number_format($exentasActual, 0, ',', '.') }} Gs.</h3>
                    <p
                        class="text-xs mt-1 flex items-center gap-1 {{ $exentasSube ? 'text-green-400' : 'text-red-400' }}">
                        <span>{{ $exentasSube ? '↑' : '↓' }} {{ number_format($exentasPct, 1, ',', '.') }}%</span>
                        <span class="text-zinc-500">vs mes anterior</span>
                    </p>
                </div>
            </div>

            {{-- Card 4 --}}
            <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5 shadow-sm hover:border-zinc-700 transition">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Comprobantes
                        Emitidos</span>
                    <div class="p-2 rounded-lg bg-yellow-500/10 text-yellow-400">
                        <flux:icon name="document-text" class="w-5 h-5" />
                    </div>
                </div>
                <div class="mt-4">
                    <h3 class="text-2xl font-bold text-white">{{ number_format($comprobantesEmitidos, 0, ',', '.') }}
                    </h3>
                    <p class="text-xs text-zinc-500 mt-1">Este mes</p>
                </div>
            </div>

        </div>

        {{-- Direct Access Section --}}
        <div>
            <h2 class="text-lg font-bold text-white mb-4">Accesos Directos y Reportes</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                {{-- Libro Ventas Card --}}
                <a href="{{ route('contabilidad.libro-ventas-iva') }}" wire:navigate
                    class="bg-zinc-900 border border-zinc-800 rounded-xl p-6 hover:border-indigo-500/50 hover:bg-zinc-850/30 transition group flex flex-col justify-between h-44">
                    <div>
                        <div
                            class="w-10 h-10 bg-indigo-500/10 text-indigo-400 rounded-lg flex items-center justify-center mb-4 group-hover:bg-indigo-500 group-hover:text-white transition">
                            <flux:icon name="calculator" class="w-5 h-5" />
                        </div>
                        <h4 class="text-base font-bold text-white group-hover:text-indigo-400 transition">Libro de
                            Ventas IVA</h4>
                        <p class="text-xs text-gray-400 mt-1">Generación y descarga del reporte impositivo de ventas del
                            período.</p>
                    </div>
                    <span class="text-xs text-indigo-400 font-semibold mt-4 flex items-center gap-1">
                        <span>Ingresar</span>
                        <span>→</span>
                    </span>
                </a>

                {{-- Reporte Ventas (Facturas) --}}
                <a href="{{ route('contabilidad.reporte.facturas') }}" wire:navigate
                    class="bg-zinc-900 border border-zinc-800 rounded-xl p-6 hover:border-indigo-500/50 hover:bg-zinc-850/30 transition group flex flex-col justify-between h-44">
                    <div>
                        <div
                            class="w-10 h-10 bg-emerald-500/10 text-emerald-400 rounded-lg flex items-center justify-center mb-4 group-hover:bg-emerald-500 group-hover:text-white transition">
                            <flux:icon name="document-text" class="w-5 h-5" />
                        </div>
                        <h4 class="text-base font-bold text-white group-hover:text-emerald-400 transition">Reporte de
                            Ventas</h4>
                        <p class="text-xs text-gray-400 mt-1">Consulta y descarga del listado de comprobantes y facturas
                            de venta.</p>
                    </div>
                    <span class="text-xs text-emerald-400 font-semibold mt-4 flex items-center gap-1">
                        <span>Ingresar</span>
                        <span>→</span>
                    </span>
                </a>

                {{-- Libro Compras Card (Disabled) --}}
                <div
                    class="bg-zinc-900 border border-zinc-800 rounded-xl p-6 flex flex-col justify-between h-44 opacity-60">
                    <div>
                        <div
                            class="w-10 h-10 bg-zinc-800 text-zinc-400 rounded-lg flex items-center justify-center mb-4">
                            <flux:icon name="shopping-bag" class="w-5 h-5" />
                        </div>
                        <h4 class="text-base font-bold text-white">Libro de Compras IVA</h4>
                        <p class="text-xs text-gray-400 mt-1">Módulo para registro y clasificación de facturas de
                            compras.</p>
                    </div>
                    <span class="text-xs text-zinc-500 font-semibold mt-4 flex items-center gap-1">
                        <span>Próximamente</span>
                    </span>
                </div>

                {{-- Hechauka Card (Disabled) --}}
                <!-- <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-6 flex flex-col justify-between h-44 opacity-60">
                    <div>
                        <div class="w-10 h-10 bg-zinc-800 text-zinc-400 rounded-lg flex items-center justify-center mb-4">
                            <flux:icon name="arrow-up-tray" class="w-5 h-5" />
                        </div>
                        <h4 class="text-base font-bold text-white">Exportador Hechauka (SET)</h4>
                        <p class="text-xs text-gray-400 mt-1">Generación de archivos de texto requeridos por la SET para Hechauka.</p>
                    </div>
                    <span class="text-xs text-zinc-500 font-semibold mt-4 flex items-center gap-1">
                        <span>Próximamente</span>
                    </span>
                </div> -->

            </div>
        </div>

    </div>
</div>