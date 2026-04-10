@props([
    'label',
    'value',
    'icon' => null,
    'color' => 'brand', // brand | info | warning | danger | slate
    'trend' => null,    // '+12%' | '-3%'
    'trendUp' => null,  // true | false | null
    'hint' => null,
    'href' => null,
])

@php
    $palette = [
        'brand'   => ['bg' => 'bg-brand-50 dark:bg-brand-950/40', 'text' => 'text-brand-600 dark:text-brand-400', 'ring' => 'ring-brand-100 dark:ring-brand-900'],
        'info'    => ['bg' => 'bg-sky-50 dark:bg-sky-950/40', 'text' => 'text-sky-600 dark:text-sky-400', 'ring' => 'ring-sky-100 dark:ring-sky-900'],
        'warning' => ['bg' => 'bg-amber-50 dark:bg-amber-950/40', 'text' => 'text-amber-600 dark:text-amber-400', 'ring' => 'ring-amber-100 dark:ring-amber-900'],
        'danger'  => ['bg' => 'bg-red-50 dark:bg-red-950/40', 'text' => 'text-red-600 dark:text-red-400', 'ring' => 'ring-red-100 dark:ring-red-900'],
        'slate'   => ['bg' => 'bg-slate-100 dark:bg-slate-800', 'text' => 'text-slate-600 dark:text-slate-300', 'ring' => 'ring-slate-200 dark:ring-slate-700'],
    ][$color] ?? [];

    $tag = $href ? 'a' : 'div';
    $interactive = $href ? 'transition hover:border-brand-300 hover:shadow-md dark:hover:border-brand-700' : '';
@endphp

{{--
    Uso:
    <x-ui.stat-card label="Vehículos activos" value="124" icon="truck" color="brand" trend="+5%" :trendUp="true" hint="vs mes anterior" :href="route('vehiculos.index')" />
--}}

<{{ $tag }}
    @if ($href) href="{{ $href }}" wire:navigate @endif
    {{ $attributes->class(['block rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900', $interactive]) }}
>
    @if ($icon)
        <div class="mb-3 flex size-9 items-center justify-center rounded-lg ring-1 sm:hidden {{ $palette['bg'] }} {{ $palette['ring'] }}">
            <flux:icon :name="$icon" class="size-4 {{ $palette['text'] }}" />
        </div>
    @endif

    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0 flex-1">
            <p class="text-[11px] font-medium uppercase leading-tight tracking-wide text-slate-500 sm:text-xs dark:text-slate-400">{{ $label }}</p>
            <p class="mt-2 font-mono-data text-2xl font-semibold text-slate-900 sm:text-3xl dark:text-white">{{ $value }}</p>
            @if ($hint || $trend)
                <div class="mt-2 flex items-center gap-2 text-xs">
                    @if ($trend)
                        <span @class([
                            'inline-flex items-center gap-0.5 font-medium',
                            'text-brand-600 dark:text-brand-400' => $trendUp === true,
                            'text-red-600 dark:text-red-400' => $trendUp === false,
                            'text-slate-500 dark:text-slate-400' => $trendUp === null,
                        ])>
                            @if ($trendUp === true)
                                <flux:icon name="arrow-trending-up" class="size-3.5" />
                            @elseif ($trendUp === false)
                                <flux:icon name="arrow-trending-down" class="size-3.5" />
                            @endif
                            {{ $trend }}
                        </span>
                    @endif
                    @if ($hint)
                        <span class="text-slate-500 dark:text-slate-400">{{ $hint }}</span>
                    @endif
                </div>
            @endif
        </div>

        @if ($icon)
            <div class="hidden sm:flex size-11 shrink-0 items-center justify-center rounded-lg ring-1 {{ $palette['bg'] }} {{ $palette['ring'] }}">
                <flux:icon :name="$icon" class="size-5 {{ $palette['text'] }}" />
            </div>
        @endif
    </div>
</{{ $tag }}>
