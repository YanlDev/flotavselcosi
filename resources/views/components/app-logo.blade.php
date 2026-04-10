@props([
    'sidebar' => false,
])

@php
    $brandName = config('app.name', 'Selcosi Flota');
@endphp

@if ($sidebar)
    <flux:sidebar.brand :name="$brandName" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-9 items-center justify-center rounded-lg bg-white ring-1 ring-brand-200 dark:bg-brand-950 dark:ring-brand-800">
            <img src="{{ asset('selcosilog.png') }}" alt="{{ $brandName }}" class="size-7 object-contain" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand :name="$brandName" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-9 items-center justify-center rounded-lg bg-white ring-1 ring-brand-200 dark:bg-brand-950 dark:ring-brand-800">
            <img src="{{ asset('selcosilog.png') }}" alt="{{ $brandName }}" class="size-7 object-contain" />
        </x-slot>
    </flux:brand>
@endif
