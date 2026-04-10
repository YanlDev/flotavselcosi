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
        $this->vehiculo = $vehiculo->load('sucursal', 'conductor');
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

    public function tipoIcon(): string
    {
        return match ($this->vehiculo->tipo) {
            'moto' => 'bolt',
            'bus' => 'building-storefront',
            'vehiculo_pesado' => 'cube',
            default => 'truck',
        };
    }

    public function estadoHeaderColor(): string
    {
        return match ($this->vehiculo->estado) {
            'operativo' => 'from-brand-500/10 to-transparent border-brand-500/20',
            'parcialmente' => 'from-amber-500/10 to-transparent border-amber-500/20',
            'fuera_de_servicio' => 'from-red-500/10 to-transparent border-red-500/20',
            default => 'from-slate-500/10 to-transparent border-slate-500/20',
        };
    }
}; ?>

<section class="w-full max-w-6xl mx-auto px-3 py-4 sm:p-6 lg:p-8">

    {{-- Barra superior --}}
    <div class="mb-4 flex items-center justify-between gap-3">
        <flux:button
            :href="route('vehiculos.index')"
            variant="ghost" icon="arrow-left" size="sm"
            wire:navigate
        >
            <span class="hidden sm:inline">{{ __('Vehículos') }}</span>
        </flux:button>

        @if (auth()->user()->esAdmin())
            {{-- Desktop: botones visibles --}}
            <div class="hidden sm:flex gap-2">
                <flux:button
                    :href="route('vehiculos.editar', $vehiculo)"
                    variant="outline" icon="pencil" size="sm"
                    wire:navigate
                >{{ __('Editar') }}</flux:button>
                <flux:button
                    wire:click="$set('showDeleteModal', true)"
                    variant="danger" icon="trash" size="sm"
                >{{ __('Eliminar') }}</flux:button>
            </div>

            {{-- Mobile: dropdown --}}
            <div class="sm:hidden">
                <flux:dropdown position="bottom" align="end">
                    <flux:button variant="ghost" icon="ellipsis-vertical" size="sm" />
                    <flux:menu>
                        <flux:menu.item
                            :href="route('vehiculos.editar', $vehiculo)"
                            icon="pencil"
                            wire:navigate
                        >{{ __('Editar') }}</flux:menu.item>
                        <flux:menu.separator />
                        <flux:menu.item
                            wire:click="$set('showDeleteModal', true)"
                            icon="trash"
                            variant="danger"
                        >{{ __('Eliminar') }}</flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            </div>
        @endif
    </div>

    {{-- Hero card --}}
    <div class="mb-6 rounded-2xl border bg-gradient-to-br {{ $this->estadoHeaderColor() }} p-6 bg-white dark:bg-slate-900 shadow-sm">
        <div class="flex items-start gap-4">
            <div class="hidden sm:flex size-14 shrink-0 items-center justify-center rounded-xl bg-brand-50 ring-1 ring-brand-100 dark:bg-brand-950/40 dark:ring-brand-900">
                <flux:icon :name="$this->tipoIcon()" class="size-7 text-brand-600 dark:text-brand-400" />
            </div>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="font-mono-data text-2xl font-bold tracking-wider text-slate-900 dark:text-white">{{ $vehiculo->placa }}</h1>
                    <x-ui.badge-status :status="$vehiculo->estado" :label="$this->estadoLabel()" />
                    <span class="inline-flex rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                        {{ $this->tipoLabel() }}
                    </span>
                </div>

                <p class="mt-1 text-sm font-medium text-slate-600 dark:text-slate-300">
                    {{ $vehiculo->marca }} {{ $vehiculo->modelo }}
                    @if ($vehiculo->anio) · {{ $vehiculo->anio }} @endif
                    @if ($vehiculo->color) · {{ ucfirst($vehiculo->color) }} @endif
                </p>

                <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500 dark:text-slate-400">
                    @if ($vehiculo->sucursal)
                        <span class="flex items-center gap-1">
                            <flux:icon name="building-office" class="size-3.5" />
                            {{ $vehiculo->sucursal->nombre }}
                        </span>
                    @endif
                    @if ($vehiculo->conductor)
                        <span class="flex items-center gap-1">
                            <flux:icon name="user" class="size-3.5" />
                            {{ $vehiculo->conductor->nombre_completo }}
                            @if ($vehiculo->conductor->telefono)
                                · {{ $vehiculo->conductor->telefono }}
                            @endif
                        </span>
                    @endif
                    @if ($vehiculo->km_actuales)
                        <span class="flex items-center gap-1">
                            <flux:icon name="map-pin" class="size-3.5" />
                            {{ number_format($vehiculo->km_actuales) }} km
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Alerta problema activo --}}
    @if ($vehiculo->problema_activo)
        <flux:callout color="amber" icon="exclamation-triangle" class="mb-6">
            <flux:callout.heading>{{ __('Problema activo') }}</flux:callout.heading>
            <flux:callout.text>{{ $vehiculo->problema_activo }}</flux:callout.text>
        </flux:callout>
    @endif

    {{-- Tabs --}}
    <div x-data="{ tab: 'info' }">
        {{-- Tab bar: scrollable en mobile --}}
        <div class="mb-6 overflow-x-auto border-b border-zinc-200 dark:border-zinc-700">
            <div class="flex min-w-max gap-0">
                @php
                    $tabs = [
                        'info' => ['label' => __('Información'), 'icon' => 'information-circle'],
                        'documentos' => ['label' => __('Documentos'), 'icon' => 'document-text'],
                        'combustible' => ['label' => __('Combustible'), 'icon' => 'fire'],
                        'mantenimientos' => ['label' => __('Mantenimientos'), 'icon' => 'wrench-screwdriver'],
                        'fotos' => ['label' => __('Fotos'), 'icon' => 'photo'],
                    ];
                @endphp
                @foreach ($tabs as $key => $tabData)
                    <button
                        type="button"
                        x-on:click="tab = '{{ $key }}'"
                        :class="tab === '{{ $key }}'
                            ? 'border-b-2 border-accent text-accent font-semibold'
                            : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300'"
                        class="flex items-center gap-1.5 whitespace-nowrap px-4 py-2.5 text-sm transition-colors"
                    >
                        <flux:icon name="{{ $tabData['icon'] }}" class="size-4" />
                        {{ $tabData['label'] }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Tab: Información --}}
        <div x-show="tab === 'info'" x-cloak>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                {{-- Identificación --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <h3 class="mb-3 flex items-center gap-2 text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                        <flux:icon name="identification" class="size-4 text-accent" />
                        {{ __('Identificación') }}
                    </h3>
                    <dl class="space-y-2.5">
                        @foreach ([
                            __('Placa') => ['value' => $vehiculo->placa, 'mono' => true],
                            __('Tipo') => ['value' => $this->tipoLabel()],
                            __('Marca / Modelo') => ['value' => $vehiculo->marca.' '.$vehiculo->modelo],
                            __('Año') => ['value' => $vehiculo->anio],
                            __('Color') => ['value' => $vehiculo->color ? ucfirst($vehiculo->color) : null],
                            __('Sucursal') => ['value' => $vehiculo->sucursal?->nombre],
                        ] as $label => $item)
                            @if ($item['value'] ?? null)
                                <div class="flex items-baseline justify-between gap-2 text-sm">
                                    <dt class="text-zinc-500 shrink-0">{{ $label }}</dt>
                                    <dd class="{{ ($item['mono'] ?? false) ? 'font-mono font-semibold' : 'text-right' }}">{{ $item['value'] }}</dd>
                                </div>
                            @endif
                        @endforeach
                    </dl>
                </div>

                {{-- Tarjeta de propiedad --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <h3 class="mb-3 flex items-center gap-2 text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                        <flux:icon name="document-check" class="size-4 text-accent" />
                        {{ __('Tarjeta de propiedad') }}
                    </h3>
                    <dl class="space-y-2.5">
                        @foreach ([
                            __('N° Motor') => ['value' => $vehiculo->num_motor, 'mono' => true],
                            __('N° Chasis') => ['value' => $vehiculo->num_chasis, 'mono' => true],
                            __('VIN') => ['value' => $vehiculo->vin, 'mono' => true],
                            __('Propietario') => ['value' => $vehiculo->propietario],
                            __('RUC') => ['value' => $vehiculo->ruc_propietario, 'mono' => true],
                        ] as $label => $item)
                            <div class="flex items-baseline justify-between gap-2 text-sm">
                                <dt class="text-zinc-500 shrink-0">{{ $label }}</dt>
                                <dd class="{{ ($item['mono'] ?? false) ? 'font-mono text-right' : 'text-right' }}">
                                    {{ $item['value'] ?? '—' }}
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </div>

                {{-- Datos técnicos --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <h3 class="mb-3 flex items-center gap-2 text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                        <flux:icon name="cog-6-tooth" class="size-4 text-accent" />
                        {{ __('Datos técnicos') }}
                    </h3>
                    <dl class="space-y-2.5">
                        @foreach ([
                            __('Combustible') => ucfirst($vehiculo->combustible ?? '—'),
                            __('Transmisión') => $vehiculo->transmision ? ucfirst($vehiculo->transmision) : '—',
                            __('Tracción') => $vehiculo->traccion ?? '—',
                            __('Km actuales') => $vehiculo->km_actuales ? number_format($vehiculo->km_actuales).' km' : '—',
                            __('Cap. de carga') => $vehiculo->capacidad_carga ?? '—',
                        ] as $label => $value)
                            <div class="flex items-baseline justify-between gap-2 text-sm">
                                <dt class="text-zinc-500 shrink-0">{{ $label }}</dt>
                                <dd class="text-right">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>

                {{-- Conductor y administración --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <h3 class="mb-3 flex items-center gap-2 text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                        <flux:icon name="user-circle" class="size-4 text-accent" />
                        {{ __('Conductor y administración') }}
                    </h3>
                    <dl class="space-y-2.5">
                        @foreach ([
                            __('Conductor') => $vehiculo->conductor?->nombre_completo ?? '—',
                            __('Teléfono') => $vehiculo->conductor?->telefono ?? '—',
                            __('Adquisición') => $vehiculo->fecha_adquisicion?->format('d/m/Y') ?? '—',
                            __('GPS') => $vehiculo->tiene_gps ? 'Sí' : 'No',
                        ] as $label => $value)
                            <div class="flex items-baseline justify-between gap-2 text-sm">
                                <dt class="text-zinc-500 shrink-0">{{ $label }}</dt>
                                <dd class="text-right">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    @if ($vehiculo->observaciones)
                        <div class="mt-3 rounded-lg bg-zinc-50 dark:bg-zinc-800 p-3">
                            <p class="text-xs text-zinc-500 mb-1">{{ __('Observaciones') }}</p>
                            <p class="text-sm">{{ $vehiculo->observaciones }}</p>
                        </div>
                    @endif
                </div>

            </div>
        </div>

        {{-- Tab: Documentos --}}
        <div x-show="tab === 'documentos'" x-cloak>
            <livewire:pages::vehiculos.documentos :vehiculo="$vehiculo" lazy />
        </div>

        {{-- Tab: Combustible --}}
        <div x-show="tab === 'combustible'" x-cloak>
            <livewire:pages::vehiculos.combustible :vehiculo="$vehiculo" lazy />
        </div>

        {{-- Tab: Mantenimientos --}}
        <div x-show="tab === 'mantenimientos'" x-cloak>
            <livewire:pages::vehiculos.mantenimientos :vehiculo="$vehiculo" lazy />
        </div>

        {{-- Tab: Fotos --}}
        <div x-show="tab === 'fotos'" x-cloak>
            <livewire:pages::vehiculos.fotos :vehiculo="$vehiculo" lazy />
        </div>
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
