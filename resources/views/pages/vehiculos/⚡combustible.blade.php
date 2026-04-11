<?php

use App\Models\RegistroCombustible;
use App\Models\Vehiculo;
use App\Services\StorageService;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

new class extends Component {
    use WithFileUploads;

    public Vehiculo $vehiculo;

    // Modal nueva carga
    public bool $showCrearModal = false;
    public ?TemporaryUploadedFile $fotoFactura = null;
    public ?TemporaryUploadedFile $fotoOdometro = null;
    public string $observacionesEnvio = '';

    public function mount(Vehiculo $vehiculo): void
    {
        $this->authorize('view', $vehiculo);
        $this->vehiculo = $vehiculo;

        if (request()->boolean('registrar') && $this->puedeRegistrar()) {
            $this->showCrearModal = true;
        }
    }

    #[Computed]
    public function historial(): \Illuminate\Database\Eloquent\Collection
    {
        return RegistroCombustible::where('vehiculo_id', $this->vehiculo->id)
            ->aprobados()
            ->with('enviadoPor', 'revisadoPor')
            ->orderByDesc('fecha_carga')
            ->get();
    }

    #[Computed]
    public function totales(): array
    {
        $registros = $this->historial;

        $totalGalones = $registros->sum('galones');
        $totalMonto = $registros->sum('monto_total');

        // km/galón promedio: (km_max - km_min) / total_galones
        $kms = $registros->whereNotNull('km_al_cargar')->pluck('km_al_cargar')->sort()->values();
        $kmPorGalon = null;
        if ($kms->count() >= 2 && $totalGalones > 0) {
            $kmPorGalon = round(($kms->last() - $kms->first()) / $totalGalones, 2);
        }

        return [
            'total_galones' => $totalGalones,
            'total_monto'   => $totalMonto,
            'km_por_galon'  => $kmPorGalon,
            'total_cargas'  => $registros->count(),
        ];
    }

    public function puedeRegistrar(): bool
    {
        $user = auth()->user();

        if ($user->esAdmin()) {
            return true;
        }

        if ($user->esJefeResguardo()) {
            return $user->sucursal_id === $this->vehiculo->sucursal_id;
        }

        return false;
    }

    public function abrirCrear(): void
    {
        abort_unless($this->puedeRegistrar(), 403);
        $this->reset(['fotoFactura', 'fotoOdometro', 'observacionesEnvio']);
        $this->showCrearModal = true;
    }

    public function guardar(StorageService $storage): void
    {
        abort_unless($this->puedeRegistrar(), 403);

        $this->validate([
            'fotoFactura'        => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:20480'],
            'fotoOdometro'       => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:10240'],
            'observacionesEnvio' => ['nullable', 'string', 'max:1000'],
        ]);

        $facturaKey  = $storage->upload($this->fotoFactura, "combustible/{$this->vehiculo->id}/facturas");
        $odometroKey = $storage->upload($this->fotoOdometro, "combustible/{$this->vehiculo->id}/odometros");

        RegistroCombustible::create([
            'vehiculo_id'         => $this->vehiculo->id,
            'sucursal_id'         => $this->vehiculo->sucursal_id,
            'enviado_por'         => auth()->id(),
            'foto_factura_key'    => $facturaKey,
            'foto_odometro_key'   => $odometroKey,
            'observaciones_envio' => $this->observacionesEnvio ?: null,
            'estado'              => 'pendiente',
        ]);

        unset($this->historial);
        $this->showCrearModal = false;
    }

    public function tipoCombustibleLabel(?string $tipo): string
    {
        return match ($tipo) {
            'gasolina'  => 'Gasolina',
            'diesel'    => 'Diésel',
            'glp'       => 'GLP',
            'gnv'       => 'GNV',
            'electrico' => 'Eléctrico',
            'hibrido'   => 'Híbrido',
            default     => $tipo ?? '—',
        };
    }
}; ?>

<div class="space-y-6">

    {{-- Encabezado --}}
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="sm">{{ __('Historial de combustible') }}</flux:heading>
            <flux:text class="text-xs text-zinc-500">{{ __('Solo registros aprobados.') }}</flux:text>
        </div>
        @if ($this->puedeRegistrar())
            <flux:button
                variant="primary"
                icon="plus"
                size="sm"
                wire:click="abrirCrear"
            >
                {{ __('Registrar carga') }}
            </flux:button>
        @endif
    </div>

    {{-- KPIs --}}
    @if ($this->totales['total_cargas'] > 0)
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 p-4 text-center">
                <p class="text-2xl font-bold text-zinc-800 dark:text-zinc-100">
                    {{ $this->totales['total_cargas'] }}
                </p>
                <p class="mt-1 text-xs text-zinc-500">{{ __('Cargas') }}</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 p-4 text-center">
                <p class="text-2xl font-bold text-zinc-800 dark:text-zinc-100">
                    {{ number_format($this->totales['total_galones'], 2) }}
                </p>
                <p class="mt-1 text-xs text-zinc-500">{{ __('Galones totales') }}</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 p-4 text-center">
                <p class="text-2xl font-bold text-zinc-800 dark:text-zinc-100">
                    S/ {{ number_format($this->totales['total_monto'], 2) }}
                </p>
                <p class="mt-1 text-xs text-zinc-500">{{ __('Monto total') }}</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 p-4 text-center">
                <p class="text-2xl font-bold text-zinc-800 dark:text-zinc-100">
                    {{ $this->totales['km_por_galon'] !== null ? number_format($this->totales['km_por_galon'], 1) : '—' }}
                </p>
                <p class="mt-1 text-xs text-zinc-500">{{ __('Km / galón') }}</p>
            </div>
        </div>
    @endif

    {{-- Tabla desktop --}}
    @if ($this->historial->isNotEmpty())
        <div class="hidden sm:block overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Fecha carga') }}</flux:table.column>
                    <flux:table.column>{{ __('Km') }}</flux:table.column>
                    <flux:table.column>{{ __('Galones') }}</flux:table.column>
                    <flux:table.column>{{ __('S/ / gal') }}</flux:table.column>
                    <flux:table.column>{{ __('Monto') }}</flux:table.column>
                    <flux:table.column>{{ __('Tipo') }}</flux:table.column>
                    <flux:table.column>{{ __('Proveedor') }}</flux:table.column>
                    <flux:table.column>{{ __('Enviado por') }}</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->historial as $registro)
                        <flux:table.row :key="$registro->id">
                            <flux:table.cell class="text-sm">
                                {{ $registro->fecha_carga?->format('d/m/Y') ?? '—' }}
                            </flux:table.cell>
                            <flux:table.cell class="text-sm font-mono">
                                {{ $registro->km_al_cargar ? number_format($registro->km_al_cargar) : '—' }}
                            </flux:table.cell>
                            <flux:table.cell class="text-sm font-mono">
                                {{ $registro->galones }}
                            </flux:table.cell>
                            <flux:table.cell class="text-sm font-mono">
                                {{ $registro->precio_galon }}
                            </flux:table.cell>
                            <flux:table.cell class="text-sm font-semibold">
                                S/ {{ $registro->monto_total }}
                            </flux:table.cell>
                            <flux:table.cell class="text-sm">
                                {{ $this->tipoCombustibleLabel($registro->tipo_combustible) }}
                            </flux:table.cell>
                            <flux:table.cell class="text-sm text-zinc-500">
                                {{ $registro->proveedor ?? '—' }}
                            </flux:table.cell>
                            <flux:table.cell class="text-sm text-zinc-500">
                                {{ $registro->enviadoPor?->name ?? '—' }}
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:button
                                    :href="route('combustible.show', $registro)"
                                    size="sm" variant="subtle" icon="eye"
                                    inset="top bottom"
                                    wire:navigate
                                />
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>

        {{-- Cards mobile --}}
        <div class="sm:hidden space-y-3">
            @foreach ($this->historial as $registro)
                <a
                    href="{{ route('combustible.show', $registro) }}"
                    wire:navigate
                    class="block rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 p-4"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-sm">
                                    {{ $registro->fecha_carga?->format('d/m/Y') ?? '—' }}
                                </span>
                                <flux:badge color="green" size="sm">{{ __('Aprobado') }}</flux:badge>
                            </div>
                            <p class="mt-0.5 text-sm font-bold">
                                S/ {{ $registro->monto_total }}
                                <span class="font-normal text-zinc-500">· {{ $registro->galones }} gal</span>
                            </p>
                            <p class="mt-0.5 text-xs text-zinc-400">
                                {{ $this->tipoCombustibleLabel($registro->tipo_combustible) }}
                                @if ($registro->proveedor) · {{ $registro->proveedor }} @endif
                            </p>
                        </div>
                        <flux:icon name="chevron-right" class="size-5 shrink-0 text-zinc-400 mt-0.5" />
                    </div>
                </a>
            @endforeach
        </div>

    @else
        <div class="py-16 text-center">
            <flux:icon name="fire" class="mx-auto mb-3 size-10 text-zinc-300 dark:text-zinc-600" />
            <flux:text>{{ __('No hay cargas de combustible aprobadas.') }}</flux:text>
            @if ($this->puedeRegistrar())
                <flux:button
                    variant="ghost"
                    size="sm"
                    class="mt-3"
                    wire:click="abrirCrear"
                >
                    {{ __('Registrar primera carga') }}
                </flux:button>
            @endif
        </div>
    @endif

    {{-- Modal: Nueva carga --}}
    <flux:modal wire:model.self="showCrearModal" class="md:w-[36rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Registrar carga de combustible') }}</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500">
                    {{ $vehiculo->placa }} — {{ $vehiculo->marca }} {{ $vehiculo->modelo }}
                </flux:text>
            </div>

            <form wire:submit="guardar" class="space-y-4">
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

</div>
