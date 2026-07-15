<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800">
    <flux:header container class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.toggle class="lg:hidden mr-2" icon="bars-2" inset="left" />

        <x-app-logo href="{{ route('dashboard') }}" wire:navigate />

        <flux:navbar class="-mb-px max-lg:hidden">
            {{-- <flux:navbar.item icon="layout-grid" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                </flux:navbar.item> --}}
            @can('access-admin-reports')
                <flux:navbar.item icon="document-text" :href="route('admin.reporte.facturas')"
                    :current="request()->routeIs('admin.reporte.facturas')" wire:navigate>
                    {{ __('Reporte Facturas') }}
                </flux:navbar.item>
            @endcan

            @can('manage-users')
                <flux:navbar.item icon="users" :href="route('admin.usuarios')"
                    :current="request()->routeIs('admin.usuarios')" wire:navigate>
                    {{ __('Usuarios') }}
                </flux:navbar.item>
            @endcan

            @can('access-admin-reports')
                <flux:dropdown class="max-lg:hidden">
                    <flux:navbar.item icon="chart-bar" class="cursor-pointer">
                        {{ __('Business Intelligence') }}
                    </flux:navbar.item>

                    <flux:menu>
                        <flux:menu.item icon="chart-bar" :href="route('bi.dashboard')" wire:navigate>{{ __('Dashboard BI') }}</flux:menu.item>
                        <flux:menu.item icon="arrow-trending-up" :href="route('bi.ventas')" wire:navigate>{{ __('Análisis de Ventas') }}</flux:menu.item>
                        <flux:menu.item icon="tag" :href="route('bi.productos')" wire:navigate>{{ __('Análisis de Productos') }}</flux:menu.item>
                        <flux:menu.item icon="users" :href="route('bi.clientes')" wire:navigate>{{ __('Análisis de Clientes') }}</flux:menu.item>
                        <flux:menu.item icon="sparkles" :href="route('bi.forecasting')" wire:navigate>{{ __('Forecasting') }}</flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            @endcan

            @can('access-accounting')
                <flux:dropdown class="max-lg:hidden">
                    <flux:navbar.item icon="calculator" class="cursor-pointer">
                        {{ __('Accounting') }}
                    </flux:navbar.item>

                    <flux:menu>
                        <flux:menu.item icon="layout-grid" :href="route('contabilidad.dashboard')" wire:navigate>{{ __('Dashboard Contabilidad') }}</flux:menu.item>
                        <flux:menu.item icon="document-text" :href="route('contabilidad.reporte.facturas')" wire:navigate>{{ __('Reporte de Ventas') }}</flux:menu.item>
                        <flux:menu.item icon="calculator" :href="route('contabilidad.libro-ventas-iva')" wire:navigate>{{ __('Libro de ventas IVA') }}</flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            @endcan

            @if (auth()->user()->is_customer)
                <flux:navbar.item icon="document-text" :href="route('mis.facturas')"
                    :current="request()->routeIs('mis.facturas')" wire:navigate>
                    {{ __('Mis Facturas') }}
                </flux:navbar.item>
            @endif
        </flux:navbar>

        <flux:spacer />
        {{-- 
        <flux:navbar class="me-1.5 space-x-0.5 rtl:space-x-reverse py-0!">
            <flux:tooltip :content="__('Search')" position="bottom">
                <flux:navbar.item class="!h-10 [&>div>svg]:size-5" icon="magnifying-glass" href="#"
                    :label="__('Search')" />
            </flux:tooltip>
            <flux:tooltip :content="__('Repository')" position="bottom">
                <flux:navbar.item class="h-10 max-lg:hidden [&>div>svg]:size-5" icon="folder-git-2"
                    href="https://github.com/laravel/livewire-starter-kit" target="_blank" :label="__('Repository')" />
            </flux:tooltip>
            <flux:tooltip :content="__('Documentation')" position="bottom">
                <flux:navbar.item class="h-10 max-lg:hidden [&>div>svg]:size-5" icon="book-open-text"
                    href="https://laravel.com/docs/starter-kits#livewire" target="_blank"
                    :label="__('Documentation')" />
            </flux:tooltip>
        </flux:navbar> --}}

        <x-desktop-user-menu />
    </flux:header>

    <!-- Mobile Menu -->
    <flux:sidebar collapsible="mobile" sticky
        class="lg:hidden border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.header>
            <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
            <flux:sidebar.collapse
                class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
        </flux:sidebar.header>

        <flux:sidebar.nav>
            <flux:sidebar.group :heading="__('Platform')">
                <flux:sidebar.item icon="layout-grid" :href="route('dashboard')"
                    :current="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                </flux:sidebar.item>
                @can('access-admin-reports')
                    <flux:sidebar.item icon="document-text" :href="route('admin.reporte.facturas')"
                        :current="request()->routeIs('admin.reporte.facturas')" wire:navigate>
                        {{ __('Reporte Facturas') }}
                    </flux:sidebar.item>
                @endcan

                @can('manage-users')
                    <flux:sidebar.item icon="users" :href="route('admin.usuarios')"
                        :current="request()->routeIs('admin.usuarios')" wire:navigate>
                        {{ __('Usuarios') }}
                    </flux:sidebar.item>
                @endcan

                @if (auth()->user()->is_customer)
                    <flux:sidebar.item icon="document-text" :href="route('mis.facturas')"
                        :current="request()->routeIs('mis.facturas')" wire:navigate>
                        {{ __('Mis Facturas') }}
                    </flux:sidebar.item>
                @endif
            </flux:sidebar.group>

            {{-- BI Module — solo administradores --}}
            @can('access-admin-reports')
            <flux:sidebar.group heading="Business Intelligence" class="grid">
                <flux:sidebar.item icon="chart-bar" :href="route('bi.dashboard')" :current="request()->routeIs('bi.dashboard')" wire:navigate>Dashboard BI</flux:sidebar.item>
                <flux:sidebar.item icon="arrow-trending-up" :href="route('bi.ventas')" :current="request()->routeIs('bi.ventas')" wire:navigate>Análisis de Ventas</flux:sidebar.item>
                <flux:sidebar.item icon="tag" :href="route('bi.productos')" :current="request()->routeIs('bi.productos')" wire:navigate>Análisis de Productos</flux:sidebar.item>
                <flux:sidebar.item icon="users" :href="route('bi.clientes')" :current="request()->routeIs('bi.clientes')" wire:navigate>Análisis de Clientes</flux:sidebar.item>
                <flux:sidebar.item icon="sparkles" :href="route('bi.forecasting')" :current="request()->routeIs('bi.forecasting')" wire:navigate>Forecasting</flux:sidebar.item>
            </flux:sidebar.group>
            @endcan

            {{-- Contabilidad Module — solo contadores --}}
            @can('access-accounting')
            <flux:sidebar.group heading="Contabilidad" class="grid">
                <flux:sidebar.item icon="layout-grid" :href="route('contabilidad.dashboard')" :current="request()->routeIs('contabilidad.dashboard')" wire:navigate>Dashboard Contabilidad</flux:sidebar.item>
                <flux:sidebar.item icon="document-text" :href="route('contabilidad.reporte.facturas')" :current="request()->routeIs('contabilidad.reporte.facturas')" wire:navigate>Reporte de Ventas</flux:sidebar.item>
                <flux:sidebar.item icon="calculator" :href="route('contabilidad.libro-ventas-iva')" :current="request()->routeIs('contabilidad.libro-ventas-iva')" wire:navigate>Libro de ventas IVA</flux:sidebar.item>
            </flux:sidebar.group>
            @endcan
        </flux:sidebar.nav>

        <flux:spacer />

        {{-- <flux:sidebar.nav>
            <flux:sidebar.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit"
                target="_blank">
                {{ __('Repository') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire"
                target="_blank">
                {{ __('Documentation') }}
            </flux:sidebar.item>
        </flux:sidebar.nav> --}}
    </flux:sidebar>

    {{ $slot }}

    @stack('scripts')
    @fluxScripts
</body>

</html>
