<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                {{-- Principal: accesible para todos los roles --}}
                <flux:sidebar.group :heading="__('Principal')" class="grid">
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

                    <flux:sidebar.item
                        icon="fire"
                        :href="route('combustible.index')"
                        :current="request()->routeIs('combustible.*')"
                        wire:navigate
                    >
                        {{ __('Combustible') }}
                    </flux:sidebar.item>

                    @php
                        $totalAlertas = app(\App\Services\AlertasService::class)->totalAlertas(auth()->user());
                    @endphp
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
                    <flux:sidebar.group :heading="__('Administración')" class="grid">
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

            <x-desktop-user-menu class="hidden lg:block" />
        </flux:sidebar>

        {{-- Header móvil --}}
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
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
