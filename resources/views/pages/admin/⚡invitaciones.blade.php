<?php

use App\Models\Invitacion;
use App\Models\Sucursal;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Invitaciones')] class extends Component {
    public string $email = '';
    public string $rol = '';
    public ?int $sucursalId = null;

    public bool $showCreateModal = false;
    public bool $showLinkModal = false;
    public string $linkGenerado = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->esAdmin(), 403);
    }

    #[Computed]
    public function invitaciones(): \Illuminate\Database\Eloquent\Collection
    {
        return Invitacion::with(['sucursal', 'invitadoPor'])
            ->orderByDesc('created_at')
            ->get();
    }

    #[Computed]
    public function sucursales(): \Illuminate\Database\Eloquent\Collection
    {
        return Sucursal::activas()->orderBy('nombre')->get();
    }

    public function openCreate(): void
    {
        $this->reset(['email', 'rol', 'sucursalId']);
        $this->resetValidation();
        $this->showCreateModal = true;
    }

    public function crear(): void
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $this->validate([
            'email' => ['required', 'email', 'max:255'],
            'rol' => ['required', 'in:admin,jefe_resguardo,visor'],
            'sucursalId' => [
                'nullable',
                'exists:sucursales,id',
                $this->rol !== 'admin' ? 'required' : 'nullable',
            ],
        ]);

        $invitacion = Invitacion::create([
            'token' => Str::random(64),
            'email' => $this->email,
            'rol' => $this->rol,
            'sucursal_id' => $this->rol === 'admin' ? null : $this->sucursalId,
            'invitado_por' => auth()->id(),
            'expira_en' => now()->addDays(7),
        ]);

        $this->linkGenerado = route('registro.invitacion', $invitacion->token);

        $this->showCreateModal = false;
        $this->showLinkModal = true;
        $this->reset(['email', 'rol', 'sucursalId']);
    }

    public function estadoBadgeColor(string $estado): string
    {
        return match ($estado) {
            'activo' => 'green',
            'usado' => 'zinc',
            'expirado' => 'red',
            default => 'zinc',
        };
    }

    public function estadoLabel(string $estado): string
    {
        return match ($estado) {
            'activo' => 'Activo',
            'usado' => 'Usado',
            'expirado' => 'Expirado',
            default => $estado,
        };
    }

    public function rolLabel(string $rol): string
    {
        return match ($rol) {
            'admin' => 'Admin',
            'jefe_resguardo' => 'Jefe de resguardo',
            'visor' => 'Visor',
            default => $rol,
        };
    }
}; ?>

<section class="w-full">
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Invitaciones') }}</flux:heading>
            <flux:text class="hidden sm:block">{{ __('Genera enlaces de registro y compártelos manualmente.') }}</flux:text>
        </div>
        <flux:button wire:click="openCreate" variant="primary" icon="link">
            <span class="hidden sm:inline">{{ __('Nueva invitación') }}</span>
            <span class="sm:hidden">{{ __('Nueva') }}</span>
        </flux:button>
    </div>

    {{-- Tabla desktop --}}
    <div class="hidden sm:block">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Email') }}</flux:table.column>
                <flux:table.column>{{ __('Rol') }}</flux:table.column>
                <flux:table.column>{{ __('Sucursal') }}</flux:table.column>
                <flux:table.column>{{ __('Expira') }}</flux:table.column>
                <flux:table.column>{{ __('Estado') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->invitaciones as $inv)
                    <flux:table.row :key="$inv->id">
                        <flux:table.cell class="text-sm">{{ $inv->email }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge color="blue" size="sm">{{ $this->rolLabel($inv->rol) }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="text-sm">{{ $inv->sucursal?->nombre ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="text-sm">{{ $inv->expira_en->format('d/m/Y') }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$this->estadoBadgeColor($inv->estado)" size="sm">
                                {{ $this->estadoLabel($inv->estado) }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($inv->estaActiva())
                                <flux:tooltip :content="__('Copiar enlace')">
                                    <flux:button
                                        size="sm" variant="subtle" icon="clipboard-document" inset="top bottom"
                                        x-on:click="navigator.clipboard.writeText('{{ route('registro.invitacion', $inv->token) }}'); $flux.toast('{{ __('Enlace copiado') }}')"
                                    />
                                </flux:tooltip>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>

    {{-- Cards mobile --}}
    <div class="sm:hidden space-y-3">
        @foreach ($this->invitaciones as $inv)
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 p-4">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold">{{ $inv->email }}</p>
                        <p class="mt-0.5 text-xs text-zinc-400">
                            {{ __('Expira') }}: {{ $inv->expira_en->format('d/m/Y') }}
                            @if ($inv->sucursal) · {{ $inv->sucursal->nombre }} @endif
                        </p>
                        <div class="mt-1.5 flex flex-wrap gap-1.5">
                            <flux:badge color="blue" size="sm">{{ $this->rolLabel($inv->rol) }}</flux:badge>
                            <flux:badge :color="$this->estadoBadgeColor($inv->estado)" size="sm">
                                {{ $this->estadoLabel($inv->estado) }}
                            </flux:badge>
                        </div>
                    </div>
                    @if ($inv->estaActiva())
                        <flux:button
                            size="sm" variant="subtle" icon="clipboard-document" inset="top bottom"
                            class="shrink-0"
                            x-on:click="navigator.clipboard.writeText('{{ route('registro.invitacion', $inv->token) }}'); $flux.toast('{{ __('Enlace copiado') }}')"
                        />
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    @if ($this->invitaciones->isEmpty())
        <div class="py-12 text-center">
            <flux:text>{{ __('No hay invitaciones registradas.') }}</flux:text>
        </div>
    @endif

    {{-- Modal nueva invitación --}}
    <flux:modal wire:model.self="showCreateModal" class="md:w-96">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Nueva invitación') }}</flux:heading>

            <form wire:submit="crear" class="space-y-4">
                <flux:input
                    wire:model="email"
                    :label="__('Email')"
                    type="email"
                    :placeholder="__('correo@ejemplo.com')"
                    required
                />

                <flux:select wire:model.live="rol" :label="__('Rol')" required>
                    <flux:select.option value="">{{ __('Seleccionar rol') }}</flux:select.option>
                    <flux:select.option value="admin">{{ __('Admin') }}</flux:select.option>
                    <flux:select.option value="jefe_resguardo">{{ __('Jefe de resguardo') }}</flux:select.option>
                    <flux:select.option value="visor">{{ __('Visor') }}</flux:select.option>
                </flux:select>

                @if ($rol !== '' && $rol !== 'admin')
                    <flux:select wire:model="sucursalId" :label="__('Sucursal')" required>
                        <flux:select.option value="">{{ __('Seleccionar sucursal') }}</flux:select.option>
                        @foreach ($this->sucursales as $sucursal)
                            <flux:select.option :value="$sucursal->id">{{ $sucursal->nombre }}</flux:select.option>
                        @endforeach
                    </flux:select>
                @endif

                <div class="flex justify-end gap-2 pt-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">{{ __('Cancelar') }}</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                        {{ __('Generar enlace') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- Modal mostrar link generado --}}
    <flux:modal wire:model.self="showLinkModal" class="md:w-lg" :dismissible="false">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('Enlace generado') }}</flux:heading>
                <flux:text class="mt-1">
                    {{ __('Copia y comparte este enlace. Solo puede usarse una vez y expira en 7 días.') }}
                </flux:text>
            </div>

            <div class="flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:text class="flex-1 break-all text-sm font-mono">{{ $linkGenerado }}</flux:text>
                <flux:button
                    size="sm"
                    variant="subtle"
                    icon="clipboard-document"
                    class="shrink-0"
                    x-on:click="
                        navigator.clipboard.writeText('{{ $linkGenerado }}');
                        $flux.toast('{{ __('Enlace copiado') }}');
                    "
                />
            </div>

            <div class="flex justify-end pt-2">
                <flux:modal.close>
                    <flux:button variant="primary">{{ __('Listo') }}</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</section>
