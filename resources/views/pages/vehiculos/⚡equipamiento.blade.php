<?php

use App\Enums\EstadoEquipamiento;
use App\Enums\ItemEquipamiento;
use App\Models\EquipamientoVehicular;
use App\Models\Vehiculo;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {

    public Vehiculo $vehiculo;

    // Modal edición
    public bool $showModal = false;
    public string $editingItem = '';
    public string $estado = '';
    public string $vencimiento = '';
    public string $observaciones = '';

    public function mount(Vehiculo $vehiculo): void
    {
        $this->authorize('view', $vehiculo);
        $this->vehiculo = $vehiculo;
    }

    #[Computed]
    public function equipamiento(): \Illuminate\Support\Collection
    {
        $registros = EquipamientoVehicular::where('vehiculo_id', $this->vehiculo->id)
            ->get()
            ->keyBy(fn ($e) => $e->item->value);

        return collect(ItemEquipamiento::cases())->map(function (ItemEquipamiento $item) use ($registros) {
            return $registros->get($item->value) ?? new EquipamientoVehicular([
                'vehiculo_id' => $this->vehiculo->id,
                'item'        => $item,
                'estado'      => EstadoEquipamiento::No,
                'vencimiento' => null,
                'observaciones' => null,
            ]);
        });
    }

    public function abrirEditar(string $item): void
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $itemEnum = ItemEquipamiento::from($item);
        $registro = EquipamientoVehicular::where('vehiculo_id', $this->vehiculo->id)
            ->where('item', $item)
            ->first();

        $this->editingItem   = $item;
        $this->estado        = $registro?->estado->value ?? EstadoEquipamiento::No->value;
        $this->vencimiento   = $registro?->vencimiento?->format('Y-m-d') ?? '';
        $this->observaciones = $registro?->observaciones ?? '';
        $this->showModal     = true;
    }

    public function guardar(): void
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $itemEnum = ItemEquipamiento::from($this->editingItem);

        $rules = [
            'estado'        => ['required', 'in:' . implode(',', array_column(EstadoEquipamiento::cases(), 'value'))],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ];

        if ($itemEnum->tieneVencimiento() && $this->estado !== EstadoEquipamiento::NoAplica->value) {
            $rules['vencimiento'] = ['nullable', 'date'];
        }

        $this->validate($rules);

        EquipamientoVehicular::updateOrCreate(
            ['vehiculo_id' => $this->vehiculo->id, 'item' => $this->editingItem],
            [
                'estado'        => $this->estado,
                'vencimiento'   => $itemEnum->tieneVencimiento() && $this->vencimiento ? $this->vencimiento : null,
                'observaciones' => $this->observaciones ?: null,
            ]
        );

        unset($this->equipamiento);
        $this->showModal = false;
    }

    public function estadoColor(EstadoEquipamiento $estado): string
    {
        return $estado->color();
    }
}; ?>

<div class="space-y-4">

    {{-- Encabezado --}}
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="sm">{{ __('Equipamiento mínimo de seguridad') }}</flux:heading>
            <flux:text class="text-xs text-zinc-500">
                {{ __('Según Reglamento Nacional de Vehículos (D.S. N° 058-2003-MTC).') }}
            </flux:text>
        </div>
    </div>

    {{-- Alertas de equipamiento --}}
    @php
        $enAlerta = $this->equipamiento->filter(
            fn ($e) => $e->item instanceof \App\Enums\ItemEquipamiento
                && $e->estado instanceof \App\Enums\EstadoEquipamiento
                && $e->estado->esAlerta()
        );
        $extintorVence = $this->equipamiento->first(
            fn ($e) => $e->item === \App\Enums\ItemEquipamiento::Extintor
                && $e->vencimiento
                && $e->vencimiento->lte(now()->addDays(30))
        );
    @endphp

    @if ($enAlerta->isNotEmpty() || $extintorVence)
        <div class="space-y-2">
            @if ($enAlerta->isNotEmpty())
                <flux:callout color="red" icon="exclamation-triangle">
                    <flux:callout.heading>
                        {{ $enAlerta->count() }} {{ $enAlerta->count() === 1 ? __('ítem requiere atención') : __('ítems requieren atención') }}
                    </flux:callout.heading>
                    <flux:callout.text>
                        {{ $enAlerta->map(fn ($e) => $e->item->label())->implode(', ') }}
                    </flux:callout.text>
                </flux:callout>
            @endif
            @if ($extintorVence)
                <flux:callout color="amber" icon="fire">
                    <flux:callout.heading>{{ __('Extintor próximo a vencer') }}</flux:callout.heading>
                    <flux:callout.text>
                        {{ __('Vence el') }} {{ $extintorVence->vencimiento->format('d/m/Y') }}
                        ({{ $extintorVence->diasParaVencer() }} {{ __('días') }})
                    </flux:callout.text>
                </flux:callout>
            @endif
        </div>
    @endif

    {{-- Tabla desktop --}}
    <div class="hidden sm:block overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800/60">
                    <th class="px-4 py-3 text-left font-semibold text-zinc-700 dark:text-zinc-300">{{ __('Equipo / Elemento') }}</th>
                    <th class="px-4 py-3 text-center font-semibold text-zinc-700 dark:text-zinc-300 w-24">{{ __('Qty. mínima') }}</th>
                    <th class="px-4 py-3 text-center font-semibold text-zinc-700 dark:text-zinc-300 w-32">{{ __('Estado') }}</th>
                    <th class="px-4 py-3 text-left font-semibold text-zinc-700 dark:text-zinc-300 w-36">{{ __('Vencimiento') }}</th>
                    <th class="px-4 py-3 text-left font-semibold text-zinc-700 dark:text-zinc-300">{{ __('Observaciones') }}</th>
                    @if (auth()->user()->esAdmin())
                        <th class="w-12"></th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach ($this->equipamiento as $registro)
                    <tr class="bg-white dark:bg-zinc-900 hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition-colors">
                        <td class="px-4 py-3 text-zinc-800 dark:text-zinc-200">
                            {{ $registro->item->label() }}
                        </td>
                        <td class="px-4 py-3 text-center text-zinc-500 dark:text-zinc-400">
                            {{ $registro->item->cantidadMinima() }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <flux:badge :color="$registro->estado->color()" size="sm">
                                {{ $registro->estado->label() }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-3">
                            @if ($registro->vencimiento)
                                @php $dias = $registro->diasParaVencer(); @endphp
                                <span class="{{ $dias !== null && $dias <= 30 ? 'text-amber-600 dark:text-amber-400 font-medium' : 'text-zinc-600 dark:text-zinc-400' }}">
                                    {{ $registro->vencimiento->format('d/m/Y') }}
                                    @if ($dias !== null && $dias <= 30)
                                        <span class="text-xs">({{ $dias }}d)</span>
                                    @endif
                                </span>
                            @elseif ($registro->item->tieneVencimiento() && $registro->estado !== \App\Enums\EstadoEquipamiento::NoAplica)
                                <span class="text-xs text-zinc-400">{{ __('Sin fecha') }}</span>
                            @else
                                <span class="text-zinc-300 dark:text-zinc-600">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-zinc-500 dark:text-zinc-400">
                            {{ $registro->observaciones ?? '—' }}
                        </td>
                        @if (auth()->user()->esAdmin())
                            <td class="px-2 py-3">
                                <flux:button
                                    wire:click="abrirEditar('{{ $registro->item->value }}')"
                                    size="sm" variant="subtle" icon="pencil"
                                    inset="top bottom"
                                />
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Cards mobile --}}
    <div class="sm:hidden space-y-2">
        @foreach ($this->equipamiento as $registro)
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200 leading-snug">
                            {{ $registro->item->label() }}
                        </p>
                        <p class="mt-0.5 text-xs text-zinc-400">{{ __('Qty. mínima:') }} {{ $registro->item->cantidadMinima() }}</p>
                        @if ($registro->vencimiento)
                            @php $dias = $registro->diasParaVencer(); @endphp
                            <p class="mt-1 text-xs {{ $dias !== null && $dias <= 30 ? 'text-amber-600 dark:text-amber-400 font-medium' : 'text-zinc-500' }}">
                                {{ __('Vence:') }} {{ $registro->vencimiento->format('d/m/Y') }}
                                @if ($dias !== null && $dias <= 30)
                                    ({{ $dias }}d)
                                @endif
                            </p>
                        @endif
                        @if ($registro->observaciones)
                            <p class="mt-1 text-xs text-zinc-400 italic">{{ $registro->observaciones }}</p>
                        @endif
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <flux:badge :color="$registro->estado->color()" size="sm">
                            {{ $registro->estado->label() }}
                        </flux:badge>
                        @if (auth()->user()->esAdmin())
                            <flux:button
                                wire:click="abrirEditar('{{ $registro->item->value }}')"
                                size="sm" variant="subtle" icon="pencil"
                                inset="top bottom"
                            />
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Modal edición --}}
    @if (auth()->user()->esAdmin())
        <flux:modal wire:model.self="showModal" class="md:w-96">
            <div class="space-y-5">
                <div>
                    <flux:heading size="lg">{{ __('Editar equipamiento') }}</flux:heading>
                    @if ($editingItem)
                        <flux:text class="mt-1 text-sm text-zinc-500">
                            {{ \App\Enums\ItemEquipamiento::tryFrom($editingItem)?->label() }}
                        </flux:text>
                    @endif
                </div>

                <form wire:submit="guardar" class="space-y-4">
                    <flux:select wire:model.live="estado" :label="__('Estado')" required>
                        @foreach (\App\Enums\EstadoEquipamiento::cases() as $case)
                            <flux:select.option value="{{ $case->value }}">{{ $case->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    @if ($editingItem && \App\Enums\ItemEquipamiento::tryFrom($editingItem)?->tieneVencimiento() && $estado !== \App\Enums\EstadoEquipamiento::NoAplica->value)
                        <flux:input
                            wire:model="vencimiento"
                            type="date"
                            :label="__('Fecha de vencimiento')"
                        />
                    @endif

                    <flux:input
                        wire:model="observaciones"
                        :label="__('Observaciones (opcional)')"
                        :placeholder="__('Ej: Extintor marca X, renovar antes de julio')"
                    />

                    <div class="flex justify-end gap-2 pt-1">
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
    @endif

</div>
