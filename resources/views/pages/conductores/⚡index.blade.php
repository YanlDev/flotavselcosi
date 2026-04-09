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
    public string $vehiculoId = '';
    public string $licenciaNumero = '';
    public string $licenciaCategoria = '';
    public string $licenciaVencimiento = '';
    public bool $activo = true;

    // Modal eliminar
    public ?int $deletingId = null;
    public bool $showDeleteModal = false;

    public function mount(): void
    {
        abort_unless(auth()->user()->esAdmin(), 403);
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedFilterSucursal(): void { $this->resetPage(); }
    public function updatedFilterActivo(): void { $this->resetPage(); }

    #[Computed]
    public function conductores(): \Illuminate\Pagination\LengthAwarePaginator
    {
        return Conductor::with(['sucursal', 'vehiculo'])
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

    #[Computed]
    public function vehiculosDisponibles(): \Illuminate\Database\Eloquent\Collection
    {
        return Vehiculo::orderBy('placa')
            ->get(['id', 'placa', 'marca', 'modelo', 'sucursal_id']);
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
        $this->reset([
            'editingId', 'nombreCompleto', 'dni', 'telefono', 'email',
            'sucursalId', 'vehiculoId', 'licenciaNumero', 'licenciaCategoria',
            'licenciaVencimiento',
        ]);
        $this->activo = true;
        $this->showModal = true;
    }

    public function abrirEditar(int $id): void
    {
        $c = Conductor::findOrFail($id);

        $this->editingId            = $c->id;
        $this->nombreCompleto       = $c->nombre_completo;
        $this->dni                  = $c->dni;
        $this->telefono             = $c->telefono ?? '';
        $this->email                = $c->email ?? '';
        $this->sucursalId           = $c->sucursal_id ? (string) $c->sucursal_id : '';
        $this->vehiculoId           = $c->vehiculo_id ? (string) $c->vehiculo_id : '';
        $this->licenciaNumero       = $c->licencia_numero ?? '';
        $this->licenciaCategoria    = $c->licencia_categoria ?? '';
        $this->licenciaVencimiento  = $c->licencia_vencimiento?->format('Y-m-d') ?? '';
        $this->activo               = $c->activo;
        $this->showModal            = true;
    }

    public function guardar(): void
    {
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
            'vehiculoId'          => ['nullable', 'exists:vehiculos,id'],
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
            'vehiculo_id'          => $this->vehiculoId ?: null,
            'licencia_numero'      => $this->licenciaNumero ?: null,
            'licencia_categoria'   => $this->licenciaCategoria ?: null,
            'licencia_vencimiento' => $this->licenciaVencimiento ?: null,
            'activo'               => $this->activo,
        ];

        if ($this->editingId) {
            Conductor::findOrFail($this->editingId)->update($data);
        } else {
            Conductor::create($data);
        }

        unset($this->conductores);
        $this->showModal = false;
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        Conductor::findOrFail($this->deletingId)->delete();

        unset($this->conductores);
        $this->deletingId = null;
        $this->showDeleteModal = false;
    }
}; ?>

<section class="w-full">

    {{-- Encabezado --}}
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Conductores') }}</flux:heading>
            <flux:text class="hidden sm:block">{{ __('Gestión de conductores asignados a la flota.') }}</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="abrirCrear">
            <span class="hidden sm:inline">{{ __('Nuevo conductor') }}</span>
            <span class="sm:hidden">{{ __('Nuevo') }}</span>
        </flux:button>
    </div>

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
    </div>

    {{-- Tabla desktop --}}
    <div class="hidden sm:block overflow-x-auto">
        <flux:table :paginate="$this->conductores">
            <flux:table.columns>
                <flux:table.column>{{ __('Conductor') }}</flux:table.column>
                <flux:table.column>{{ __('DNI') }}</flux:table.column>
                <flux:table.column>{{ __('Sucursal') }}</flux:table.column>
                <flux:table.column>{{ __('Vehículo asignado') }}</flux:table.column>
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

                        <flux:table.cell class="font-mono text-sm">
                            {{ $conductor->dni }}
                        </flux:table.cell>

                        <flux:table.cell class="text-sm">
                            {{ $conductor->sucursal?->nombre ?? '—' }}
                        </flux:table.cell>

                        <flux:table.cell>
                            @if ($conductor->vehiculo)
                                <span class="font-mono text-sm font-semibold">{{ $conductor->vehiculo->placa }}</span>
                                <span class="text-xs text-zinc-500 ml-1">
                                    {{ $conductor->vehiculo->marca }} {{ $conductor->vehiculo->modelo }}
                                </span>
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
                        </flux:table.cell>

                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>

    {{-- Cards mobile --}}
    <div class="sm:hidden space-y-3">
        @foreach ($this->conductores as $conductor)
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 p-4">
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
                        @if ($conductor->vehiculo)
                            <p class="mt-0.5 text-xs text-zinc-400">
                                <flux:icon name="truck" class="inline size-3 mr-0.5" />
                                {{ $conductor->vehiculo->placa }} — {{ $conductor->vehiculo->marca }} {{ $conductor->vehiculo->modelo }}
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

                {{-- Asignación --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:select wire:model="sucursalId" :label="__('Sucursal')" required>
                        <flux:select.option value="">{{ __('Seleccionar') }}</flux:select.option>
                        @foreach ($this->sucursales as $sucursal)
                            <flux:select.option :value="$sucursal->id">{{ $sucursal->nombre }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="vehiculoId" :label="__('Vehículo asignado (opcional)')">
                        <flux:select.option value="">{{ __('Sin asignar') }}</flux:select.option>
                        @foreach ($this->vehiculosDisponibles as $v)
                            <flux:select.option :value="$v->id">
                                {{ $v->placa }} — {{ $v->marca }} {{ $v->modelo }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
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
