@props([
    'title',
    'subtitle' => null,
    'breadcrumbs' => [], // [['label' => '...', 'href' => '...'], ...]
])

{{--
    Uso:
    <x-ui.page-header title="Vehículos" subtitle="Gestión de flota" :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Vehículos'],
    ]">
        <x-slot:actions>
            <flux:button variant="primary" icon="plus">Nuevo</flux:button>
        </x-slot:actions>
    </x-ui.page-header>
--}}

<div {{ $attributes->class(['mb-6']) }}>
    @if (! empty($breadcrumbs))
        <nav class="mb-2 flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
            @foreach ($breadcrumbs as $i => $crumb)
                @if ($i > 0)
                    <flux:icon name="chevron-right" class="size-3.5 text-slate-300 dark:text-slate-600" />
                @endif
                @if (! empty($crumb['href']) && ! $loop->last)
                    <a href="{{ $crumb['href'] }}" wire:navigate class="hover:text-brand-600 dark:hover:text-brand-400">
                        {{ $crumb['label'] }}
                    </a>
                @else
                    <span class="text-slate-700 dark:text-slate-300">{{ $crumb['label'] }}</span>
                @endif
            @endforeach
        </nav>
    @endif

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">
                {{ $title }}
            </h1>
            @if ($subtitle)
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $subtitle }}</p>
            @endif
        </div>

        @isset($actions)
            <div class="flex items-center gap-2">
                {{ $actions }}
            </div>
        @endisset
    </div>
</div>
