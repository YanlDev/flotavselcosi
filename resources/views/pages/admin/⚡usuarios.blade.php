<?php

use App\Models\Sucursal;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Usuarios')] class extends Component {
    public ?int $editingId = null;
    public string $rol = '';
    public ?int $sucursalId = null;
    public bool $activo = true;

    public bool $showEditModal = false;

    public function mount(): void
    {
        abort_unless(auth()->user()->esAdmin(), 403);
    }

    #[Computed]
    public function usuarios(): \Illuminate\Database\Eloquent\Collection
    {
        return User::with(['roles', 'sucursal'])
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function sucursales(): \Illuminate\Database\Eloquent\Collection
    {
        return Sucursal::activas()->orderBy('nombre')->get();
    }

    public function edit(User $user): void
    {
        $this->editingId = $user->id;
        $this->rol = $user->roles->first()?->name ?? '';
        $this->sucursalId = $user->sucursal_id;
        $this->activo = $user->activo;
        $this->resetValidation();
        $this->showEditModal = true;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $validated = $this->validate([
            'rol' => ['required', 'in:admin,jefe_resguardo,visor'],
            'sucursalId' => [
                'nullable',
                'exists:sucursales,id',
                $this->rol !== 'admin' ? 'required' : 'nullable',
            ],
            'activo' => ['boolean'],
        ]);

        $user = User::findOrFail($this->editingId);

        $user->syncRoles([$this->rol]);
        $user->sucursal_id = $this->rol === 'admin' ? null : $this->sucursalId;
        $user->activo = $this->activo;
        $user->save();

        $this->showEditModal = false;
        $this->resetEdit();
    }

    public function toggleActivo(User $user): void
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $user->update(['activo' => ! $user->activo]);
    }

    private function resetEdit(): void
    {
        $this->editingId = null;
        $this->rol = '';
        $this->sucursalId = null;
        $this->activo = true;
        $this->resetValidation();
    }

    /** @return array<string, string> */
    public function rolBadgeColor(string $rol): string
    {
        return match ($rol) {
            'admin' => 'blue',
            'jefe_resguardo' => 'amber',
            default => 'zinc',
        };
    }

    /** @return array<string, string> */
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
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl">{{ __('Usuarios') }}</flux:heading>
            <flux:text>{{ __('Gestiona los roles, sucursales y acceso de los usuarios.') }}</flux:text>
        </div>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Usuario') }}</flux:table.column>
            <flux:table.column>{{ __('Rol') }}</flux:table.column>
            <flux:table.column>{{ __('Sucursal') }}</flux:table.column>
            <flux:table.column>{{ __('Estado') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->usuarios as $usuario)
                @php $rolNombre = $usuario->roles->first()?->name ?? '' @endphp
                <flux:table.row :key="$usuario->id">
                    <flux:table.cell>
                        <div class="flex items-center gap-3">
                            <flux:avatar :name="$usuario->name" size="sm" />
                            <div>
                                <flux:heading>{{ $usuario->name }}</flux:heading>
                                <flux:text size="sm">{{ $usuario->email }}</flux:text>
                            </div>
                        </div>
                    </flux:table.cell>

                    <flux:table.cell>
                        @if ($rolNombre)
                            <flux:badge :color="$this->rolBadgeColor($rolNombre)">
                                {{ $this->rolLabel($rolNombre) }}
                            </flux:badge>
                        @else
                            <flux:text>—</flux:text>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        {{ $usuario->sucursal?->nombre ?? '—' }}
                    </flux:table.cell>

                    <flux:table.cell>
                        @if ($usuario->activo)
                            <flux:badge color="green">{{ __('Activo') }}</flux:badge>
                        @else
                            <flux:badge color="zinc">{{ __('Inactivo') }}</flux:badge>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="flex justify-end gap-2">
                            <flux:button
                                wire:click="toggleActivo({{ $usuario->id }})"
                                size="sm"
                                variant="subtle"
                                :icon="$usuario->activo ? 'lock-open' : 'lock-closed'"
                                inset="top bottom"
                            />
                            <flux:button
                                wire:click="edit({{ $usuario->id }})"
                                size="sm"
                                variant="subtle"
                                icon="pencil"
                                inset="top bottom"
                            />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    @if ($this->usuarios->isEmpty())
        <div class="py-12 text-center">
            <flux:text>{{ __('No hay usuarios registrados.') }}</flux:text>
        </div>
    @endif

    {{-- Modal editar usuario --}}
    <flux:modal wire:model.self="showEditModal" class="md:w-96">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Editar usuario') }}</flux:heading>

            <form wire:submit="save" class="space-y-4">
                <flux:select
                    wire:model.live="rol"
                    :label="__('Rol')"
                    required
                >
                    <flux:select.option value="">{{ __('Seleccionar rol') }}</flux:select.option>
                    <flux:select.option value="admin">{{ __('Admin') }}</flux:select.option>
                    <flux:select.option value="jefe_resguardo">{{ __('Jefe de resguardo') }}</flux:select.option>
                    <flux:select.option value="visor">{{ __('Visor') }}</flux:select.option>
                </flux:select>

                @if ($rol !== 'admin')
                    <flux:select
                        wire:model="sucursalId"
                        :label="__('Sucursal')"
                        required
                    >
                        <flux:select.option value="">{{ __('Seleccionar sucursal') }}</flux:select.option>
                        @foreach ($this->sucursales as $sucursal)
                            <flux:select.option :value="$sucursal->id">{{ $sucursal->nombre }}</flux:select.option>
                        @endforeach
                    </flux:select>
                @endif

                <flux:field variant="inline">
                    <flux:label>{{ __('Activo') }}</flux:label>
                    <flux:switch wire:model="activo" />
                </flux:field>

                <div class="flex justify-end gap-2 pt-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">{{ __('Cancelar') }}</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                        {{ __('Guardar') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</section>
