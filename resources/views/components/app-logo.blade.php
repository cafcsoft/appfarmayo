@props([
    'sidebar' => false,
])

@if ($sidebar)
    <flux:sidebar.brand name="Mayo" {{ $attributes }}>
        <x-slot name="logo">
            <img src="{{ asset('images/logo.png') }}" alt="Mayo Plus" class="size-8 object-contain">
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="Mayo" {{ $attributes }}>
        <x-slot name="logo">
            <img src="{{ asset('images/logo.png') }}" alt="Mayo Plus" class="size-8 object-contain">
        </x-slot>
    </flux:brand>
@endif
