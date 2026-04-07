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

    #[Computed]
    public function vehiculos(): \Illuminate\Pagination\LengthAwarePaginator
    {
        return Vehiculo::with('sucursal')
            ->forUser(auth()->user())
            ->when($this->search, fn ($q) => $q->search($this->search))
            ->when($this->filterEstado, fn ($q) => $q->where('estado', $this->filterEstado))
            ->when($this->filterTipo, fn ($q) => $q->where('tipo', $this->filterTipo))
            ->when($this->filterSucursal && auth()->user()->esAdmin(), fn ($q) => $q->where('sucursal_id', $this->filterSucursal))
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
            'vehiculo_pesado' => 'Vehículo pesado',
            default => $tipo,
        };
    }
}; ?>

<section class="w-full">
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl">{{ __('Vehículos') }}</flux:heading>
            <flux:text>{{ __('Gestiona la flota de vehículos.') }}</flux:text>
        </div>
        @if (auth()->user()->esAdmin())
            <flux:button :href="route('vehiculos.crear')" variant="primary" icon="plus" wire:navigate>
                {{ __('Nuevo vehículo') }}
            </flux:button>
        @endif
    </div>

    {{-- Filtros --}}
    <div class="flex flex-wrap gap-3 mb-4">
        <div class="flex-1 min-w-48">
            <flux:input
                wire:model.live.debounce.300ms="search"
                :placeholder="__('Buscar placa, marca, modelo...')"
                icon="magnifying-glass"
                clearable
            />
        </div>

        <flux:select wire:model.live="filterEstado" class="w-40">
            <flux:select.option value="">{{ __('Todos los estados') }}</flux:select.option>
            <flux:select.option value="operativo">{{ __('Operativo') }}</flux:select.option>
            <flux:select.option value="parcialmente">{{ __('Parcial') }}</flux:select.option>
            <flux:select.option value="fuera_de_servicio">{{ __('Fuera de servicio') }}</flux:select.option>
        </flux:select>

        <flux:select wire:model.live="filterTipo" class="w-44">
            <flux:select.option value="">{{ __('Todos los tipos') }}</flux:select.option>
            <flux:select.option value="moto">{{ __('Moto') }}</flux:select.option>
            <flux:select.option value="auto">{{ __('Auto') }}</flux:select.option>
            <flux:select.option value="camioneta">{{ __('Camioneta') }}</flux:select.option>
            <flux:select.option value="minivan">{{ __('Minivan') }}</flux:select.option>
            <flux:select.option value="furgon">{{ __('Furgón') }}</flux:select.option>
            <flux:select.option value="bus">{{ __('Bus') }}</flux:select.option>
            <flux:select.option value="vehiculo_pesado">{{ __('Veh. pesado') }}</flux:select.option>
        </flux:select>

        @if (auth()->user()->esAdmin())
            <flux:select wire:model.live="filterSucursal" class="w-44">
                <flux:select.option value="">{{ __('Todas las sucursales') }}</flux:select.option>
                @foreach ($this->sucursales as $sucursal)
                    <flux:select.option :value="$sucursal->id">{{ $sucursal->nombre }}</flux:select.option>
                @endforeach
            </flux:select>
        @endif
    </div>

    <flux:table :paginate="$this->vehiculos">
        <flux:table.columns>
            <flux:table.column>{{ __('Vehículo') }}</flux:table.column>
            <flux:table.column>{{ __('Tipo') }}</flux:table.column>
            @if (auth()->user()->esAdmin())
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
                            <flux:heading class="font-mono">{{ $vehiculo->placa }}</flux:heading>
                            <flux:text size="sm">{{ $vehiculo->marca }} {{ $vehiculo->modelo }} · {{ $vehiculo->anio }}</flux:text>
                        </div>
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:badge color="zinc">{{ $this->tipoLabel($vehiculo->tipo) }}</flux:badge>
                    </flux:table.cell>

                    @if (auth()->user()->esAdmin())
                        <flux:table.cell>{{ $vehiculo->sucursal?->nombre ?? '—' }}</flux:table.cell>
                    @endif

                    <flux:table.cell>
                        {{ $vehiculo->conductor_nombre ?? '—' }}
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:badge :color="$this->estadoBadgeColor($vehiculo->estado)">
                            {{ $this->estadoLabel($vehiculo->estado) }}
                        </flux:badge>
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="flex justify-end gap-1">
                            <flux:button
                                :href="route('vehiculos.show', $vehiculo)"
                                size="sm" variant="subtle" icon="eye"
                                inset="top bottom"
                                wire:navigate
                            />
                            @if (auth()->user()->esAdmin())
                                <flux:button
                                    :href="route('vehiculos.editar', $vehiculo)"
                                    size="sm" variant="subtle" icon="pencil"
                                    inset="top bottom"
                                    wire:navigate
                                />
                                <flux:button
                                    wire:click="confirmDelete({{ $vehiculo->id }})"
                                    size="sm" variant="subtle" icon="trash"
                                    inset="top bottom"
                                />
                            @endif
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    @if ($this->vehiculos->isEmpty())
        <div class="py-16 text-center">
            <flux:icon name="truck" class="mx-auto mb-3 size-10 text-zinc-300 dark:text-zinc-600" />
            <flux:text>{{ __('No se encontraron vehículos.') }}</flux:text>
        </div>
    @endif

    {{-- Modal confirmar eliminación --}}
    <flux:modal wire:model.self="showDeleteModal" class="md:w-80">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Eliminar vehículo') }}</flux:heading>
                <flux:text class="mt-2">
                    {{ __('¿Estás seguro? Esta acción no se puede deshacer.') }}
                </flux:text>
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
