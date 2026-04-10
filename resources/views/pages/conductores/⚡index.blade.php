<?php

use App\Models\Conductor;
use App\Models\Sucursal;
use App\Models\Vehiculo;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Conductores')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $filterSucursal = '';

    #[Url]
    public string $filterActivo = '';

    // Modal crear / editar
    public bool $showModal = false;
    public ?int $editingId = null;

    public string $nombreCompleto = '';
    public string $dni = '';
    public string $telefono = '';
    public string $email = '';
    public string $sucursalId = '';
    /** @var array<int> IDs de vehículos asignados al conductor */
    public array $vehiculosIds = [];
    public bool $mostrarTodosVehiculos = false;
    public string $licenciaNumero = '';
    public string $licenciaCategoria = '';
    public string $licenciaVencimiento = '';
    public bool $activo = true;

    // Modal eliminar
    public ?int $deletingId = null;
    public bool $showDeleteModal = false;

    public function mount(): void
    {
        abort_unless(auth()->user()->esAdmin() || auth()->user()->esVisor(), 403);
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedFilterSucursal(): void { $this->resetPage(); }
    public function updatedFilterActivo(): void { $this->resetPage(); }

    public function clearFilters(): void
    {
        $this->reset(['search', 'filterSucursal', 'filterActivo']);
        $this->resetPage();
    }

    #[Computed]
    public function hasActiveFilters(): bool
    {
        return $this->search !== '' || $this->filterSucursal !== '' || $this->filterActivo !== '';
    }

    #[Computed]
    public function conductores(): \Illuminate\Pagination\LengthAwarePaginator
    {
        return Conductor::with(['sucursal', 'vehiculos'])
            ->withTrashed(false)
            ->when($this->search, function ($q) {
                $q->where(function ($q2) {
                    $q2->where('nombre_completo', 'like', "%{$this->search}%")
                        ->orWhere('dni', 'like', "%{$this->search}%")
                        ->orWhere('telefono', 'like', "%{$this->search}%");
                });
            })
            ->when($this->filterSucursal, fn ($q) => $q->where('sucursal_id', $this->filterSucursal))
            ->when($this->filterActivo !== '', fn ($q) => $q->where('activo', (bool) $this->filterActivo))
            ->orderBy('nombre_completo')
            ->paginate(15);
    }

    #[Computed]
    public function sucursales(): \Illuminate\Database\Eloquent\Collection
    {
        return Sucursal::activas()->orderBy('nombre')->get();
    }

    /**
     * Vehículos disponibles para asignar al conductor.
     * Muestra primero los de la sucursal del conductor, luego el resto si se activa el toggle.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Vehiculo>
     */
    #[Computed]
    public function vehiculosDisponibles(): \Illuminate\Database\Eloquent\Collection
    {
        $query = Vehiculo::with('sucursal')
            ->orderBy('placa')
            ->get(['id', 'placa', 'marca', 'modelo', 'sucursal_id', 'conductor_id']);

        if (! $this->mostrarTodosVehiculos && $this->sucursalId) {
            return $query->filter(
                fn ($v) => $v->sucursal_id == $this->sucursalId
                    || in_array($v->id, $this->vehiculosIds)
            )->values();
        }

        return $query;
    }

    public function licenciaAlertaColor(Conductor $conductor): ?string
    {
        if (! $conductor->licencia_vencimiento) {
            return null;
        }

        if ($conductor->licencia_vencimiento->isPast()) {
            return 'red';
        }

        if ($conductor->licencia_vencimiento->diffInDays(now()) <= 30) {
            return 'amber';
        }

        return null;
    }

    public function abrirCrear(): void
    {
        abort_unless(auth()->user()->esAdmin(), 403);
        $this->reset([
            'editingId', 'nombreCompleto', 'dni', 'telefono', 'email',
            'sucursalId', 'vehiculosIds', 'mostrarTodosVehiculos',
            'licenciaNumero', 'licenciaCategoria', 'licenciaVencimiento',
        ]);
        $this->activo = true;
        $this->showModal = true;
    }

    public function abrirEditar(int $id): void
    {
        abort_unless(auth()->user()->esAdmin(), 403);
        $c = Conductor::with('vehiculos')->findOrFail($id);

        $this->editingId            = $c->id;
        $this->nombreCompleto       = $c->nombre_completo;
        $this->dni                  = $c->dni;
        $this->telefono             = $c->telefono ?? '';
        $this->email                = $c->email ?? '';
        $this->sucursalId           = $c->sucursal_id ? (string) $c->sucursal_id : '';
        $this->vehiculosIds         = $c->vehiculos->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        $this->mostrarTodosVehiculos = false;
        $this->licenciaNumero       = $c->licencia_numero ?? '';
        $this->licenciaCategoria    = $c->licencia_categoria ?? '';
        $this->licenciaVencimiento  = $c->licencia_vencimiento?->format('Y-m-d') ?? '';
        $this->activo               = $c->activo;
        $this->showModal            = true;
    }

    public function guardar(): void
    {
        abort_unless(auth()->user()->esAdmin(), 403);
        $this->validate([
            'nombreCompleto'      => ['required', 'string', 'max:200'],
            'dni'                 => ['required', 'digits:8',
                \Illuminate\Validation\Rule::unique('conductores', 'dni')
                    ->ignore($this->editingId)
                    ->whereNull('deleted_at'),
            ],
            'telefono'            => ['nullable', 'string', 'max:20'],
            'email'               => ['nullable', 'email', 'max:255'],
            'sucursalId'          => ['required', 'exists:sucursales,id'],
            'vehiculosIds'        => ['nullable', 'array'],
            'vehiculosIds.*'      => ['exists:vehiculos,id'],
            'licenciaNumero'      => ['nullable', 'string', 'max:20'],
            'licenciaCategoria'   => ['nullable', 'string', 'max:10'],
            'licenciaVencimiento' => ['nullable', 'date'],
        ]);

        $data = [
            'nombre_completo'      => $this->nombreCompleto,
            'dni'                  => $this->dni,
            'telefono'             => $this->telefono ?: null,
            'email'                => $this->email ?: null,
            'sucursal_id'          => $this->sucursalId ?: null,
            'licencia_numero'      => $this->licenciaNumero ?: null,
            'licencia_categoria'   => $this->licenciaCategoria ?: null,
            'licencia_vencimiento' => $this->licenciaVencimiento ?: null,
            'activo'               => $this->activo,
        ];

        if ($this->editingId) {
            $conductor = Conductor::findOrFail($this->editingId);
            $conductor->update($data);
        } else {
            $conductor = Conductor::create($data);
        }

        // Sincronizar asignación de vehículos
        $vehiculosSeleccionados = array_map('intval', $this->vehiculosIds);

        // Desasignar vehículos que ya no corresponden a este conductor
        Vehiculo::where('conductor_id', $conductor->id)
            ->whereNotIn('id', $vehiculosSeleccionados)
            ->update([
                'conductor_id'     => null,
                'conductor_nombre' => null,
                'conductor_tel'    => null,
            ]);

        // Asignar los vehículos seleccionados
        if (! empty($vehiculosSeleccionados)) {
            Vehiculo::whereIn('id', $vehiculosSeleccionados)
                ->update([
                    'conductor_id'     => $conductor->id,
                    'conductor_nombre' => $conductor->nombre_completo,
                    'conductor_tel'    => $conductor->telefono,
                ]);
        }

        unset($this->conductores);
        $this->showModal = false;
    }

    public function confirmDelete(int $id): void
    {
        abort_unless(auth()->user()->esAdmin(), 403);
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        abort_unless(auth()->user()->esAdmin(), 403);
        $conductor = Conductor::findOrFail($this->deletingId);

        // Desasignar vehículos antes de eliminar
        Vehiculo::where('conductor_id', $conductor->id)
            ->update([
                'conductor_id'     => null,
                'conductor_nombre' => null,
                'conductor_tel'    => null,
            ]);

        $conductor->delete();

        unset($this->conductores);
        $this->deletingId = null;
        $this->showDeleteModal = false;
    }
}; ?>

<section class="w-full px-3 py-4 sm:p-6 lg:p-8">

    <x-ui.page-header
        :title="__('Conductores')"
        :subtitle="__('Gestión de conductores asignados a la flota')"
        :breadcrumbs="[
            ['label' => __('Dashboard'), 'href' => route('dashboard')],
            ['label' => __('Conductores')],
        ]"
    >
        @if (auth()->user()->esAdmin())
            <x-slot:actions>
                <flux:button variant="primary" icon="plus" wire:click="abrirCrear">
                    {{ __('Nuevo conductor') }}
                </flux:button>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    {{-- Filtros --}}
    <div class="mb-4 space-y-2 sm:space-y-0 sm:flex sm:flex-wrap sm:gap-3">
        <div class="w-full sm:flex-1 sm:min-w-48">
            <flux:input
                wire:model.live.debounce.300ms="search"
                :placeholder="__('Buscar nombre, DNI, teléfono...')"
                icon="magnifying-glass"
                clearable
            />
        </div>

        <flux:select wire:model.live="filterSucursal" class="sm:w-44">
            <flux:select.option value="">{{ __('Sucursal') }}</flux:select.option>
            @foreach ($this->sucursales as $sucursal)
                <flux:select.option :value="$sucursal->id">{{ $sucursal->nombre }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="filterActivo" class="sm:w-36">
            <flux:select.option value="">{{ __('Estado') }}</flux:select.option>
            <flux:select.option value="1">{{ __('Activo') }}</flux:select.option>
            <flux:select.option value="0">{{ __('Inactivo') }}</flux:select.option>
        </flux:select>

        @if ($this->hasActiveFilters)
            <flux:button wire:click="clearFilters" variant="ghost" size="sm" icon="x-mark" class="self-center">
                {{ __('Limpiar') }}
            </flux:button>
        @endif
    </div>

    {{-- Tabla desktop --}}
    <div class="hidden sm:block overflow-hidden rounded-xl border border-slate-200 bg-white px-2 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <flux:table :paginate="$this->conductores">
            <flux:table.columns>
                <flux:table.column>{{ __('Conductor') }}</flux:table.column>
                <flux:table.column>{{ __('DNI') }}</flux:table.column>
                <flux:table.column>{{ __('Sucursal') }}</flux:table.column>
                <flux:table.column>{{ __('Vehículos asignados') }}</flux:table.column>
                <flux:table.column>{{ __('Licencia') }}</flux:table.column>
                <flux:table.column>{{ __('Estado') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->conductores as $conductor)
                    <flux:table.row :key="$conductor->id">

                        <flux:table.cell>
                            <div>
                                <p class="font-medium text-sm">{{ $conductor->nombre_completo }}</p>
                                @if ($conductor->telefono)
                                    <p class="text-xs text-zinc-500">{{ $conductor->telefono }}</p>
                                @endif
                            </div>
                        </flux:table.cell>

                        <flux:table.cell class="font-mono-data text-sm">
                            {{ $conductor->dni }}
                        </flux:table.cell>

                        <flux:table.cell class="text-sm">
                            {{ $conductor->sucursal?->nombre ?? '—' }}
                        </flux:table.cell>

                        <flux:table.cell>
                            @if ($conductor->vehiculos->isNotEmpty())
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($conductor->vehiculos->take(3) as $v)
                                        <span class="font-mono-data text-xs font-semibold bg-slate-100 dark:bg-slate-800 rounded px-1.5 py-0.5">
                                            {{ $v->placa }}
                                        </span>
                                    @endforeach
                                    @if ($conductor->vehiculos->count() > 3)
                                        <span class="text-xs text-zinc-400">+{{ $conductor->vehiculos->count() - 3 }}</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-zinc-400 text-sm">—</span>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            @if ($conductor->licencia_vencimiento)
                                @php $alertaColor = $this->licenciaAlertaColor($conductor); @endphp
                                <div class="text-xs">
                                    @if ($alertaColor)
                                        <flux:badge :color="$alertaColor" size="sm">
                                            {{ $conductor->licencia_vencimiento->format('d/m/Y') }}
                                        </flux:badge>
                                    @else
                                        <span class="text-zinc-500">{{ $conductor->licencia_vencimiento->format('d/m/Y') }}</span>
                                    @endif
                                    @if ($conductor->licencia_categoria)
                                        <span class="block text-zinc-400 mt-0.5">{{ $conductor->licencia_categoria }}</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-zinc-400 text-sm">—</span>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:badge
                                :color="$conductor->activo ? 'green' : 'zinc'"
                                size="sm"
                            >
                                {{ $conductor->activo ? __('Activo') : __('Inactivo') }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell>
                            @if (auth()->user()->esAdmin())
                                <div class="flex gap-1">
                                    <flux:button
                                        wire:click="abrirEditar({{ $conductor->id }})"
                                        size="sm" variant="subtle" icon="pencil"
                                        inset="top bottom"
                                    />
                                    <flux:button
                                        wire:click="confirmDelete({{ $conductor->id }})"
                                        size="sm" variant="subtle" icon="trash"
                                        inset="top bottom"
                                    />
                                </div>
                            @endif
                        </flux:table.cell>

                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>

    {{-- Cards mobile --}}
    <div class="sm:hidden space-y-3">
        @foreach ($this->conductores as $conductor)
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 p-4">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-semibold text-sm">{{ $conductor->nombre_completo }}</span>
                            <flux:badge
                                :color="$conductor->activo ? 'green' : 'zinc'"
                                size="sm"
                            >
                                {{ $conductor->activo ? __('Activo') : __('Inactivo') }}
                            </flux:badge>
                        </div>
                        <p class="mt-0.5 text-xs text-zinc-500">
                            DNI: {{ $conductor->dni }}
                            @if ($conductor->telefono) · {{ $conductor->telefono }} @endif
                        </p>
                        @if ($conductor->sucursal)
                            <p class="mt-0.5 text-xs text-zinc-400">
                                <flux:icon name="building-office" class="inline size-3 mr-0.5" />
                                {{ $conductor->sucursal->nombre }}
                            </p>
                        @endif
                        @if ($conductor->vehiculos->isNotEmpty())
                            <p class="mt-0.5 text-xs text-zinc-400">
                                <flux:icon name="truck" class="inline size-3 mr-0.5" />
                                {{ $conductor->vehiculos->pluck('placa')->implode(', ') }}
                            </p>
                        @endif
                        @if ($conductor->licencia_vencimiento)
                            @php $color = $this->licenciaAlertaColor($conductor); @endphp
                            <p class="mt-1">
                                @if ($color)
                                    <flux:badge :color="$color" size="sm">
                                        Licencia: {{ $conductor->licencia_vencimiento->format('d/m/Y') }}
                                    </flux:badge>
                                @else
                                    <span class="text-xs text-zinc-400">Licencia: {{ $conductor->licencia_vencimiento->format('d/m/Y') }}</span>
                                @endif
                            </p>
                        @endif
                    </div>

                    @if (auth()->user()->esAdmin())
                        <flux:dropdown position="bottom" align="end">
                            <flux:button variant="ghost" icon="ellipsis-vertical" size="sm" />
                            <flux:menu>
                                <flux:menu.item icon="pencil" wire:click="abrirEditar({{ $conductor->id }})">
                                    {{ __('Editar') }}
                                </flux:menu.item>
                                <flux:menu.separator />
                                <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete({{ $conductor->id }})">
                                    {{ __('Eliminar') }}
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    @endif
                </div>
            </div>
        @endforeach

        @if ($this->conductores->hasPages())
            <div class="pt-2">{{ $this->conductores->links() }}</div>
        @endif
    </div>

    {{-- Vacío --}}
    @if ($this->conductores->isEmpty())
        <div class="py-16 text-center">
            <flux:icon name="identification" class="mx-auto mb-3 size-10 text-zinc-300 dark:text-zinc-600" />
            <flux:text>{{ __('No se encontraron conductores.') }}</flux:text>
        </div>
    @endif

    {{-- Modal crear / editar --}}
    <flux:modal wire:model.self="showModal" class="md:w-[44rem]">
        <div class="space-y-6">
            <flux:heading size="lg">
                {{ $editingId ? __('Editar conductor') : __('Nuevo conductor') }}
            </flux:heading>

            <form wire:submit="guardar" class="space-y-4">

                {{-- Datos personales --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:input
                        wire:model="nombreCompleto"
                        :label="__('Nombre completo')"
                        :placeholder="__('Ej: Juan Pérez López')"
                        required
                    />
                    <flux:input
                        wire:model="dni"
                        :label="__('DNI')"
                        placeholder="12345678"
                        maxlength="8"
                        required
                    />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:input
                        wire:model="telefono"
                        :label="__('Teléfono (opcional)')"
                        :placeholder="__('Ej: 987654321')"
                    />
                    <flux:input
                        wire:model="email"
                        :label="__('Email (opcional)')"
                        type="email"
                        :placeholder="__('conductor@ejemplo.com')"
                    />
                </div>

                {{-- Sucursal --}}
                <flux:select wire:model.live="sucursalId" :label="__('Sucursal')" required>
                    <flux:select.option value="">{{ __('Seleccionar') }}</flux:select.option>
                    @foreach ($this->sucursales as $sucursal)
                        <flux:select.option :value="$sucursal->id">{{ $sucursal->nombre }}</flux:select.option>
                    @endforeach
                </flux:select>

                {{-- Vehículos asignados --}}
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            {{ __('Vehículos asignados') }}
                            @if (count($vehiculosIds) > 0)
                                <flux:badge color="blue" size="sm" class="ml-1">{{ count($vehiculosIds) }}</flux:badge>
                            @endif
                        </p>
                        @if ($sucursalId)
                            <button
                                type="button"
                                wire:click="$toggle('mostrarTodosVehiculos')"
                                class="text-xs text-brand-600 hover:text-brand-800 dark:text-brand-400 dark:hover:text-brand-300 underline"
                            >
                                {{ $mostrarTodosVehiculos ? __('Solo mi sucursal') : __('Ver todas las sucursales') }}
                            </button>
                        @endif
                    </div>

                    @if ($this->vehiculosDisponibles->isEmpty())
                        <p class="text-sm text-zinc-400 italic">
                            {{ $sucursalId ? __('No hay vehículos registrados en esta sucursal.') : __('Selecciona una sucursal primero.') }}
                        </p>
                    @else
                        <div class="max-h-48 overflow-y-auto rounded-lg border border-zinc-200 dark:border-zinc-700 divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach ($this->vehiculosDisponibles as $vehiculo)
                                @php
                                    $asignadoOtro = $vehiculo->conductor_id && $vehiculo->conductor_id != $editingId;
                                @endphp
                                <label
                                    class="flex items-center gap-3 px-3 py-2.5 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors {{ $asignadoOtro ? 'opacity-60' : '' }}"
                                >
                                    <input
                                        type="checkbox"
                                        wire:model="vehiculosIds"
                                        value="{{ $vehiculo->id }}"
                                        class="size-4 rounded border-zinc-300 text-brand-600 focus:ring-brand-500"
                                    />
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="font-mono-data text-sm font-semibold">{{ $vehiculo->placa }}</span>
                                            <span class="text-xs text-zinc-500">{{ $vehiculo->marca }} {{ $vehiculo->modelo }}</span>
                                            @if ($vehiculo->sucursal_id != $sucursalId)
                                                <flux:badge color="zinc" size="sm">{{ $vehiculo->sucursal?->nombre }}</flux:badge>
                                            @endif
                                        </div>
                                        @if ($asignadoOtro)
                                            <p class="text-xs text-amber-500 mt-0.5">
                                                {{ __('Asignado a otro conductor') }}
                                            </p>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Licencia --}}
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4 space-y-4">
                    <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Licencia de conducir') }}</p>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <flux:input
                            wire:model="licenciaNumero"
                            :label="__('N° licencia')"
                            :placeholder="__('Q12345678')"
                        />
                        <flux:input
                            wire:model="licenciaCategoria"
                            :label="__('Categoría')"
                            :placeholder="__('Ej: A-IIb, B-IIb')"
                        />
                        <flux:input
                            wire:model="licenciaVencimiento"
                            :label="__('Vencimiento')"
                            type="date"
                        />
                    </div>
                </div>

                {{-- Estado --}}
                <flux:checkbox wire:model="activo" :label="__('Conductor activo')" />

                <div class="flex justify-end gap-2 pt-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">{{ __('Cancelar') }}</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="guardar">
                            {{ $editingId ? __('Guardar cambios') : __('Crear conductor') }}
                        </span>
                        <span wire:loading wire:target="guardar">{{ __('Guardando...') }}</span>
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- Modal eliminar --}}
    <flux:modal wire:model.self="showDeleteModal" class="md:w-80">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Eliminar conductor') }}</flux:heading>
                <flux:text class="mt-2">{{ __('¿Eliminar este conductor? Esta acción no se puede deshacer.') }}</flux:text>
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

</section>
