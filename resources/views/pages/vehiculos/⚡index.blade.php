<?php

use App\Models\Sucursal;
use App\Models\Vehiculo;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Vehículos')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $filterEstado = '';

    #[Url]
    public string $filterTipo = '';

    #[Url]
    public string $filterSucursal = '';

    public ?int $deletingId = null;
    public bool $showDeleteModal = false;

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedFilterEstado(): void { $this->resetPage(); }
    public function updatedFilterTipo(): void { $this->resetPage(); }
    public function updatedFilterSucursal(): void { $this->resetPage(); }

    public function clearFilters(): void
    {
        $this->reset(['search', 'filterEstado', 'filterTipo', 'filterSucursal']);
        $this->resetPage();
    }

    #[Computed]
    public function hasActiveFilters(): bool
    {
        return $this->search !== '' || $this->filterEstado !== '' || $this->filterTipo !== '' || $this->filterSucursal !== '';
    }

    #[Computed]
    public function vehiculos(): \Illuminate\Pagination\LengthAwarePaginator
    {
        return Vehiculo::with(['sucursal', 'conductor'])
            ->forUser(auth()->user())
            ->when($this->search, fn ($q) => $q->search($this->search))
            ->when($this->filterEstado, fn ($q) => $q->where('estado', $this->filterEstado))
            ->when($this->filterTipo, fn ($q) => $q->where('tipo', $this->filterTipo))
            ->when($this->filterSucursal && auth()->user()->puedeVerTodo(), fn ($q) => $q->where('sucursal_id', $this->filterSucursal))
            ->orderByDesc('created_at')
            ->paginate(15);
    }

    #[Computed]
    public function sucursales(): \Illuminate\Database\Eloquent\Collection
    {
        return Sucursal::activas()->orderBy('nombre')->get();
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
        Vehiculo::findOrFail($this->deletingId)->delete();
        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    public function estadoBadgeColor(string $estado): string
    {
        return match ($estado) {
            'operativo' => 'green',
            'parcialmente' => 'amber',
            'fuera_de_servicio' => 'red',
            default => 'zinc',
        };
    }

    public function estadoLabel(string $estado): string
    {
        return match ($estado) {
            'operativo' => 'Operativo',
            'parcialmente' => 'Parcial',
            'fuera_de_servicio' => 'Fuera de servicio',
            default => $estado,
        };
    }

    public function tipoLabel(string $tipo): string
    {
        return match ($tipo) {
            'moto' => 'Moto',
            'auto' => 'Auto',
            'camioneta' => 'Camioneta',
            'minivan' => 'Minivan',
            'furgon' => 'Furgón',
            'bus' => 'Bus',
            'vehiculo_pesado' => 'Veh. pesado',
            default => $tipo,
        };
    }
}; ?>

<section class="w-full px-3 py-4 sm:p-6 lg:p-8">

    <x-ui.page-header
        :title="__('Vehículos')"
        :subtitle="__('Gestiona la flota de vehículos')"
        :breadcrumbs="[
            ['label' => __('Dashboard'), 'href' => route('dashboard')],
            ['label' => __('Vehículos')],
        ]"
    >
        @if (auth()->user()->esAdmin())
            <x-slot:actions>
                <flux:button :href="route('vehiculos.crear')" variant="primary" icon="plus" wire:navigate>
                    {{ __('Nuevo vehículo') }}
                </flux:button>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    {{-- Filtros --}}
    <div class="mb-4 space-y-2 sm:space-y-0 sm:flex sm:flex-wrap sm:gap-3">
        <div class="w-full sm:flex-1 sm:min-w-48">
            <flux:input
                wire:model.live.debounce.300ms="search"
                :placeholder="__('Buscar placa, marca, modelo...')"
                icon="magnifying-glass"
                clearable
            />
        </div>

        <div class="grid grid-cols-2 gap-2 sm:contents">
            <flux:select wire:model.live="filterEstado" class="sm:w-40">
                <flux:select.option value="">{{ __('Estado') }}</flux:select.option>
                <flux:select.option value="operativo">{{ __('Operativo') }}</flux:select.option>
                <flux:select.option value="parcialmente">{{ __('Parcial') }}</flux:select.option>
                <flux:select.option value="fuera_de_servicio">{{ __('Fuera de servicio') }}</flux:select.option>
            </flux:select>

            <flux:select wire:model.live="filterTipo" class="sm:w-44">
                <flux:select.option value="">{{ __('Tipo') }}</flux:select.option>
                <flux:select.option value="moto">{{ __('Moto') }}</flux:select.option>
                <flux:select.option value="auto">{{ __('Auto') }}</flux:select.option>
                <flux:select.option value="camioneta">{{ __('Camioneta') }}</flux:select.option>
                <flux:select.option value="minivan">{{ __('Minivan') }}</flux:select.option>
                <flux:select.option value="furgon">{{ __('Furgón') }}</flux:select.option>
                <flux:select.option value="bus">{{ __('Bus') }}</flux:select.option>
                <flux:select.option value="vehiculo_pesado">{{ __('Veh. pesado') }}</flux:select.option>
            </flux:select>
        </div>

        @if (auth()->user()->puedeVerTodo())
            <flux:select wire:model.live="filterSucursal" class="w-full sm:w-44">
                <flux:select.option value="">{{ __('Sucursal') }}</flux:select.option>
                @foreach ($this->sucursales as $sucursal)
                    <flux:select.option :value="$sucursal->id">{{ $sucursal->nombre }}</flux:select.option>
                @endforeach
            </flux:select>
        @endif

        @if ($this->hasActiveFilters)
            <flux:button wire:click="clearFilters" variant="ghost" size="sm" icon="x-mark" class="self-center">
                {{ __('Limpiar') }}
            </flux:button>
        @endif
    </div>

    {{-- Tabla desktop --}}
    <div class="hidden sm:block overflow-hidden rounded-xl border border-slate-200 bg-white px-2 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <flux:table :paginate="$this->vehiculos">
            <flux:table.columns>
                <flux:table.column>{{ __('Vehículo') }}</flux:table.column>
                <flux:table.column>{{ __('Tipo') }}</flux:table.column>
                @if (auth()->user()->puedeVerTodo())
                    <flux:table.column>{{ __('Sucursal') }}</flux:table.column>
                @endif
                <flux:table.column>{{ __('Conductor') }}</flux:table.column>
                <flux:table.column>{{ __('Estado') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->vehiculos as $vehiculo)
                    <flux:table.row :key="$vehiculo->id">
                        <flux:table.cell>
                            <div>
                                <a href="{{ route('vehiculos.show', $vehiculo) }}" wire:navigate class="font-mono-data text-sm font-semibold text-slate-900 hover:text-brand-600 dark:text-white dark:hover:text-brand-400 transition-colors">{{ $vehiculo->placa }}</a>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $vehiculo->marca }} {{ $vehiculo->modelo }} · {{ $vehiculo->anio }}</p>
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <span class="inline-flex rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                {{ $this->tipoLabel($vehiculo->tipo) }}
                            </span>
                        </flux:table.cell>

                        @if (auth()->user()->puedeVerTodo())
                            <flux:table.cell class="text-sm text-slate-600 dark:text-slate-300">{{ $vehiculo->sucursal?->nombre ?? '—' }}</flux:table.cell>
                        @endif

                        <flux:table.cell class="text-sm text-slate-600 dark:text-slate-300">{{ $vehiculo->conductor?->nombre_completo ?? '—' }}</flux:table.cell>

                        <flux:table.cell>
                            <x-ui.badge-status :status="$vehiculo->estado" :label="$this->estadoLabel($vehiculo->estado)" />
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="flex justify-end gap-1">
                                <flux:button
                                    :href="route('vehiculos.show', $vehiculo)"
                                    size="sm" variant="subtle" icon="eye"
                                    inset="top bottom"
                                    wire:navigate
                                    class="!text-sky-500 hover:!text-sky-700 dark:!text-sky-400 dark:hover:!text-sky-300"
                                />
                                @if (auth()->user()->esAdmin())
                                    <flux:button
                                        :href="route('vehiculos.editar', $vehiculo)"
                                        size="sm" variant="subtle" icon="pencil"
                                        inset="top bottom"
                                        wire:navigate
                                        class="!text-emerald-500 hover:!text-emerald-700 dark:!text-emerald-400 dark:hover:!text-emerald-300"
                                    />
                                    <flux:button
                                        wire:click="confirmDelete({{ $vehiculo->id }})"
                                        size="sm" variant="subtle" icon="trash"
                                        inset="top bottom"
                                        class="!text-red-500 hover:!text-red-700 dark:!text-red-400 dark:hover:!text-red-300"
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
        @foreach ($this->vehiculos as $vehiculo)
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <a href="{{ route('vehiculos.show', $vehiculo) }}" wire:navigate class="font-mono-data text-base font-bold text-slate-900 hover:text-brand-600 dark:text-white dark:hover:text-brand-400 transition-colors">{{ $vehiculo->placa }}</a>
                            <x-ui.badge-status :status="$vehiculo->estado" :label="$this->estadoLabel($vehiculo->estado)" />
                        </div>
                        <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
                            {{ $vehiculo->marca }} {{ $vehiculo->modelo }} · {{ $vehiculo->anio }}
                        </p>
                        @if ($vehiculo->conductor)
                            <p class="mt-0.5 text-xs text-slate-400 dark:text-slate-500">
                                <flux:icon name="user" class="inline size-3 mr-0.5" />
                                {{ $vehiculo->conductor->nombre_completo }}
                            </p>
                        @endif
                        @if (auth()->user()->puedeVerTodo() && $vehiculo->sucursal)
                            <p class="mt-0.5 text-xs text-slate-400 dark:text-slate-500">
                                <flux:icon name="building-office" class="inline size-3 mr-0.5" />
                                {{ $vehiculo->sucursal->nombre }}
                            </p>
                        @endif
                    </div>

                    <div class="flex shrink-0 gap-1">
                        <flux:button
                            :href="route('vehiculos.show', $vehiculo)"
                            size="sm" variant="subtle" icon="eye"
                            inset="top bottom"
                            wire:navigate
                            class="!text-sky-500 hover:!text-sky-700 dark:!text-sky-400 dark:hover:!text-sky-300"
                        />
                        @if (auth()->user()->esAdmin())
                            <flux:button
                                :href="route('vehiculos.editar', $vehiculo)"
                                size="sm" variant="subtle" icon="pencil"
                                inset="top bottom"
                                wire:navigate
                                class="!text-emerald-500 hover:!text-emerald-700 dark:!text-emerald-400 dark:hover:!text-emerald-300"
                            />
                            <flux:button
                                wire:click="confirmDelete({{ $vehiculo->id }})"
                                size="sm" variant="subtle" icon="trash"
                                inset="top bottom"
                                class="!text-red-500 hover:!text-red-700 dark:!text-red-400 dark:hover:!text-red-300"
                            />
                        @endif
                    </div>
                </div>
            </div>
        @endforeach

        {{-- Paginación mobile --}}
        @if ($this->vehiculos->hasPages())
            <div class="pt-2">
                {{ $this->vehiculos->links() }}
            </div>
        @endif
    </div>

    {{-- Vacío --}}
    @if ($this->vehiculos->isEmpty())
        <div class="mt-4 rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
            <x-ui.empty-state
                icon="truck"
                :title="__('No se encontraron vehículos')"
                :description="__('Ajusta los filtros o crea un nuevo vehículo.')"
            />
        </div>
    @endif

    {{-- Modal eliminar --}}
    <flux:modal wire:model.self="showDeleteModal" class="md:w-80">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Eliminar vehículo') }}</flux:heading>
                <flux:text class="mt-2">{{ __('¿Estás seguro? Esta acción no se puede deshacer.') }}</flux:text>
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
