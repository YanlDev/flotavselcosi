<?php

use App\Models\Mantenimiento;
use App\Models\Vehiculo;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {

    public Vehiculo $vehiculo;

    // Modal crear/editar
    public bool $showModal = false;
    public ?int $editingId = null;

    // Campos del formulario
    public string $categoria = '';
    public string $tipo = '';
    public string $descripcion = '';
    public string $taller = '';
    public string $costo = '';
    public string $fechaServicio = '';
    public string $kmServicio = '';
    public string $proximoKm = '';
    public string $proximaFecha = '';
    public string $observaciones = '';

    // Modal eliminar
    public ?int $deletingId = null;
    public bool $showDeleteModal = false;

    public function mount(Vehiculo $vehiculo): void
    {
        $this->authorize('view', $vehiculo);
        $this->vehiculo = $vehiculo;
    }

    #[Computed]
    public function mantenimientos(): \Illuminate\Database\Eloquent\Collection
    {
        return Mantenimiento::where('vehiculo_id', $this->vehiculo->id)
            ->with('registradoPor')
            ->orderByDesc('fecha_servicio')
            ->get();
    }

    #[Computed]
    public function alertas(): \Illuminate\Database\Eloquent\Collection
    {
        return Mantenimiento::where('vehiculo_id', $this->vehiculo->id)
            ->where(function ($q) {
                $q->whereNotNull('proxima_fecha')
                    ->where('proxima_fecha', '<=', now()->addDays(30))
                    ->orWhere(function ($q2) {
                        $q2->whereNotNull('proximo_km')
                            ->whereNotNull('km_servicio')
                            ->whereRaw(
                                'proximo_km - ? <= 1000',
                                [$this->vehiculo->km_actuales ?? 0]
                            );
                    });
            })
            ->orderBy('proxima_fecha')
            ->get();
    }

    public function abrirCrear(): void
    {
        abort_unless(auth()->user()->esAdmin(), 403);
        $this->reset([
            'editingId', 'categoria', 'tipo', 'descripcion', 'taller',
            'costo', 'fechaServicio', 'kmServicio', 'proximoKm',
            'proximaFecha', 'observaciones',
        ]);
        $this->fechaServicio = now()->format('Y-m-d');
        $this->showModal = true;
    }

    public function abrirEditar(int $id): void
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $m = Mantenimiento::where('vehiculo_id', $this->vehiculo->id)->findOrFail($id);

        $this->editingId     = $m->id;
        $this->categoria     = $m->categoria;
        $this->tipo          = $m->tipo;
        $this->descripcion   = $m->descripcion ?? '';
        $this->taller        = $m->taller ?? '';
        $this->costo         = $m->costo !== null ? (string) $m->costo : '';
        $this->fechaServicio = $m->fecha_servicio->format('Y-m-d');
        $this->kmServicio    = $m->km_servicio !== null ? (string) $m->km_servicio : '';
        $this->proximoKm     = $m->proximo_km !== null ? (string) $m->proximo_km : '';
        $this->proximaFecha  = $m->proxima_fecha?->format('Y-m-d') ?? '';
        $this->observaciones = $m->observaciones ?? '';
        $this->showModal     = true;
    }

    public function guardar(): void
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $this->validate([
            'categoria'     => ['required', 'in:aceite_filtros,llantas,frenos,liquidos,bateria,alineacion_balanceo,suspension,transmision,electricidad,revision_general,otro'],
            'tipo'          => ['required', 'in:preventivo,correctivo'],
            'fechaServicio' => ['required', 'date'],
            'descripcion'   => ['nullable', 'string', 'max:1000'],
            'taller'        => ['nullable', 'string', 'max:200'],
            'costo'         => ['nullable', 'numeric', 'min:0'],
            'kmServicio'    => ['nullable', 'integer', 'min:0'],
            'proximoKm'     => ['nullable', 'integer', 'min:0'],
            'proximaFecha'  => ['nullable', 'date'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ]);

        $data = [
            'vehiculo_id'    => $this->vehiculo->id,
            'registrado_por' => auth()->id(),
            'categoria'      => $this->categoria,
            'tipo'           => $this->tipo,
            'descripcion'    => $this->descripcion ?: null,
            'taller'         => $this->taller ?: null,
            'costo'          => $this->costo !== '' ? $this->costo : null,
            'fecha_servicio' => $this->fechaServicio,
            'km_servicio'    => $this->kmServicio !== '' ? (int) $this->kmServicio : null,
            'proximo_km'     => $this->proximoKm !== '' ? (int) $this->proximoKm : null,
            'proxima_fecha'  => $this->proximaFecha ?: null,
            'observaciones'  => $this->observaciones ?: null,
        ];

        if ($this->editingId) {
            Mantenimiento::where('vehiculo_id', $this->vehiculo->id)
                ->findOrFail($this->editingId)
                ->update($data);
        } else {
            Mantenimiento::create($data);
        }

        unset($this->mantenimientos, $this->alertas);
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

        Mantenimiento::where('vehiculo_id', $this->vehiculo->id)
            ->findOrFail($this->deletingId)
            ->delete();

        unset($this->mantenimientos, $this->alertas);
        $this->deletingId = null;
        $this->showDeleteModal = false;
    }

    public function categoriaLabel(string $cat): string
    {
        return match ($cat) {
            'aceite_filtros'      => 'Aceite y filtros',
            'llantas'             => 'Llantas',
            'frenos'              => 'Frenos',
            'liquidos'            => 'Líquidos',
            'bateria'             => 'Batería',
            'alineacion_balanceo' => 'Alineación / balanceo',
            'suspension'          => 'Suspensión',
            'transmision'         => 'Transmisión',
            'electricidad'        => 'Electricidad',
            'revision_general'    => 'Revisión general',
            'otro'                => 'Otro',
            default               => $cat,
        };
    }

    public function alertaColor(Mantenimiento $m): string
    {
        $kmActuales = $this->vehiculo->km_actuales ?? 0;

        // Vencido por fecha o km
        if ($m->proxima_fecha && $m->proxima_fecha->isPast()) {
            return 'red';
        }
        if ($m->proximo_km && $kmActuales >= $m->proximo_km) {
            return 'red';
        }

        return 'amber';
    }

    public function alertaTexto(Mantenimiento $m): string
    {
        $partes = [];
        $kmActuales = $this->vehiculo->km_actuales ?? 0;

        if ($m->proxima_fecha) {
            $dias = now()->diffInDays($m->proxima_fecha, false);
            $partes[] = $dias < 0
                ? 'Venció hace ' . abs((int) $dias) . ' días'
                : 'Vence en ' . (int) $dias . ' días';
        }

        if ($m->proximo_km) {
            $restantes = $m->proximo_km - $kmActuales;
            $partes[] = $restantes <= 0
                ? 'Km superado por ' . number_format(abs($restantes)) . ' km'
                : 'Faltan ' . number_format($restantes) . ' km';
        }

        return implode(' · ', $partes);
    }
}; ?>

<div class="space-y-6">

    {{-- Encabezado --}}
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="sm">{{ __('Historial de mantenimientos') }}</flux:heading>
            <flux:text class="text-xs text-zinc-500">{{ __('Registro cronológico de servicios realizados.') }}</flux:text>
        </div>
        @if (auth()->user()->esAdmin())
            <flux:button variant="primary" icon="plus" size="sm" wire:click="abrirCrear">
                {{ __('Nuevo servicio') }}
            </flux:button>
        @endif
    </div>

    {{-- Alertas de mantenimiento --}}
    @if ($this->alertas->isNotEmpty())
        <div class="space-y-2">
            @foreach ($this->alertas as $alerta)
                <flux:callout
                    :color="$this->alertaColor($alerta)"
                    icon="{{ $this->alertaColor($alerta) === 'red' ? 'exclamation-circle' : 'clock' }}"
                >
                    <flux:callout.heading>
                        {{ $this->categoriaLabel($alerta->categoria) }}
                        <span class="font-normal text-sm"> — {{ ucfirst($alerta->tipo) }}</span>
                    </flux:callout.heading>
                    <flux:callout.text>{{ $this->alertaTexto($alerta) }}</flux:callout.text>
                </flux:callout>
            @endforeach
        </div>
    @endif

    {{-- Lista de mantenimientos --}}
    @if ($this->mantenimientos->isNotEmpty())

        {{-- Desktop --}}
        <div class="hidden sm:block overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Fecha') }}</flux:table.column>
                    <flux:table.column>{{ __('Categoría') }}</flux:table.column>
                    <flux:table.column>{{ __('Tipo') }}</flux:table.column>
                    <flux:table.column>{{ __('Km') }}</flux:table.column>
                    <flux:table.column>{{ __('Taller') }}</flux:table.column>
                    <flux:table.column>{{ __('Costo') }}</flux:table.column>
                    <flux:table.column>{{ __('Próximo') }}</flux:table.column>
                    @if (auth()->user()->esAdmin())
                        <flux:table.column></flux:table.column>
                    @endif
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->mantenimientos as $m)
                        <flux:table.row :key="$m->id">
                            <flux:table.cell class="text-sm whitespace-nowrap">
                                {{ $m->fecha_servicio->format('d/m/Y') }}
                            </flux:table.cell>

                            <flux:table.cell>
                                <div>
                                    <p class="text-sm font-medium">{{ $this->categoriaLabel($m->categoria) }}</p>
                                    @if ($m->descripcion)
                                        <p class="text-xs text-zinc-500 truncate max-w-48">{{ $m->descripcion }}</p>
                                    @endif
                                </div>
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:badge
                                    :color="$m->tipo === 'preventivo' ? 'blue' : 'amber'"
                                    size="sm"
                                >
                                    {{ ucfirst($m->tipo) }}
                                </flux:badge>
                            </flux:table.cell>

                            <flux:table.cell class="text-sm font-mono">
                                {{ $m->km_servicio ? number_format($m->km_servicio) : '—' }}
                            </flux:table.cell>

                            <flux:table.cell class="text-sm text-zinc-500">
                                {{ $m->taller ?? '—' }}
                            </flux:table.cell>

                            <flux:table.cell class="text-sm">
                                {{ $m->costo !== null ? 'S/ '.number_format($m->costo, 2) : '—' }}
                            </flux:table.cell>

                            <flux:table.cell>
                                @if ($m->proxima_fecha || $m->proximo_km)
                                    <div class="text-xs text-zinc-500 space-y-0.5">
                                        @if ($m->proxima_fecha)
                                            <p>{{ $m->proxima_fecha->format('d/m/Y') }}</p>
                                        @endif
                                        @if ($m->proximo_km)
                                            <p class="font-mono">{{ number_format($m->proximo_km) }} km</p>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-zinc-400">—</span>
                                @endif
                            </flux:table.cell>

                            @if (auth()->user()->esAdmin())
                                <flux:table.cell>
                                    <div class="flex gap-1">
                                        <flux:button
                                            wire:click="abrirEditar({{ $m->id }})"
                                            size="sm" variant="subtle" icon="pencil"
                                            inset="top bottom"
                                        />
                                        <flux:button
                                            wire:click="confirmDelete({{ $m->id }})"
                                            size="sm" variant="subtle" icon="trash"
                                            inset="top bottom"
                                        />
                                    </div>
                                </flux:table.cell>
                            @endif
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>

        {{-- Mobile --}}
        <div class="sm:hidden space-y-3">
            @foreach ($this->mantenimientos as $m)
                <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 p-4">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-medium text-sm">{{ $this->categoriaLabel($m->categoria) }}</span>
                                <flux:badge
                                    :color="$m->tipo === 'preventivo' ? 'blue' : 'amber'"
                                    size="sm"
                                >
                                    {{ ucfirst($m->tipo) }}
                                </flux:badge>
                            </div>
                            @if ($m->descripcion)
                                <p class="mt-0.5 text-xs text-zinc-500">{{ $m->descripcion }}</p>
                            @endif
                            <div class="mt-1 flex flex-wrap gap-x-3 gap-y-0.5 text-xs text-zinc-400">
                                <span>{{ $m->fecha_servicio->format('d/m/Y') }}</span>
                                @if ($m->km_servicio)
                                    <span class="font-mono">{{ number_format($m->km_servicio) }} km</span>
                                @endif
                                @if ($m->costo !== null)
                                    <span>S/ {{ number_format($m->costo, 2) }}</span>
                                @endif
                                @if ($m->taller)
                                    <span>{{ $m->taller }}</span>
                                @endif
                            </div>
                            @if ($m->proxima_fecha || $m->proximo_km)
                                <p class="mt-1 text-xs text-zinc-400">
                                    <span class="text-zinc-500 font-medium">{{ __('Próximo:') }}</span>
                                    @if ($m->proxima_fecha) {{ $m->proxima_fecha->format('d/m/Y') }} @endif
                                    @if ($m->proxima_fecha && $m->proximo_km) · @endif
                                    @if ($m->proximo_km) {{ number_format($m->proximo_km) }} km @endif
                                </p>
                            @endif
                        </div>
                        @if (auth()->user()->esAdmin())
                            <flux:dropdown position="bottom" align="end">
                                <flux:button variant="ghost" icon="ellipsis-vertical" size="sm" />
                                <flux:menu>
                                    <flux:menu.item
                                        icon="pencil"
                                        wire:click="abrirEditar({{ $m->id }})"
                                    >{{ __('Editar') }}</flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item
                                        icon="trash"
                                        variant="danger"
                                        wire:click="confirmDelete({{ $m->id }})"
                                    >{{ __('Eliminar') }}</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

    @else
        <div class="py-16 text-center">
            <flux:icon name="wrench-screwdriver" class="mx-auto mb-3 size-10 text-zinc-300 dark:text-zinc-600" />
            <flux:text>{{ __('No hay mantenimientos registrados.') }}</flux:text>
            @if (auth()->user()->esAdmin())
                <flux:button variant="ghost" size="sm" class="mt-3" wire:click="abrirCrear">
                    {{ __('Registrar primer servicio') }}
                </flux:button>
            @endif
        </div>
    @endif

    {{-- Modal crear / editar --}}
    <flux:modal wire:model.self="showModal" class="md:w-[42rem]">
        <div class="space-y-6">
            <flux:heading size="lg">
                {{ $editingId ? __('Editar servicio') : __('Nuevo servicio de mantenimiento') }}
            </flux:heading>

            <form wire:submit="guardar" class="space-y-4">

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:select wire:model="categoria" :label="__('Categoría')" required>
                        <flux:select.option value="">{{ __('Seleccionar') }}</flux:select.option>
                        <flux:select.option value="aceite_filtros">{{ __('Aceite y filtros') }}</flux:select.option>
                        <flux:select.option value="llantas">{{ __('Llantas') }}</flux:select.option>
                        <flux:select.option value="frenos">{{ __('Frenos') }}</flux:select.option>
                        <flux:select.option value="liquidos">{{ __('Líquidos') }}</flux:select.option>
                        <flux:select.option value="bateria">{{ __('Batería') }}</flux:select.option>
                        <flux:select.option value="alineacion_balanceo">{{ __('Alineación / balanceo') }}</flux:select.option>
                        <flux:select.option value="suspension">{{ __('Suspensión') }}</flux:select.option>
                        <flux:select.option value="transmision">{{ __('Transmisión') }}</flux:select.option>
                        <flux:select.option value="electricidad">{{ __('Electricidad') }}</flux:select.option>
                        <flux:select.option value="revision_general">{{ __('Revisión general') }}</flux:select.option>
                        <flux:select.option value="otro">{{ __('Otro') }}</flux:select.option>
                    </flux:select>

                    <flux:select wire:model="tipo" :label="__('Tipo')" required>
                        <flux:select.option value="">{{ __('Seleccionar') }}</flux:select.option>
                        <flux:select.option value="preventivo">{{ __('Preventivo') }}</flux:select.option>
                        <flux:select.option value="correctivo">{{ __('Correctivo') }}</flux:select.option>
                    </flux:select>
                </div>

                <flux:textarea
                    wire:model="descripcion"
                    :label="__('Descripción (opcional)')"
                    rows="2"
                    :placeholder="__('Detalles del servicio realizado...')"
                />

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <flux:input
                        wire:model="fechaServicio"
                        :label="__('Fecha del servicio')"
                        type="date"
                        required
                    />
                    <flux:input
                        wire:model="kmServicio"
                        :label="__('Km al momento')"
                        type="number"
                        min="0"
                        :placeholder="__('Ej: 45000')"
                    />
                    <flux:input
                        wire:model="costo"
                        :label="__('Costo (S/)')"
                        type="number"
                        step="0.01"
                        min="0"
                        :placeholder="__('Ej: 250.00')"
                    />
                </div>

                <flux:input
                    wire:model="taller"
                    :label="__('Taller / proveedor (opcional)')"
                    :placeholder="__('Nombre del taller o mecánico')"
                />

                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4 space-y-4">
                    <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('Próximo mantenimiento programado') }}
                    </p>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <flux:input
                            wire:model="proximaFecha"
                            :label="__('Fecha programada')"
                            type="date"
                        />
                        <flux:input
                            wire:model="proximoKm"
                            :label="__('Km programado')"
                            type="number"
                            min="0"
                            :placeholder="__('Ej: 50000')"
                        />
                    </div>
                </div>

                <flux:textarea
                    wire:model="observaciones"
                    :label="__('Observaciones (opcional)')"
                    rows="2"
                />

                <div class="flex justify-end gap-2 pt-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">{{ __('Cancelar') }}</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="guardar">
                            {{ $editingId ? __('Guardar cambios') : __('Registrar') }}
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
                <flux:heading size="lg">{{ __('Eliminar servicio') }}</flux:heading>
                <flux:text class="mt-2">{{ __('¿Eliminar este registro de mantenimiento? La acción no se puede deshacer.') }}</flux:text>
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

</div>
