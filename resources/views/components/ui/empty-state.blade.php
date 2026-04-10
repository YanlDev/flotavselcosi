@props([
    'icon' => 'inbox',
    'title' => 'Sin datos',
    'description' => null,
])

{{--
    Uso:
    <x-ui.empty-state icon="truck" title="Sin vehículos" description="Aún no has registrado ningún vehículo.">
        <flux:button variant="primary" icon="plus" :href="route('vehiculos.create')">Nuevo vehículo</flux:button>
    </x-ui.empty-state>
--}}

<div {{ $attributes->class(['flex flex-col items-center justify-center px-6 py-12 text-center']) }}>
    <div class="flex size-14 items-center justify-center rounded-full bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500">
        <flux:icon :name="$icon" class="size-7" />
    </div>
    <h3 class="mt-4 text-sm font-semibold text-slate-900 dark:text-white">{{ $title }}</h3>
    @if ($description)
        <p class="mt-1 max-w-sm text-sm text-slate-500 dark:text-slate-400">{{ $description }}</p>
    @endif
    @if (trim($slot))
        <div class="mt-5 flex items-center gap-2">{{ $slot }}</div>
    @endif
</div>
