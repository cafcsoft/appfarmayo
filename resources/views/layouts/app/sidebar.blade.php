<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800 text-gray-800 dark:text-gray-200">
    <flux:sidebar sticky collapsible="mobile"
        class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.header>
            <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
            <flux:sidebar.collapse class="lg:hidden" />
        </flux:sidebar.header>

        <flux:sidebar.nav>
            <flux:sidebar.group :heading="__('Platform')" class="grid">
                @can('access-admin-reports')
                    <flux:sidebar.item icon="document-text" :href="route('admin.reporte.facturas')"
                        :current="request()->routeIs('admin.reporte.facturas')" wire:navigate>
                        {{ __('Reporte Facturas') }}
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
                <flux:sidebar.item icon="chart-bar" :href="route('bi.dashboard')"
                    :current="request()->routeIs('bi.dashboard')" wire:navigate>
                    Dashboard BI
                </flux:sidebar.item>
                <flux:sidebar.item icon="arrow-trending-up" :href="route('bi.ventas')"
                    :current="request()->routeIs('bi.ventas')" wire:navigate>
                    Análisis de Ventas
                </flux:sidebar.item>
                <flux:sidebar.item icon="tag" :href="route('bi.productos')"
                    :current="request()->routeIs('bi.productos')" wire:navigate>
                    Análisis de Productos
                </flux:sidebar.item>
                <flux:sidebar.item icon="users" :href="route('bi.clientes')"
                    :current="request()->routeIs('bi.clientes')" wire:navigate>
                    Análisis de Clientes
                </flux:sidebar.item>
                <flux:sidebar.item icon="sparkles" :href="route('bi.forecasting')"
                    :current="request()->routeIs('bi.forecasting')" wire:navigate>
                    Forecasting
                </flux:sidebar.item>
            </flux:sidebar.group>
            @endcan
        </flux:sidebar.nav>

        <flux:spacer />

        {{-- <flux:sidebar.nav>
                <flux:sidebar.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank">
                    {{ __('Repository') }}
                </flux:sidebar.item>

                <flux:sidebar.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">
                    {{ __('Documentation') }}
                </flux:sidebar.item>
            </flux:sidebar.nav> --}}

        <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
    </flux:sidebar>

    <!-- Mobile User Menu -->
    <flux:header class="lg:hidden">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        <flux:spacer />

        <flux:dropdown position="top" align="end">
            <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />

            <flux:menu>
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <flux:avatar :name="auth()->user()->name" :initials="auth()->user()->initials()" />

                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.radio.group>
                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                        {{ __('Settings') }}
                    </flux:menu.item>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                        class="w-full cursor-pointer" data-test="logout-button">
                        {{ __('Log out') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:header>

    {{ $slot }}

    @stack('scripts')
    @fluxScripts
</body>

</html>
