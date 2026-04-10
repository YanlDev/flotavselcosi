@props([
    'title' => null,
    'subtitle' => null,
    'empty' => false,
    'emptyIcon' => 'inbox',
    'emptyTitle' => 'Sin registros',
    'emptyDescription' => null,
])

{{--
    Wrapper sobre flux:table. Provee card contenedora, header con filtros/acciones y estado vacío.

    Uso:
    <x-ui.data-table title="Vehículos" subtitle="124 registros">
        <x-slot:filters>
            <flux:input icon="magnifying-glass" placeholder="Buscar..." wire:model.live="search" />
        </x-slot:filters>
        <x-slot:actions>
            <flux:button variant="primary" icon="plus">Nuevo</flux:button>
        </x-slot:actions>

        <flux:table>
            ...
        </flux:table>
    </x-ui.data-table>
--}}

<div {{ $attributes->class(['overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900']) }}>
    @if ($title || isset($filters) || isset($actions))
        <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 dark:border-slate-800 md:flex-row md:items-center md:justify-between">
            <div class="min-w-0">
                @if ($title)
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">{{ $title }}</h3>
                @endif
                @if ($subtitle)
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ $subtitle }}</p>
                @endif
            </div>

            <div class="flex flex-col gap-2 md:flex-row md:items-center">
                @isset($filters)
                    <div class="flex items-center gap-2">{{ $filters }}</div>
                @endisset
                @isset($actions)
                    <div class="flex items-center gap-2">{{ $actions }}</div>
                @endisset
            </div>
        </div>
    @endif

    @if ($empty)
        <x-ui.empty-state :icon="$emptyIcon" :title="$emptyTitle" :description="$emptyDescription">
            {{ $emptyActions ?? '' }}
        </x-ui.empty-state>
    @else
        <div class="overflow-x-auto">
            {{ $slot }}
        </div>
    @endif

    @isset($footer)
        <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">
            {{ $footer }}
        </div>
    @endisset
</div>
