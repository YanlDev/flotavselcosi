@props([
    'value',
    'up' => null, // true | false | null
])

{{-- Uso: <x-ui.metric-trend value="+12%" :up="true" /> --}}

<span {{ $attributes->class([
    'inline-flex items-center gap-0.5 text-xs font-medium',
    'text-brand-600 dark:text-brand-400' => $up === true,
    'text-red-600 dark:text-red-400' => $up === false,
    'text-slate-500 dark:text-slate-400' => $up === null,
]) }}>
    @if ($up === true)
        <flux:icon name="arrow-trending-up" class="size-3.5" />
    @elseif ($up === false)
        <flux:icon name="arrow-trending-down" class="size-3.5" />
    @endif
    {{ $value }}
</span>
