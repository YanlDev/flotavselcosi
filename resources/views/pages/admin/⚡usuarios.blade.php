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
    public bool $showDeleteModal = false;
    public ?int $deletingId = null;

    public bool $showPasswordModal = false;
    public string $temporaryPassword = '';

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

    public function resetPassword(User $user): void
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $password = \Illuminate\Support\Str::password(12, symbols: false);

        $user->update(['password' => $password]);

        $this->temporaryPassword = $password;
        $this->showPasswordModal = true;
    }

    public function confirmDelete(User $user): void
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $this->deletingId = $user->id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $user = User::findOrFail($this->deletingId);

        abort_if($user->id === auth()->id(), 403);
        abort_if(
            $user->esAdmin() && User::role('admin')->count() <= 1,
            403,
        );

        $user->delete();

        $this->showDeleteModal = false;
        $this->deletingId = null;
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

<section class="w-full px-3 py-4 sm:p-6 lg:p-8">
    <x-ui.page-header
        :title="__('Usuarios')"
        :subtitle="__('Gestiona los roles, sucursales y acceso de los usuarios')"
        :breadcrumbs="[
            ['label' => __('Dashboard'), 'href' => route('dashboard')],
            ['label' => __('Usuarios')],
        ]"
    />

    {{-- Tabla desktop --}}
    <div class="hidden sm:block overflow-hidden rounded-xl border border-slate-200 bg-white px-2 shadow-sm dark:border-slate-800 dark:bg-slate-900">
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
                                    <p class="text-sm font-semibold">{{ $usuario->name }}</p>
                                    <p class="text-xs text-zinc-500">{{ $usuario->email }}</p>
                                </div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($rolNombre)
                                <flux:badge :color="$this->rolBadgeColor($rolNombre)" size="sm">{{ $this->rolLabel($rolNombre) }}</flux:badge>
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="text-sm">{{ $usuario->sucursal?->nombre ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$usuario->activo ? 'green' : 'zinc'" size="sm">
                                {{ $usuario->activo ? __('Activo') : __('Inactivo') }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex justify-end gap-1">
                                <flux:button wire:click="toggleActivo({{ $usuario->id }})" size="sm" variant="subtle" :icon="$usuario->activo ? 'lock-open' : 'lock-closed'" inset="top bottom" />
                                <flux:button wire:click="resetPassword({{ $usuario->id }})" size="sm" variant="subtle" icon="key" inset="top bottom" />
                                <flux:button wire:click="edit({{ $usuario->id }})" size="sm" variant="subtle" icon="pencil" inset="top bottom" />
                                @if ($usuario->id !== auth()->id())
                                    <flux:button wire:click="confirmDelete({{ $usuario->id }})" size="sm" variant="subtle" icon="trash" inset="top bottom" class="text-red-500 hover:text-red-600" />
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>

    {{-- Cards mobile --}}
    <div class="sm:hidden space-y-3">
        @foreach ($this->usuarios as $usuario)
            @php $rolNombre = $usuario->roles->first()?->name ?? '' @endphp
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 p-4">
                <div class="flex items-start justify-between gap-2">
                    <div class="flex items-center gap-3 min-w-0">
                        <flux:avatar :name="$usuario->name" size="sm" class="shrink-0" />
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-sm">{{ $usuario->name }}</p>
                            <p class="truncate text-xs text-zinc-500">{{ $usuario->email }}</p>
                            <div class="mt-1.5 flex flex-wrap gap-1.5">
                                @if ($rolNombre)
                                    <flux:badge :color="$this->rolBadgeColor($rolNombre)" size="sm">{{ $this->rolLabel($rolNombre) }}</flux:badge>
                                @endif
                                <flux:badge :color="$usuario->activo ? 'green' : 'zinc'" size="sm">
                                    {{ $usuario->activo ? __('Activo') : __('Inactivo') }}
                                </flux:badge>
                                @if ($usuario->sucursal)
                                    <span class="text-xs text-zinc-400">{{ $usuario->sucursal->nombre }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="flex shrink-0 gap-1">
                        <flux:button wire:click="toggleActivo({{ $usuario->id }})" size="sm" variant="subtle" :icon="$usuario->activo ? 'lock-open' : 'lock-closed'" inset="top bottom" />
                        <flux:button wire:click="resetPassword({{ $usuario->id }})" size="sm" variant="subtle" icon="key" inset="top bottom" />
                        <flux:button wire:click="edit({{ $usuario->id }})" size="sm" variant="subtle" icon="pencil" inset="top bottom" />
                        @if ($usuario->id !== auth()->id())
                            <flux:button wire:click="confirmDelete({{ $usuario->id }})" size="sm" variant="subtle" icon="trash" inset="top bottom" class="text-red-500 hover:text-red-600" />
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if ($this->usuarios->isEmpty())
        <div class="py-12 text-center">
            <flux:text>{{ __('No hay usuarios registrados.') }}</flux:text>
        </div>
    @endif

    {{-- Modal contraseña temporal --}}
    <flux:modal wire:model.self="showPasswordModal" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Contraseña temporal generada') }}</flux:heading>
                <flux:subheading>{{ __('Comparte esta contraseña con el usuario. Solo se muestra una vez.') }}</flux:subheading>
            </div>

            <div
                x-data="{ copied: false }"
                class="flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-800"
            >
                <span class="flex-1 font-mono text-lg font-semibold tracking-widest text-zinc-800 dark:text-white select-all">
                    {{ $temporaryPassword }}
                </span>
                <flux:button
                    x-show="!copied"
                    size="sm"
                    variant="ghost"
                    icon="clipboard"
                    x-on:click="
                        const text = '{{ $temporaryPassword }}';
                        if (navigator.clipboard && window.isSecureContext) {
                            navigator.clipboard.writeText(text);
                        } else {
                            const el = document.createElement('textarea');
                            el.value = text;
                            el.style.position = 'fixed';
                            el.style.left = '-9999px';
                            el.style.top = '-9999px';
                            document.body.appendChild(el);
                            el.focus();
                            el.select();
                            document.execCommand('copy');
                            document.body.removeChild(el);
                        }
                        copied = true;
                        setTimeout(() => copied = false, 2000);
                    "
                />
                <flux:button x-show="copied" x-cloak size="sm" variant="ghost" icon="check" class="text-green-600" />
            </div>

            <div class="flex justify-end">
                <flux:modal.close>
                    <flux:button variant="primary">{{ __('Listo') }}</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

    {{-- Modal confirmar eliminación --}}
    <flux:modal wire:model.self="showDeleteModal" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Eliminar usuario') }}</flux:heading>
                <flux:subheading>{{ __('Esta acción no se puede deshacer. El usuario perderá acceso inmediatamente.') }}</flux:subheading>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancelar') }}</flux:button>
                </flux:modal.close>
                <flux:button wire:click="delete" variant="danger" wire:loading.attr="disabled">
                    {{ __('Eliminar') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

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
