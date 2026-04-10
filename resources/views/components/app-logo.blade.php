@props([
    'sidebar' => false,
])

@if ($sidebar)
    <a 
        href="{{ route('dashboard') }}" 
        class="flex h-10 w-full items-center justify-center px-2 text-center text-[15px] font-roboto font-black uppercase tracking-tight text-brand-600 transition-colors hover:text-brand-700 dark:text-brand-500 dark:hover:text-brand-400 in-data-flux-sidebar-collapsed-desktop:hidden"
        {{ $attributes->except('href') }}
    >
        SELCOSI FLOTA VEHICULAR
    </a>
    <a 
        href="{{ route('dashboard') }}" 
        class="hidden h-10 w-10 items-center justify-center font-roboto text-xl font-black text-brand-600 transition-colors hover:text-brand-700 dark:text-brand-500 dark:hover:text-brand-400 in-data-flux-sidebar-collapsed-desktop:flex"
    >
        S
    </a>
@else
    <a 
        href="{{ route('dashboard') }}" 
        class="flex items-center text-[15px] font-roboto font-black uppercase tracking-tight text-brand-600 transition-colors hover:text-brand-700 dark:text-brand-500 dark:hover:text-brand-400"
        {{ $attributes->except('href') }}
    >
        SELCOSI FLOTA VEHICULAR
    </a>
@endif
