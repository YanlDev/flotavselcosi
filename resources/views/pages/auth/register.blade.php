<x-layouts::auth :title="__('Crear cuenta')">
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('Crear tu cuenta')"
            :description="__('Completa tus datos para activar tu acceso a Selcosi Flota')"
        />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register') }}" class="flex flex-col gap-6">
            @csrf

            <input type="hidden" name="token" value="{{ $invitacion->token }}">

            {{-- Email bloqueado — viene de la invitación --}}
            <flux:input
                name="email"
                :label="__('Correo electrónico')"
                :value="$invitacion->email"
                type="email"
                readonly
                class="bg-zinc-100 dark:bg-zinc-800 cursor-not-allowed"
            />

            <flux:input
                name="name"
                :label="__('Nombre completo')"
                :value="old('name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                :placeholder="__('Tu nombre completo')"
            />

            <flux:input
                name="password"
                :label="__('Contraseña')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Mínimo 8 caracteres')"
                viewable
            />

            <flux:input
                name="password_confirmation"
                :label="__('Confirmar contraseña')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Repite tu contraseña')"
                viewable
            />

            @if ($invitacion->sucursal_id)
                <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-300">
                    Serás asignado a la sucursal <strong>{{ $invitacion->sucursal->nombre }}</strong>
                    con el rol de <strong>{{ str_replace('_', ' ', $invitacion->rol) }}</strong>.
                </div>
            @endif

            <flux:button type="submit" variant="primary" class="w-full" data-test="register-user-button">
                {{ __('Activar cuenta') }}
            </flux:button>
        </form>

        <div class="text-center text-sm text-zinc-600 dark:text-zinc-400">
            {{ __('¿Ya tienes cuenta?') }}
            <flux:link :href="route('login')" wire:navigate>{{ __('Inicia sesión') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
