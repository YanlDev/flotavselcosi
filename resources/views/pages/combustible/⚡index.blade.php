<?php

use App\Models\RegistroCombustible;
use App\Models\Sucursal;
use App\Models\Vehiculo;
use App\Services\CombustibleAnalyticsService;
use App\Services\StorageService;
use Carbon\CarbonImmutable;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new #[Title('Combustible')] class extends Component {
    use WithFileUploads, WithPagination;

    // ── Tabs ───────────────────────────────────────────────
    #[Url]
    public string $tab = 'resumen'; // resumen | cargas

    // ── Filtros analíticos (afectan KPIs y gráficos) ───────
    #[Url]
    public string $preset = 'mes_actual'; // mes_actual | mes_pasado | ult_3 | ult_12 | ano | personalizado

    #[Url]
    public string $desde = '';

    #[Url]
    public string $hasta = '';

    #[Url]
    public string $filterVehiculo = '';

    #[Url]
    public string $tipoCombustible = '';

    // ── Filtros lista de cargas ────────────────────────────
    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $filterEstado = '';

    #[Url]
    public string $filterSucursal = '';

    // ── Modal eliminar ─────────────────────────────────────
    public bool $showDeleteModal = false;

    public ?int $deletingId = null;

    // ── Modal nueva carga ──────────────────────────────────
    public bool $showCrearModal = false;

    public int|string $formVehiculoId = '';

    public ?TemporaryUploadedFile $fotoFactura = null;

    public ?TemporaryUploadedFile $fotoOdometro = null;

    public string $observacionesEnvio = '';

    public function mount(): void
    {
        $this->aplicarPreset(refrescarPersonalizado: false);
    }

    public function updatedPreset(): void
    {
        $this->aplicarPreset();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterEstado(): void
    {
        $this->resetPage();
    }

    public function updatedFilterSucursal(): void
    {
        $this->resetPage();
    }

    public function updatedFilterVehiculo(): void
    {
        $this->resetPage();
    }

    public function updatedTipoCombustible(): void
    {
        $this->resetPage();
    }

    public function updatedDesde(): void
    {
        if ($this->preset !== 'personalizado') {
            $this->preset = 'personalizado';
        }
        $this->resetPage();
    }

    public function updatedHasta(): void
    {
        if ($this->preset !== 'personalizado') {
            $this->preset = 'personalizado';
        }
        $this->resetPage();
    }

    private function aplicarPreset(bool $refrescarPersonalizado = true): void
    {
        $hoy = CarbonImmutable::now();

        [$desde, $hasta] = match ($this->preset) {
            'mes_pasado' => [
                $hoy->subMonthNoOverflow()->startOfMonth(),
                $hoy->subMonthNoOverflow()->endOfMonth(),
            ],
            'ult_3' => [$hoy->subMonthsNoOverflow(2)->startOfMonth(), $hoy->endOfDay()],
            'ult_12' => [$hoy->subMonthsNoOverflow(11)->startOfMonth(), $hoy->endOfDay()],
            'ano' => [$hoy->startOfYear(), $hoy->endOfDay()],
            'personalizado' => $refrescarPersonalizado ? [null, null] : [null, null],
            default => [$hoy->startOfMonth(), $hoy->endOfDay()],
        };

        if ($this->preset !== 'personalizado' && $desde && $hasta) {
            $this->desde = $desde->toDateString();
            $this->hasta = $hasta->toDateString();
        }

        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'filterEstado', 'filterSucursal', 'filterVehiculo', 'tipoCombustible']);
        $this->preset = 'mes_actual';
        $this->aplicarPreset();
    }

    #[Computed]
    public function hasListFilters(): bool
    {
        return $this->search !== '' || $this->filterEstado !== '' || $this->filterSucursal !== '' || $this->filterVehiculo !== '';
    }

    /** @return array<string, mixed> */
    #[Computed]
    public function filtrosAnalytics(): array
    {
        return [
            'vehiculo_id' => $this->filterVehiculo !== '' ? (int) $this->filterVehiculo : null,
            'sucursal_id' => $this->filterSucursal !== '' ? (int) $this->filterSucursal : null,
            'tipo_combustible' => $this->tipoCombustible ?: null,
            'desde' => $this->desde ?: null,
            'hasta' => $this->hasta ?: null,
        ];
    }

    /** @return array<string, mixed> */
    #[Computed]
    public function kpisAnalytics(): array
    {
        return app(CombustibleAnalyticsService::class)
            ->kpis(auth()->user(), $this->filtrosAnalytics);
    }

    /** @return array<string, mixed> */
    #[Computed]
    public function chartData(): array
    {
        $svc = app(CombustibleAnalyticsService::class);
        $u = auth()->user();
        $f = $this->filtrosAnalytics;

        return [
            'porMes' => $svc->porMes($u, $f),
            'porSucursal' => $svc->porSucursal($u, $f),
            'porTipoCombustible' => $svc->porTipoCombustible($u, $f),
        ];
    }

    #[Computed]
    public function topVehiculos(): \Illuminate\Support\Collection
    {
        return app(CombustibleAnalyticsService::class)
            ->topVehiculos(auth()->user(), $this->filtrosAnalytics, 10);
    }

    #[Computed]
    public function filtrosHash(): string
    {
        return md5(json_encode([
            $this->filtrosAnalytics,
            $this->kpisAnalytics,
        ]));
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
            ->when($this->filterVehiculo, fn ($q) => $q->where('vehiculo_id', (int) $this->filterVehiculo))
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
        $this->reset(['formVehiculoId', 'fotoFactura', 'fotoOdometro', 'observacionesEnvio']);
        $this->showCrearModal = true;
    }

    public function guardar(StorageService $storage): void
    {
        abort_unless(
            auth()->user()->esAdmin() || auth()->user()->esJefeResguardo(),
            403
        );

        $this->validate([
            'formVehiculoId' => ['required', 'exists:vehiculos,id'],
            'fotoFactura' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:20480'],
            'fotoOdometro' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:10240'],
            'observacionesEnvio' => ['nullable', 'string', 'max:1000'],
        ]);

        $vehiculo = Vehiculo::findOrFail($this->formVehiculoId);

        if (auth()->user()->esJefeResguardo()) {
            abort_unless($vehiculo->sucursal_id === auth()->user()->sucursal_id, 403);
        }

        $facturaKey = $storage->upload($this->fotoFactura, "combustible/{$vehiculo->id}/facturas");
        $odometroKey = $storage->upload($this->fotoOdometro, "combustible/{$vehiculo->id}/odometros");

        RegistroCombustible::create([
            'vehiculo_id' => $vehiculo->id,
            'sucursal_id' => $vehiculo->sucursal_id,
            'enviado_por' => auth()->id(),
            'foto_factura_key' => $facturaKey,
            'foto_odometro_key' => $odometroKey,
            'observaciones_envio' => $this->observacionesEnvio ?: null,
            'estado' => 'pendiente',
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

    public function estadoLabel(string $estado): string
    {
        return match ($estado) {
            'pendiente' => 'Pendiente',
            'aprobado' => 'Aprobado',
            'rechazado' => 'Rechazado',
            default => $estado,
        };
    }
}; ?>

<section class="w-full px-3 py-4 sm:p-6 lg:p-8">

    <x-ui.page-header
        :title="__('Combustible')"
        :subtitle="__('Histórico, gastos y registros de cargas')"
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

    {{-- Tabs --}}
    <div class="mb-5 border-b border-slate-200 dark:border-slate-800">
        <nav class="-mb-px flex gap-1">
            <button
                type="button"
                wire:click="$set('tab', 'resumen')"
                @class([
                    'inline-flex items-center gap-2 border-b-2 px-3 py-2 text-sm font-medium transition-colors',
                    'border-brand-500 text-brand-600 dark:text-brand-400' => $tab === 'resumen',
                    'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' => $tab !== 'resumen',
                ])
            >
                <flux:icon name="chart-bar" class="size-4" />
                {{ __('Resumen') }}
            </button>
            <button
                type="button"
                wire:click="$set('tab', 'cargas')"
                @class([
                    'inline-flex items-center gap-2 border-b-2 px-3 py-2 text-sm font-medium transition-colors',
                    'border-brand-500 text-brand-600 dark:text-brand-400' => $tab === 'cargas',
                    'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' => $tab !== 'cargas',
                ])
            >
                <flux:icon name="list-bullet" class="size-4" />
                {{ __('Cargas') }}
            </button>
        </nav>
    </div>

    {{-- ════════════════════════════════════════════════════════════
         TAB RESUMEN
         ════════════════════════════════════════════════════════════ --}}
    @if ($tab === 'resumen')

        {{-- Filtros analíticos --}}
        <div class="mb-5 rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                <flux:select wire:model.live="preset" :label="__('Periodo')" size="sm">
                    <flux:select.option value="mes_actual">{{ __('Mes actual') }}</flux:select.option>
                    <flux:select.option value="mes_pasado">{{ __('Mes pasado') }}</flux:select.option>
                    <flux:select.option value="ult_3">{{ __('Últimos 3 meses') }}</flux:select.option>
                    <flux:select.option value="ult_12">{{ __('Últimos 12 meses') }}</flux:select.option>
                    <flux:select.option value="ano">{{ __('Este año') }}</flux:select.option>
                    <flux:select.option value="personalizado">{{ __('Personalizado') }}</flux:select.option>
                </flux:select>

                <flux:input type="date" wire:model.live="desde" :label="__('Desde')" size="sm" />
                <flux:input type="date" wire:model.live="hasta" :label="__('Hasta')" size="sm" />

                <flux:select wire:model.live="filterVehiculo" :label="__('Vehículo')" size="sm">
                    <flux:select.option value="">{{ __('Todos') }}</flux:select.option>
                    @foreach ($this->vehiculosDisponibles as $v)
                        <flux:select.option :value="$v->id">{{ $v->placa }}</flux:select.option>
                    @endforeach
                </flux:select>

                @if (auth()->user()->puedeVerTodo())
                    <flux:select wire:model.live="filterSucursal" :label="__('Sucursal')" size="sm">
                        <flux:select.option value="">{{ __('Todas') }}</flux:select.option>
                        @foreach ($this->sucursales as $sucursal)
                            <flux:select.option :value="$sucursal->id">{{ $sucursal->nombre }}</flux:select.option>
                        @endforeach
                    </flux:select>
                @endif

                <flux:select wire:model.live="tipoCombustible" :label="__('Tipo')" size="sm">
                    <flux:select.option value="">{{ __('Todos') }}</flux:select.option>
                    <flux:select.option value="gasolina">{{ __('Gasolina') }}</flux:select.option>
                    <flux:select.option value="diesel">{{ __('Diesel') }}</flux:select.option>
                    <flux:select.option value="glp">{{ __('GLP') }}</flux:select.option>
                    <flux:select.option value="gnv">{{ __('GNV') }}</flux:select.option>
                </flux:select>
            </div>

            <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">
                {{ __('Solo cargas aprobadas.') }}
                @if ($this->desde && $this->hasta)
                    {{ __('Periodo:') }}
                    <span class="font-mono-data text-slate-700 dark:text-slate-300">
                        {{ \Carbon\Carbon::parse($this->desde)->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($this->hasta)->format('d/m/Y') }}
                    </span>
                @endif
            </p>
        </div>

        {{-- KPIs --}}
        <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-5">
            <x-ui.stat-card
                :label="__('Galones cargados')"
                :value="number_format($this->kpisAnalytics['galones'], 1)"
                icon="fire"
                color="info"
            />
            <x-ui.stat-card
                :label="__('Gasto total')"
                :value="'S/ ' . number_format($this->kpisAnalytics['monto'], 2)"
                icon="currency-dollar"
                color="brand"
            />
            <x-ui.stat-card
                :label="__('Cargas')"
                :value="$this->kpisAnalytics['cargas']"
                icon="document-text"
                color="slate"
            />
            <x-ui.stat-card
                :label="__('Vehículos activos')"
                :value="$this->kpisAnalytics['vehiculos']"
                icon="truck"
                color="slate"
            />
            <x-ui.stat-card
                :label="__('S/ por galón')"
                :value="'S/ ' . number_format($this->kpisAnalytics['precio_promedio'], 2)"
                icon="calculator"
                color="warning"
                :hint="__('promedio')"
            />
        </div>

        {{-- Charts --}}
        <div wire:key="charts-{{ $this->filtrosHash }}" wire:ignore>
            <div
                x-data="combustibleCharts(@js($this->chartData))"
                x-init="$nextTick(() => render())"
                x-destroy="destroy()"
            >
                <div class="grid gap-6 lg:grid-cols-2">
                    {{-- Gasto y galones por mes --}}
                    <x-ui.section-card :title="__('Gasto y galones por mes')">
                        <div class="h-72">
                            <canvas x-ref="mes"></canvas>
                        </div>
                    </x-ui.section-card>

                    {{-- Top vehículos --}}
                    <x-ui.section-card :title="__('Top vehículos por gasto')">
                        @if ($this->topVehiculos->isNotEmpty())
                            <div class="h-72">
                                <canvas x-ref="topVehiculos"
                                    data-labels="{{ json_encode($this->topVehiculos->pluck('placa')) }}"
                                    data-monto="{{ json_encode($this->topVehiculos->pluck('monto')) }}"
                                ></canvas>
                            </div>
                        @else
                            <x-ui.empty-state icon="truck" :title="__('Sin datos en el periodo')" />
                        @endif
                    </x-ui.section-card>

                    @if (auth()->user()->puedeVerTodo() && count($this->chartData['porSucursal']['labels']) > 0)
                        <x-ui.section-card :title="__('Gasto por sucursal')">
                            <div class="h-72">
                                <canvas x-ref="sucursal"></canvas>
                            </div>
                        </x-ui.section-card>
                    @endif

                    @if (count($this->chartData['porTipoCombustible']['labels']) > 0)
                        <x-ui.section-card :title="__('Cargas por tipo de combustible')">
                            <div class="h-72">
                                <canvas x-ref="tipo"></canvas>
                            </div>
                        </x-ui.section-card>
                    @endif
                </div>
            </div>
        </div>

        {{-- Tabla top vehículos detalle --}}
        @if ($this->topVehiculos->isNotEmpty())
            <div class="mt-6">
                <x-ui.section-card :title="__('Detalle por vehículo')" :padded="false">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                            <thead class="bg-slate-50 dark:bg-slate-800/50">
                                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                    <th class="px-5 py-3">{{ __('Vehículo') }}</th>
                                    <th class="px-5 py-3 text-right">{{ __('Cargas') }}</th>
                                    <th class="px-5 py-3 text-right">{{ __('Galones') }}</th>
                                    <th class="px-5 py-3 text-right">{{ __('Gasto') }}</th>
                                    <th class="px-5 py-3 text-right">{{ __('S/ por galón') }}</th>
                                    <th class="px-5 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-sm dark:divide-slate-800">
                                @foreach ($this->topVehiculos as $v)
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                        <td class="px-5 py-3">
                                            <p class="font-mono-data font-semibold text-slate-900 dark:text-white">{{ $v->placa }}</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $v->marca }} {{ $v->modelo }}</p>
                                        </td>
                                        <td class="px-5 py-3 text-right font-mono-data text-slate-700 dark:text-slate-300">{{ $v->cargas }}</td>
                                        <td class="px-5 py-3 text-right font-mono-data text-slate-700 dark:text-slate-300">{{ number_format($v->galones, 1) }}</td>
                                        <td class="px-5 py-3 text-right font-mono-data font-semibold text-slate-900 dark:text-white">S/ {{ number_format($v->monto, 2) }}</td>
                                        <td class="px-5 py-3 text-right font-mono-data text-slate-500 dark:text-slate-400">
                                            {{ $v->galones > 0 ? 'S/ ' . number_format($v->monto / $v->galones, 2) : '—' }}
                                        </td>
                                        <td class="px-5 py-3 text-right">
                                            <flux:button
                                                size="sm" variant="ghost" icon="arrow-right"
                                                wire:click="$set('filterVehiculo', '{{ $v->vehiculo_id }}'); $set('tab', 'cargas')"
                                            />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-ui.section-card>
            </div>
        @endif

    @else
        {{-- ════════════════════════════════════════════════════════════
             TAB CARGAS (listado)
             ════════════════════════════════════════════════════════════ --}}

        {{-- Filtros lista --}}
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
                <flux:select wire:model.live="filterVehiculo" class="sm:w-44">
                    <flux:select.option value="">{{ __('Vehículo') }}</flux:select.option>
                    @foreach ($this->vehiculosDisponibles as $v)
                        <flux:select.option :value="$v->id">{{ $v->placa }}</flux:select.option>
                    @endforeach
                </flux:select>

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

            @if ($this->hasListFilters)
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

                <flux:select wire:model="formVehiculoId" :label="__('Vehículo')" required>
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

@script
<script>
    Alpine.data('combustibleCharts', (data) => ({
        instances: [],

        render() {
            const isDark = document.documentElement.classList.contains('dark');
            const grid = isDark ? 'rgba(148, 163, 184, 0.15)' : 'rgba(100, 116, 139, 0.15)';
            const text = isDark ? '#cbd5e1' : '#475569';

            const palette = {
                brand: '#10b981',
                info: '#0ea5e9',
                amber: '#f59e0b',
                red: '#ef4444',
                violet: '#8b5cf6',
                slate: '#64748b',
            };
            const donutColors = [palette.brand, palette.info, palette.amber, palette.violet, palette.red, palette.slate];

            const commonOpts = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { color: text } },
                    tooltip: { mode: 'index', intersect: false },
                },
                scales: {
                    x: { grid: { color: grid }, ticks: { color: text } },
                    y: { grid: { color: grid }, ticks: { color: text }, beginAtZero: true },
                },
            };

            // 1) Gasto y galones por mes (línea doble eje)
            if (this.$refs.mes && data.porMes.labels.length) {
                this.instances.push(new window.Chart(this.$refs.mes, {
                    type: 'line',
                    data: {
                        labels: data.porMes.labels,
                        datasets: [
                            {
                                label: 'Gasto (S/)',
                                data: data.porMes.monto,
                                borderColor: palette.brand,
                                backgroundColor: palette.brand + '33',
                                tension: 0.3,
                                fill: true,
                                yAxisID: 'y',
                            },
                            {
                                label: 'Galones',
                                data: data.porMes.galones,
                                borderColor: palette.info,
                                backgroundColor: palette.info + '22',
                                tension: 0.3,
                                yAxisID: 'y1',
                            },
                        ],
                    },
                    options: {
                        ...commonOpts,
                        scales: {
                            x: commonOpts.scales.x,
                            y: { ...commonOpts.scales.y, position: 'left', title: { display: true, text: 'S/', color: text } },
                            y1: { ...commonOpts.scales.y, position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'Gal', color: text } },
                        },
                    },
                }));
            }

            // 2) Top vehículos (barras horizontales)
            if (this.$refs.topVehiculos) {
                const labels = JSON.parse(this.$refs.topVehiculos.dataset.labels || '[]');
                const monto = JSON.parse(this.$refs.topVehiculos.dataset.monto || '[]');
                if (labels.length) {
                    this.instances.push(new window.Chart(this.$refs.topVehiculos, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Gasto (S/)',
                                data: monto,
                                backgroundColor: palette.brand,
                                borderRadius: 4,
                            }],
                        },
                        options: {
                            ...commonOpts,
                            indexAxis: 'y',
                            plugins: { ...commonOpts.plugins, legend: { display: false } },
                        },
                    }));
                }
            }

            // 3) Por sucursal (donut)
            if (this.$refs.sucursal && data.porSucursal.labels.length) {
                this.instances.push(new window.Chart(this.$refs.sucursal, {
                    type: 'doughnut',
                    data: {
                        labels: data.porSucursal.labels,
                        datasets: [{
                            data: data.porSucursal.monto,
                            backgroundColor: donutColors,
                            borderWidth: 2,
                            borderColor: isDark ? '#0f172a' : '#ffffff',
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom', labels: { color: text } } },
                    },
                }));
            }

            // 4) Por tipo de combustible (donut)
            if (this.$refs.tipo && data.porTipoCombustible.labels.length) {
                this.instances.push(new window.Chart(this.$refs.tipo, {
                    type: 'doughnut',
                    data: {
                        labels: data.porTipoCombustible.labels,
                        datasets: [{
                            data: data.porTipoCombustible.cargas,
                            backgroundColor: donutColors,
                            borderWidth: 2,
                            borderColor: isDark ? '#0f172a' : '#ffffff',
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom', labels: { color: text } } },
                    },
                }));
            }
        },

        destroy() {
            this.instances.forEach((c) => c.destroy());
            this.instances = [];
        },
    }));
</script>
@endscript
