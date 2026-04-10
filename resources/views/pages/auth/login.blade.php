<x-layouts::auth :title="__('Iniciar sesión')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Selcosi Flota Vehicular')" :description="__('Ingresa tus credenciales para acceder al sistema')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5">
            @csrf

            <!-- Email Address -->
            <div>
                <label for="email" class="mb-1.5 block text-sm font-medium text-slate-300">
                    {{ __('Correo electrónico') }}
                </label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="email"
                    placeholder="correo@ejemplo.com"
                    class="block w-full rounded-lg border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder-slate-500 transition-colors focus:border-brand-500 focus:bg-white/10 focus:outline-none focus:ring-2 focus:ring-brand-500/40"
                />
                @error('email')
                    <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password -->
            <div>
                <div class="mb-1.5 flex items-center justify-between">
                    <label for="password" class="text-sm font-medium text-slate-300">
                        {{ __('Contraseña') }}
                    </label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-xs font-medium text-brand-400 transition-colors hover:text-brand-300" wire:navigate>
                            {{ __('¿Olvidaste tu contraseña?') }}
                        </a>
                    @endif
                </div>
                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                    class="block w-full rounded-lg border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder-slate-500 transition-colors focus:border-brand-500 focus:bg-white/10 focus:outline-none focus:ring-2 focus:ring-brand-500/40"
                />
                @error('password')
                    <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Remember Me -->
            <label class="flex items-center gap-2 cursor-pointer select-none">
                <input
                    type="checkbox"
                    name="remember"
                    @checked(old('remember'))
                    class="size-4 rounded border-white/20 bg-white/5 text-brand-500 focus:ring-brand-500/40 focus:ring-offset-0"
                />
                <span class="text-sm text-slate-400">{{ __('Recordar sesión') }}</span>
            </label>

            <!-- Submit -->
            <button
                type="submit"
                data-test="login-button"
                class="mt-1 flex w-full items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-600/25 transition-all hover:bg-brand-500 hover:shadow-brand-500/30 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 focus:ring-offset-slate-900 active:scale-[0.98]"
            >
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M3 4.25A2.25 2.25 0 0 1 5.25 2h5.5A2.25 2.25 0 0 1 13 4.25v2a.75.75 0 0 1-1.5 0v-2a.75.75 0 0 0-.75-.75h-5.5a.75.75 0 0 0-.75.75v11.5c0 .414.336.75.75.75h5.5a.75.75 0 0 0 .75-.75v-2a.75.75 0 0 1 1.5 0v2A2.25 2.25 0 0 1 10.75 18h-5.5A2.25 2.25 0 0 1 3 15.75V4.25Z" clip-rule="evenodd" />
                    <path fill-rule="evenodd" d="M19 10a.75.75 0 0 0-.75-.75H8.704l1.048-.943a.75.75 0 1 0-1.004-1.114l-2.5 2.25a.75.75 0 0 0 0 1.114l2.5 2.25a.75.75 0 1 0 1.004-1.114l-1.048-.943h9.546A.75.75 0 0 0 19 10Z" clip-rule="evenodd" />
                </svg>
                {{ __('Iniciar sesión') }}
            </button>
        </form>

        {{-- El registro solo es posible mediante invitación --}}
    </div>
</x-layouts::auth>
