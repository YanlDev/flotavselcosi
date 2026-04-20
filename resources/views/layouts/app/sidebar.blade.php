<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                {{-- Principal: accesible para todos los roles --}}
                <flux:sidebar.group :heading="__('OPERACIÓN')" class="grid">
                    <flux:sidebar.item
                        icon="home"
                        :href="route('dashboard')"
                        :current="request()->routeIs('dashboard')"
                        wire:navigate
                    >
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="truck"
                        :href="route('vehiculos.index')"
                        :current="request()->routeIs('vehiculos.*')"
                        wire:navigate
                    >
                        {{ __('Vehículos') }}
                    </flux:sidebar.item>

                    @php
                        $alertas             = app(\App\Services\AlertasService::class);
                        $combustiblePendiente = $alertas->combustiblePendiente(auth()->user());
                        $totalAlertas        = $alertas->totalAlertas(auth()->user());
                    @endphp
                    <flux:sidebar.item
                        icon="fire"
                        :href="route('combustible.index')"
                        :current="request()->routeIs('combustible.*')"
                        :badge="$combustiblePendiente > 0 ? $combustiblePendiente : null"
                        badge:color="amber"
                        wire:navigate
                    >
                        {{ __('Combustible') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="bell"
                        :href="route('alertas.index')"
                        :current="request()->routeIs('alertas.*')"
                        :badge="$totalAlertas > 0 ? $totalAlertas : null"
                        badge:color="red"
                        wire:navigate
                    >
                        {{ __('Alertas') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                {{-- Administración: solo admin --}}
                @if (auth()->user()->esAdmin())
                    <flux:sidebar.group :heading="__('ADMINISTRACIÓN')" class="grid">
                        <flux:sidebar.item
                            icon="identification"
                            :href="route('conductores.index')"
                            :current="request()->routeIs('conductores.*')"
                            wire:navigate
                        >
                            {{ __('Conductores') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item
                            icon="users"
                            :href="route('admin.usuarios')"
                            :current="request()->routeIs('admin.usuarios')"
                            wire:navigate
                        >
                            {{ __('Usuarios') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item
                            icon="envelope"
                            :href="route('admin.invitaciones')"
                            :current="request()->routeIs('admin.invitaciones')"
                            wire:navigate
                        >
                            {{ __('Invitaciones') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item
                            icon="building-office"
                            :href="route('admin.sucursales')"
                            :current="request()->routeIs('admin.sucursales')"
                            wire:navigate
                        >
                            {{ __('Sucursales') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endif
            </flux:sidebar.nav>

            <flux:spacer />

            {{-- Toggle apariencia --}}
            <div x-data class="px-2 pb-1 hidden lg:block">
                <button
                    type="button"
                    x-on:click="$flux.appearance = $flux.appearance === 'light' ? 'dark' : ($flux.appearance === 'dark' ? 'system' : 'light')"
                    class="flex w-full items-center gap-3 rounded-lg px-2 py-2 text-sm font-medium text-zinc-600 transition-colors hover:bg-zinc-800/5 dark:text-zinc-400 dark:hover:bg-white/10"
                >
                    <span x-show="$flux.appearance === 'light'" class="shrink-0">
                        <flux:icon name="sun" class="size-5" />
                    </span>
                    <span x-show="$flux.appearance === 'dark'" class="shrink-0">
                        <flux:icon name="moon" class="size-5" />
                    </span>
                    <span x-show="$flux.appearance !== 'light' && $flux.appearance !== 'dark'" class="shrink-0">
                        <flux:icon name="computer-desktop" class="size-5" />
                    </span>
                    <span x-show="$flux.appearance === 'light'">{{ __('Modo claro') }}</span>
                    <span x-show="$flux.appearance === 'dark'">{{ __('Modo oscuro') }}</span>
                    <span x-show="$flux.appearance !== 'light' && $flux.appearance !== 'dark'">{{ __('Sistema') }}</span>
                </button>
            </div>

            <x-desktop-user-menu class="hidden lg:block" />
        </flux:sidebar>

        {{-- Header móvil --}}
        <flux:header class="lg:hidden border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
            <x-app-logo href="{{ route('dashboard') }}" wire:navigate />
            <flux:spacer />
            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />
                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />
                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Ajustes') }}
                        </flux:menu.item>
                        <div x-data>
                            <flux:menu.item
                                as="button"
                                type="button"
                                icon="sun"
                                x-on:click="$flux.appearance = $flux.appearance === 'light' ? 'dark' : ($flux.appearance === 'dark' ? 'system' : 'light')"
                            >
                                <span x-show="$flux.appearance === 'light'">{{ __('Modo claro') }}</span>
                                <span x-show="$flux.appearance === 'dark'">{{ __('Modo oscuro') }}</span>
                                <span x-show="$flux.appearance !== 'light' && $flux.appearance !== 'dark'">{{ __('Sistema') }}</span>
                            </flux:menu.item>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Cerrar sesión') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @fluxScripts
    </body>
</html>
