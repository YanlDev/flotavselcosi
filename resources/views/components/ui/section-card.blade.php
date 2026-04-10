@props([
    'title' => null,
    'subtitle' => null,
    'padded' => true,
])

{{--
    Uso:
    <x-ui.section-card title="Información del vehículo" subtitle="Datos básicos">
        <x-slot:actions>
            <flux:button size="sm" icon="pencil">Editar</flux:button>
        </x-slot:actions>
        Contenido del body...
        <x-slot:footer>
            <flux:button variant="ghost">Cancelar</flux:button>
        </x-slot:footer>
    </x-ui.section-card>
--}}

<div {{ $attributes->class(['rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900']) }}>
    @if ($title || isset($actions))
        <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-5 py-4 dark:border-slate-800">
            <div class="min-w-0">
                @if ($title)
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">{{ $title }}</h3>
                @endif
                @if ($subtitle)
                    <p class="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400">{{ $subtitle }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>
            @endisset
        </div>
    @endif

    <div @class(['p-5' => $padded])>
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="flex items-center justify-end gap-2 border-t border-slate-200 bg-slate-50 px-5 py-3 dark:border-slate-800 dark:bg-slate-900/50">
            {{ $footer }}
        </div>
    @endisset
</div>
