@php
    $user = auth()->user();
    $rolLabel = match(true) {
        $user->esAdmin() => 'Administrador',
        $user->esJefeResguardo() => 'Jefe de Resguardo',
        $user->esVisor() => 'Visor',
        default => 'Usuario',
    };
@endphp

<flux:dropdown position="bottom" align="start">
    <flux:sidebar.profile
        :name="$user->name"
        :initials="$user->initials()"
        icon:trailing="chevrons-up-down"
        data-test="sidebar-menu-button"
    />

    <flux:menu>
        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
            <flux:avatar :name="$user->name" :initials="$user->initials()" />
            <div class="grid flex-1 text-start text-sm leading-tight">
                <flux:heading class="truncate">{{ $user->name }}</flux:heading>
                <flux:text class="truncate text-xs">{{ $rolLabel }}</flux:text>
                @if ($user->sucursal)
                    <flux:text class="truncate text-xs text-zinc-400 dark:text-zinc-500">
                        {{ $user->sucursal->nombre }}
                    </flux:text>
                @endif
            </div>
        </div>

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
