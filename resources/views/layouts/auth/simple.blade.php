<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen hero-gradient antialiased text-gray-800">
        <div class="flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-sm flex-col gap-6 glass-card p-8 rounded-3xl shadow-xl z-10">
                <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium" wire:navigate>
                    <span class="flex h-16 w-16 mb-2 items-center justify-center rounded-2xl bg-emerald-50 border border-emerald-100 shadow-sm">
                        <x-app-logo-icon class="size-10 text-emerald" />
                    </span>
                    <span class="sr-only">{{ config('app.name', 'Mayo') }}</span>
                </a>
                <div class="flex flex-col gap-6">
                    {{ $slot }}
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
