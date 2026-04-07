<?php

use App\Models\Vehiculo;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Detalle vehículo')] class extends Component {
    public Vehiculo $vehiculo;

    public bool $showDeleteModal = false;

    public function mount(Vehiculo $vehiculo): void
    {
        $this->authorize('view', $vehiculo);
        $this->vehiculo = $vehiculo->load('sucursal');
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->vehiculo);
        $this->vehiculo->delete();
        $this->redirect(route('vehiculos.index'), navigate: true);
    }

    public function estadoBadgeColor(): string
    {
        return match ($this->vehiculo->estado) {
            'operativo' => 'green',
            'parcialmente' => 'amber',
            'fuera_de_servicio' => 'red',
            default => 'zinc',
        };
    }

    public function estadoLabel(): string
    {
        return match ($this->vehiculo->estado) {
            'operativo' => 'Operativo',
            'parcialmente' => 'Parcialmente operativo',
            'fuera_de_servicio' => 'Fuera de servicio',
            default => $this->vehiculo->estado,
        };
    }

    public function tipoLabel(): string
    {
        return match ($this->vehiculo->tipo) {
            'moto' => 'Moto',
            'auto' => 'Auto',
            'camioneta' => 'Camioneta',
            'minivan' => 'Minivan',
            'furgon' => 'Furgón',
            'bus' => 'Bus',
            'vehiculo_pesado' => 'Vehículo pesado',
            default => $this->vehiculo->tipo,
        };
    }
}; ?>

<section class="w-full">

    {{-- Header --}}
    <div class="flex items-start justify-between mb-6">
        <div class="flex items-center gap-4">
            <flux:button :href="route('vehiculos.index')" variant="ghost" icon="arrow-left" wire:navigate />
            <div>
                <div class="flex items-center gap-3 flex-wrap">
                    <flux:heading size="xl" class="font-mono">{{ $vehiculo->placa }}</flux:heading>
                    <flux:badge :color="$this->estadoBadgeColor()">{{ $this->estadoLabel() }}</flux:badge>
                    <flux:badge color="zinc">{{ $this->tipoLabel() }}</flux:badge>
                </div>
                <flux:text class="mt-1">
                    {{ $vehiculo->marca }} {{ $vehiculo->modelo }} · {{ $vehiculo->anio }}
                    @if ($vehiculo->sucursal)
                        · {{ $vehiculo->sucursal->nombre }}
                    @endif
                </flux:text>
                @if ($vehiculo->propietario)
                    <flux:text size="sm" class="text-zinc-500">{{ $vehiculo->propietario }}</flux:text>
                @endif
            </div>
        </div>

        @if (auth()->user()->esAdmin())
            <div class="flex gap-2">
                <flux:button
                    :href="route('vehiculos.editar', $vehiculo)"
                    variant="outline" icon="pencil"
                    wire:navigate
                >
                    {{ __('Editar') }}
                </flux:button>
                <flux:button
                    wire:click="$set('showDeleteModal', true)"
                    variant="danger" icon="trash"
                >
                    {{ __('Eliminar') }}
                </flux:button>
            </div>
        @endif
    </div>

    @if ($vehiculo->problema_activo)
        <flux:callout color="amber" icon="exclamation-triangle" class="mb-6">
            <flux:callout.heading>{{ __('Problema activo') }}</flux:callout.heading>
            <flux:callout.text>{{ $vehiculo->problema_activo }}</flux:callout.text>
        </flux:callout>
    @endif

    {{-- Tabs --}}
    <div x-data="{ tab: 'info' }">
        <div class="flex gap-1 border-b border-zinc-200 dark:border-zinc-700 mb-6">
            @foreach ([
                'info' => __('Información'),
                'combustible' => __('Combustible'),
                'documentos' => __('Documentos'),
                'mantenimientos' => __('Mantenimientos'),
                'fotos' => __('Fotos'),
            ] as $key => $label)
                <button
                    type="button"
                    x-on:click="tab = '{{ $key }}'"
                    :class="tab === '{{ $key }}'
                        ? 'border-b-2 border-accent text-accent font-medium'
                        : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300'"
                    class="px-4 py-2 text-sm transition-colors"
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- Tab: Información --}}
        <div x-show="tab === 'info'" x-cloak>
            <div class="grid grid-cols-1 gap-8 md:grid-cols-2">

                {{-- Identificación --}}
                <div class="space-y-3">
                    <flux:heading>{{ __('Identificación') }}</flux:heading>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-zinc-500">{{ __('Placa') }}</dt>
                            <dd class="font-mono font-medium">{{ $vehiculo->placa }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-zinc-500">{{ __('Tipo') }}</dt>
                            <dd>{{ $this->tipoLabel() }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-zinc-500">{{ __('Marca / Modelo') }}</dt>
                            <dd>{{ $vehiculo->marca }} {{ $vehiculo->modelo }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-zinc-500">{{ __('Año') }}</dt>
                            <dd>{{ $vehiculo->anio }}</dd>
                        </div>
                        @if ($vehiculo->color)
                            <div class="flex justify-between">
                                <dt class="text-zinc-500">{{ __('Color') }}</dt>
                                <dd>{{ $vehiculo->color }}</dd>
                            </div>
                        @endif
                        <div class="flex justify-between">
                            <dt class="text-zinc-500">{{ __('Sucursal') }}</dt>
                            <dd>{{ $vehiculo->sucursal?->nombre ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>

                {{-- SUNARP --}}
                <div class="space-y-3">
                    <flux:heading>{{ __('Tarjeta de propiedad') }}</flux:heading>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-zinc-500">{{ __('N° Motor') }}</dt>
                            <dd class="font-mono">{{ $vehiculo->num_motor ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-zinc-500">{{ __('N° Chasis') }}</dt>
                            <dd class="font-mono">{{ $vehiculo->num_chasis ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-zinc-500">{{ __('VIN') }}</dt>
                            <dd class="font-mono">{{ $vehiculo->vin ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-zinc-500">{{ __('Propietario') }}</dt>
                            <dd>{{ $vehiculo->propietario ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-zinc-500">{{ __('RUC') }}</dt>
                            <dd class="font-mono">{{ $vehiculo->ruc_propietario ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>

                {{-- Técnico --}}
                <div class="space-y-3">
                    <flux:heading>{{ __('Datos técnicos') }}</flux:heading>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-zinc-500">{{ __('Combustible') }}</dt>
                            <dd>{{ ucfirst($vehiculo->combustible) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-zinc-500">{{ __('Transmisión') }}</dt>
                            <dd>{{ $vehiculo->transmision ? ucfirst($vehiculo->transmision) : '—' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-zinc-500">{{ __('Tracción') }}</dt>
                            <dd>{{ $vehiculo->traccion ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-zinc-500">{{ __('Km actuales') }}</dt>
                            <dd>{{ $vehiculo->km_actuales ? number_format($vehiculo->km_actuales) . ' km' : '—' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-zinc-500">{{ __('Cap. de carga') }}</dt>
                            <dd>{{ $vehiculo->capacidad_carga ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>

                {{-- Conductor + Admin --}}
                <div class="space-y-3">
                    <flux:heading>{{ __('Conductor y administración') }}</flux:heading>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-zinc-500">{{ __('Conductor') }}</dt>
                            <dd>{{ $vehiculo->conductor_nombre ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-zinc-500">{{ __('Teléfono') }}</dt>
                            <dd>{{ $vehiculo->conductor_tel ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-zinc-500">{{ __('Adquisición') }}</dt>
                            <dd>{{ $vehiculo->fecha_adquisicion?->format('d/m/Y') ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-zinc-500">{{ __('GPS ID') }}</dt>
                            <dd class="font-mono">{{ $vehiculo->gps_id ?? '—' }}</dd>
                        </div>
                    </dl>

                    @if ($vehiculo->observaciones)
                        <div class="pt-2">
                            <flux:text size="sm" class="text-zinc-500 mb-1">{{ __('Observaciones') }}</flux:text>
                            <flux:text size="sm">{{ $vehiculo->observaciones }}</flux:text>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Tab: Documentos --}}
        <div x-show="tab === 'documentos'" x-cloak>
            <livewire:pages::vehiculos.documentos :vehiculo="$vehiculo" lazy />
        </div>

        {{-- Tabs placeholders FASE 2 --}}
        @foreach (['combustible', 'mantenimientos', 'fotos'] as $tabKey)
            <div x-show="tab === '{{ $tabKey }}'" x-cloak>
                <div class="py-16 text-center">
                    <flux:text class="text-zinc-400">{{ __('Disponible próximamente.') }}</flux:text>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Modal eliminar --}}
    <flux:modal wire:model.self="showDeleteModal" class="md:w-80">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Eliminar vehículo') }}</flux:heading>
                <flux:text class="mt-2">
                    {{ __('¿Eliminar el vehículo') }} <strong>{{ $vehiculo->placa }}</strong>?
                    {{ __('Esta acción no se puede deshacer.') }}
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
