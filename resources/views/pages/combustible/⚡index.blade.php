<?php

use App\Models\RegistroCombustible;
use App\Models\Sucursal;
use App\Models\Vehiculo;
use App\Services\StorageService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

new #[Title('Combustible')] class extends Component {
    use WithPagination, WithFileUploads;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $filterEstado = '';

    #[Url]
    public string $filterSucursal = '';

    // Modal eliminar
    public bool $showDeleteModal = false;
    public ?int $deletingId = null;

    // Modal crear
    public bool $showCrearModal = false;
    public int|string $vehiculoId = '';
    public ?TemporaryUploadedFile $fotoFactura = null;
    public ?TemporaryUploadedFile $fotoOdometro = null;
    public string $observacionesEnvio = '';

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedFilterEstado(): void { $this->resetPage(); }
    public function updatedFilterSucursal(): void { $this->resetPage(); }

    public function clearFilters(): void
    {
        $this->reset(['search', 'filterEstado', 'filterSucursal']);
        $this->resetPage();
    }

    #[Computed]
    public function hasActiveFilters(): bool
    {
        return $this->search !== '' || $this->filterEstado !== '' || $this->filterSucursal !== '';
    }

    #[Computed]
    public function registros(): \Illuminate\Pagination\LengthAwarePaginator
    {
        return RegistroCombustible::with(['vehiculo', 'sucursal', 'enviadoPor'])
            ->forUser(auth()->user())
            ->when($this->search, fn ($q) => $q->whereHas(
                'vehiculo',
                fn ($v) => $v->where('placa', 'like', "%{$this->search}%")
                    ->orWhere('marca', 'like', "%{$this->search}%")
            ))
            ->when($this->filterEstado, fn ($q) => $q->where('estado', $this->filterEstado))
            ->when(
                $this->filterSucursal && auth()->user()->puedeVerTodo(),
                fn ($q) => $q->where('sucursal_id', $this->filterSucursal)
            )
            ->orderByDesc('created_at')
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
        return Vehiculo::forUser(auth()->user())
            ->orderBy('placa')
            ->get(['id', 'placa', 'marca', 'modelo', 'sucursal_id']);
    }

    public function abrirCrear(): void
    {
        abort_unless(
            auth()->user()->esAdmin() || auth()->user()->esJefeResguardo(),
            403
        );
        $this->reset(['vehiculoId', 'fotoFactura', 'fotoOdometro', 'observacionesEnvio']);
        $this->showCrearModal = true;
    }

    public function guardar(StorageService $storage): void
    {
        abort_unless(
            auth()->user()->esAdmin() || auth()->user()->esJefeResguardo(),
            403
        );

        $this->validate([
            'vehiculoId'       => ['required', 'exists:vehiculos,id'],
            'fotoFactura'      => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:20480'],
            'fotoOdometro'     => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:10240'],
            'observacionesEnvio' => ['nullable', 'string', 'max:1000'],
        ]);

        $vehiculo = Vehiculo::findOrFail($this->vehiculoId);

        // Jefe_resguardo solo puede registrar su propia sucursal
        if (auth()->user()->esJefeResguardo()) {
            abort_unless($vehiculo->sucursal_id === auth()->user()->sucursal_id, 403);
        }

        $facturaKey  = $storage->upload($this->fotoFactura, "combustible/{$vehiculo->id}/facturas");
        $odometroKey = $storage->upload($this->fotoOdometro, "combustible/{$vehiculo->id}/odometros");

        RegistroCombustible::create([
            'vehiculo_id'         => $vehiculo->id,
            'sucursal_id'         => $vehiculo->sucursal_id,
            'enviado_por'         => auth()->id(),
            'foto_factura_key'    => $facturaKey,
            'foto_odometro_key'   => $odometroKey,
            'observaciones_envio' => $this->observacionesEnvio ?: null,
            'estado'              => 'pendiente',
        ]);

        unset($this->registros);
        $this->showCrearModal = false;
    }

    public function confirmDelete(int $id): void
    {
        abort_unless(auth()->user()->esAdmin(), 403);
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(StorageService $storage): void
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $registro = RegistroCombustible::findOrFail($this->deletingId);
        $storage->delete($registro->foto_factura_key);
        $storage->delete($registro->foto_odometro_key);
        $registro->delete();

        $this->showDeleteModal = false;
        $this->deletingId = null;
        unset($this->registros);
    }

    public function estadoBadgeColor(string $estado): string
    {
        return match ($estado) {
            'pendiente'  => 'amber',
            'aprobado'   => 'green',
            'rechazado'  => 'red',
            default      => 'zinc',
        };
    }

    public function estadoLabel(string $estado): string
    {
        return match ($estado) {
            'pendiente' => 'Pendiente',
            'aprobado'  => 'Aprobado',
            'rechazado' => 'Rechazado',
            default     => $estado,
        };
    }
}; ?>

<section class="w-full px-3 py-4 sm:p-6 lg:p-8">

    <x-ui.page-header
        :title="__('Combustible')"
        :subtitle="__('Registro de cargas de combustible')"
        :breadcrumbs="[
            ['label' => __('Dashboard'), 'href' => route('dashboard')],
            ['label' => __('Combustible')],
        ]"
    >
        @if (auth()->user()->esAdmin() || auth()->user()->esJefeResguardo())
            <x-slot:actions>
                <flux:button variant="primary" icon="plus" wire:click="abrirCrear">
                    {{ __('Nueva carga') }}
                </flux:button>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    {{-- Filtros --}}
    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:gap-3">
        <div class="w-full sm:flex-1 sm:min-w-48">
            <flux:input
                wire:model.live.debounce.300ms="search"
                :placeholder="__('Buscar placa, marca...')"
                icon="magnifying-glass"
                clearable
            />
        </div>

        <div class="grid grid-cols-2 gap-2 sm:contents">
            <flux:select wire:model.live="filterEstado" class="sm:w-40">
                <flux:select.option value="">{{ __('Estado') }}</flux:select.option>
                <flux:select.option value="pendiente">{{ __('Pendiente') }}</flux:select.option>
                <flux:select.option value="aprobado">{{ __('Aprobado') }}</flux:select.option>
                <flux:select.option value="rechazado">{{ __('Rechazado') }}</flux:select.option>
            </flux:select>

            @if (auth()->user()->puedeVerTodo())
                <flux:select wire:model.live="filterSucursal" class="sm:w-44">
                    <flux:select.option value="">{{ __('Sucursal') }}</flux:select.option>
                    @foreach ($this->sucursales as $sucursal)
                        <flux:select.option :value="$sucursal->id">{{ $sucursal->nombre }}</flux:select.option>
                    @endforeach
                </flux:select>
            @endif
        </div>

        @if ($this->hasActiveFilters)
            <flux:button wire:click="clearFilters" variant="ghost" size="sm" icon="x-mark" class="self-center">
                {{ __('Limpiar') }}
            </flux:button>
        @endif
    </div>

    {{-- Tabla desktop --}}
    <div class="hidden sm:block overflow-hidden rounded-xl border border-slate-200 bg-white px-2 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <flux:table :paginate="$this->registros">
            <flux:table.columns>
                <flux:table.column>{{ __('Vehículo') }}</flux:table.column>
                @if (auth()->user()->puedeVerTodo())
                    <flux:table.column>{{ __('Sucursal') }}</flux:table.column>
                @endif
                <flux:table.column>{{ __('Enviado por') }}</flux:table.column>
                <flux:table.column>{{ __('Fecha') }}</flux:table.column>
                <flux:table.column>{{ __('Estado') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->registros as $registro)
                    <flux:table.row :key="$registro->id">
                        <flux:table.cell>
                            <div>
                                <p class="font-mono-data text-sm font-semibold text-slate-900 dark:text-white">{{ $registro->vehiculo?->placa ?? '—' }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    {{ $registro->vehiculo?->marca }} {{ $registro->vehiculo?->modelo }}
                                </p>
                            </div>
                        </flux:table.cell>

                        @if (auth()->user()->puedeVerTodo())
                            <flux:table.cell class="text-sm text-slate-600 dark:text-slate-300">{{ $registro->sucursal?->nombre ?? '—' }}</flux:table.cell>
                        @endif

                        <flux:table.cell class="text-sm text-slate-600 dark:text-slate-300">{{ $registro->enviadoPor?->name ?? '—' }}</flux:table.cell>

                        <flux:table.cell class="font-mono-data text-sm text-slate-500 dark:text-slate-400">
                            {{ $registro->created_at->format('d/m/Y') }}
                        </flux:table.cell>

                        <flux:table.cell>
                            <x-ui.badge-status :status="$registro->estado" :label="$this->estadoLabel($registro->estado)" />
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="flex items-center gap-1">
                                <flux:button
                                    :href="route('combustible.show', $registro)"
                                    size="sm" variant="subtle" icon="eye"
                                    inset="top bottom"
                                    wire:navigate
                                />
                                @if (auth()->user()->esAdmin())
                                    <flux:button
                                        size="sm" variant="subtle" icon="trash"
                                        inset="top bottom"
                                        wire:click="confirmDelete({{ $registro->id }})"
                                        class="text-red-500 hover:text-red-600 dark:text-red-400"
                                    />
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
        @foreach ($this->registros as $registro)
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <a
                    href="{{ route('combustible.show', $registro) }}"
                    wire:navigate
                    class="block p-4 transition-colors hover:border-brand-300 dark:hover:border-brand-700"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-mono-data text-base font-bold text-slate-900 dark:text-white">
                                    {{ $registro->vehiculo?->placa ?? '—' }}
                                </span>
                                <x-ui.badge-status :status="$registro->estado" :label="$this->estadoLabel($registro->estado)" />
                            </div>
                            <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
                                {{ $registro->vehiculo?->marca }} {{ $registro->vehiculo?->modelo }}
                            </p>
                            <p class="mt-0.5 text-xs text-slate-400 dark:text-slate-500">
                                {{ $registro->enviadoPor?->name }} · {{ $registro->created_at->format('d/m/Y') }}
                            </p>
                            @if (auth()->user()->puedeVerTodo() && $registro->sucursal)
                                <p class="mt-0.5 text-xs text-slate-400 dark:text-slate-500">
                                    <flux:icon name="building-office" class="inline size-3 mr-0.5" />
                                    {{ $registro->sucursal->nombre }}
                                </p>
                            @endif
                        </div>
                        <flux:icon name="chevron-right" class="size-5 shrink-0 text-slate-400 mt-0.5" />
                    </div>
                </a>
                @if (auth()->user()->esAdmin())
                    <div class="border-t border-slate-100 px-4 py-2 dark:border-slate-800">
                        <flux:button
                            size="sm" variant="ghost" icon="trash"
                            wire:click="confirmDelete({{ $registro->id }})"
                            class="text-red-500 hover:text-red-600 dark:text-red-400"
                        >
                            {{ __('Eliminar') }}
                        </flux:button>
                    </div>
                @endif
            </div>
        @endforeach

        @if ($this->registros->hasPages())
            <div class="pt-2">
                {{ $this->registros->links() }}
            </div>
        @endif
    </div>

    {{-- Vacío --}}
    @if ($this->registros->isEmpty())
        <div class="mt-4 rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
            <x-ui.empty-state
                icon="fire"
                :title="__('No se encontraron registros')"
                :description="__('Ajusta los filtros o registra una nueva carga.')"
            />
        </div>
    @endif

    {{-- Modal: Confirmar eliminación --}}
    <flux:modal wire:model.self="showDeleteModal" class="md:w-80">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Eliminar registro') }}</flux:heading>
                <flux:text class="mt-2">{{ __('¿Estás seguro? Se eliminarán también las fotos adjuntas. Esta acción no se puede deshacer.') }}</flux:text>
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

    {{-- Modal: Nueva carga --}}
    <flux:modal wire:model.self="showCrearModal" class="md:w-[36rem]">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Registrar carga de combustible') }}</flux:heading>

            <form wire:submit="guardar" class="space-y-4">

                <flux:select wire:model="vehiculoId" :label="__('Vehículo')" required>
                    <flux:select.option value="">{{ __('Seleccionar vehículo') }}</flux:select.option>
                    @foreach ($this->vehiculosDisponibles as $v)
                        <flux:select.option :value="$v->id">
                            {{ $v->placa }} — {{ $v->marca }} {{ $v->modelo }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>
                            {{ __('Foto de factura') }}
                            <span class="text-zinc-400 font-normal text-xs">(JPG, PNG, PDF — máx. 20 MB)</span>
                        </flux:label>
                        <input
                            type="file"
                            wire:model="fotoFactura"
                            accept=".jpg,.jpeg,.png,.pdf"
                            class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-700 file:mr-3 file:cursor-pointer file:rounded file:border-0 file:bg-zinc-100 file:px-3 file:py-1 file:text-sm file:font-medium file:text-zinc-700 hover:file:bg-zinc-200 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 dark:file:bg-zinc-700 dark:file:text-zinc-300"
                        />
                        <flux:error name="fotoFactura" />
                    </flux:field>

                    <flux:field>
                        <flux:label>
                            {{ __('Foto de odómetro') }}
                            <span class="text-zinc-400 font-normal text-xs">(JPG, PNG — máx. 10 MB)</span>
                        </flux:label>
                        <input
                            type="file"
                            wire:model="fotoOdometro"
                            accept=".jpg,.jpeg,.png"
                            class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-700 file:mr-3 file:cursor-pointer file:rounded file:border-0 file:bg-zinc-100 file:px-3 file:py-1 file:text-sm file:font-medium file:text-zinc-700 hover:file:bg-zinc-200 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 dark:file:bg-zinc-700 dark:file:text-zinc-300"
                        />
                        <flux:error name="fotoOdometro" />
                    </flux:field>
                </div>

                <flux:textarea
                    wire:model="observacionesEnvio"
                    :label="__('Observaciones (opcional)')"
                    rows="2"
                    :placeholder="__('Estación de servicio, detalles, etc.')"
                />

                <div class="flex justify-end gap-2 pt-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">{{ __('Cancelar') }}</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="guardar">{{ __('Registrar') }}</span>
                        <span wire:loading wire:target="guardar">{{ __('Subiendo...') }}</span>
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

</section>
