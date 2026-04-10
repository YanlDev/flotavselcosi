<?php

use App\Models\Conductor;
use App\Models\DocumentoVehicular;
use App\Models\Mantenimiento;
use App\Models\RegistroCombustible;
use App\Models\Sucursal;
use App\Models\Vehiculo;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component {

    #[Computed]
    public function kpis(): array
    {
        $user     = auth()->user();
        $cacheKey = "dashboard.kpis.{$user->id}";

        return Cache::remember($cacheKey, 300, function () use ($user) {
            $sucursalId = $user->puedeVerTodo() ? null : $user->sucursal_id;

            // Flota
            $baseFlota = Vehiculo::when($sucursalId, fn ($q) => $q->where('sucursal_id', $sucursalId));

            $totalFlota           = (clone $baseFlota)->count();
            $totalOperativos      = (clone $baseFlota)->where('estado', 'operativo')->count();
            $totalParciales       = (clone $baseFlota)->where('estado', 'parcialmente')->count();
            $totalFueraServicio   = (clone $baseFlota)->where('estado', 'fuera_de_servicio')->count();

            // Combustible este mes (aprobados)
            $baseCombustible = RegistroCombustible::aprobados()
                ->whereMonth('fecha_carga', now()->month)
                ->whereYear('fecha_carga', now()->year)
                ->when($sucursalId, fn ($q) => $q->where('sucursal_id', $sucursalId));

            $galonesEsteMes = (clone $baseCombustible)->sum('galones');
            $montoEsteMes   = (clone $baseCombustible)->sum('monto_total');

            // Documentos próximos a vencer (30 días) o vencidos
            $docsAlerta = DocumentoVehicular::whereNotNull('vencimiento')
                ->where('vencimiento', '<=', now()->addDays(30))
                ->when($sucursalId, fn ($q) => $q->whereHas(
                    'vehiculo',
                    fn ($v) => $v->where('sucursal_id', $sucursalId)
                ))
                ->count();

            // Mantenimientos urgentes (próxima fecha ≤30 días o km restantes ≤1000)
            $kmActualesSubquery = Vehiculo::selectRaw('id, km_actuales')
                ->when($sucursalId, fn ($q) => $q->where('sucursal_id', $sucursalId))
                ->pluck('km_actuales', 'id');

            $mantUrgentes = Mantenimiento::whereHas('vehiculo', function ($q) use ($sucursalId) {
                $q->when($sucursalId, fn ($v) => $v->where('sucursal_id', $sucursalId));
            })
                ->where(function ($q) use ($kmActualesSubquery) {
                    $q->where(fn ($q2) => $q2
                        ->whereNotNull('proxima_fecha')
                        ->where('proxima_fecha', '<=', now()->addDays(30))
                    )->orWhere(function ($q2) use ($kmActualesSubquery) {
                        $q2->whereNotNull('proximo_km');
                        foreach ($kmActualesSubquery as $vehiculoId => $kmActuales) {
                            if ($kmActuales !== null) {
                                $q2->orWhere(function ($q3) use ($vehiculoId, $kmActuales) {
                                    $q3->where('vehiculo_id', $vehiculoId)
                                        ->whereRaw('proximo_km - ? <= 1000', [$kmActuales]);
                                });
                            }
                        }
                    });
                })
                ->count();

            // Combustible pendiente de revisión (admin y visor)
            $combustiblePendiente = $user->puedeVerTodo()
                ? RegistroCombustible::where('estado', 'pendiente')->count()
                : null;

            return compact(
                'totalFlota', 'totalOperativos', 'totalParciales', 'totalFueraServicio',
                'galonesEsteMes', 'montoEsteMes',
                'docsAlerta', 'mantUrgentes', 'combustiblePendiente'
            );
        });
    }

    #[Computed]
    public function ultimosVehiculos(): \Illuminate\Database\Eloquent\Collection
    {
        $user = auth()->user();

        return Vehiculo::forUser($user)
            ->with('sucursal')
            ->latest()
            ->limit(5)
            ->get(['id', 'placa', 'marca', 'modelo', 'tipo', 'estado', 'sucursal_id', 'created_at']);
    }

    #[Computed]
    public function flotaPorSucursal(): \Illuminate\Database\Eloquent\Collection
    {
        if (! auth()->user()->puedeVerTodo()) {
            return collect();
        }

        return Sucursal::activas()
            ->withCount([
                'vehiculos',
                'vehiculos as operativos_count'     => fn ($q) => $q->where('estado', 'operativo'),
                'vehiculos as parciales_count'      => fn ($q) => $q->where('estado', 'parcialmente'),
                'vehiculos as fuera_servicio_count' => fn ($q) => $q->where('estado', 'fuera_de_servicio'),
            ])
            ->orderByDesc('vehiculos_count')
            ->get();
    }

    #[Computed]
    public function pendientesRecientes(): \Illuminate\Database\Eloquent\Collection
    {
        if (! auth()->user()->puedeVerTodo()) {
            return collect();
        }

        return RegistroCombustible::where('estado', 'pendiente')
            ->with(['vehiculo', 'sucursal', 'enviadoPor'])
            ->latest()
            ->limit(5)
            ->get();
    }

    #[Computed]
    public function misEnviosRecientes(): \Illuminate\Database\Eloquent\Collection
    {
        if (auth()->user()->puedeVerTodo()) {
            return collect();
        }

        return RegistroCombustible::where('enviado_por', auth()->id())
            ->with('vehiculo')
            ->latest()
            ->limit(5)
            ->get();
    }

    public function estadoBadgeColor(string $estado): string
    {
        return match ($estado) {
            'operativo'        => 'green',
            'parcialmente'     => 'amber',
            'fuera_de_servicio' => 'red',
            default            => 'zinc',
        };
    }

    public function estadoLabel(string $estado): string
    {
        return match ($estado) {
            'operativo'        => 'Operativo',
            'parcialmente'     => 'Parcial',
            'fuera_de_servicio' => 'Fuera de servicio',
            default            => $estado,
        };
    }
}; ?>

<section class="w-full px-3 py-4 sm:p-6 lg:p-8">

    <x-ui.page-header
        :title="__('Dashboard')"
        :subtitle="__('Bienvenido,') . ' ' . auth()->user()->name . (! auth()->user()->puedeVerTodo() && auth()->user()->sucursal ? ' · ' . auth()->user()->sucursal->nombre : '')"
    />

    {{-- Combustible pendiente (solo admin) --}}
    @if (auth()->user()->puedeVerTodo() && $this->kpis['combustiblePendiente'] > 0)
        <div class="mb-6">
            <flux:callout color="amber" icon="clock">
                <flux:callout.heading>
                    {{ $this->kpis['combustiblePendiente'] }}
                    {{ $this->kpis['combustiblePendiente'] === 1 ? __('carga pendiente') : __('cargas pendientes') }}
                    {{ __('de revisión') }}
                </flux:callout.heading>
                <flux:callout.text>
                    <a href="{{ route('combustible.index') }}" wire:navigate class="underline underline-offset-2">
                        {{ __('Ir a revisión de combustible') }}
                    </a>
                </flux:callout.text>
            </flux:callout>
        </div>
    @endif

    {{-- KPIs: Estado de la flota --}}
    <div class="mb-8">
        <h2 class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
            {{ __('Estado de la flota') }}
        </h2>
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <x-ui.stat-card
                :label="__('Total de vehículos')"
                :value="$this->kpis['totalFlota']"
                icon="truck"
                color="slate"
                :href="route('vehiculos.index')"
            />
            <x-ui.stat-card
                :label="__('Operativos')"
                :value="$this->kpis['totalOperativos']"
                icon="check-circle"
                color="brand"
                :hint="$this->kpis['totalFlota'] > 0 ? round($this->kpis['totalOperativos'] / $this->kpis['totalFlota'] * 100) . '% de la flota' : null"
            />
            <x-ui.stat-card
                :label="__('Parciales')"
                :value="$this->kpis['totalParciales']"
                icon="exclamation-triangle"
                color="warning"
                :hint="__('con problemas')"
            />
            <x-ui.stat-card
                :label="__('Fuera de servicio')"
                :value="$this->kpis['totalFueraServicio']"
                icon="x-circle"
                color="danger"
                :hint="__('inoperativos')"
            />
        </div>
    </div>

    {{-- KPIs: Este mes --}}
    <div class="mb-8">
        <h2 class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
            {{ __('Este mes') }} — {{ now()->translatedFormat('F Y') }}
        </h2>
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <x-ui.stat-card
                :label="__('Combustible cargado')"
                :value="number_format($this->kpis['galonesEsteMes'], 1) . ' gal'"
                icon="fire"
                color="info"
            />
            <x-ui.stat-card
                :label="__('Gasto en combustible')"
                :value="'S/ ' . number_format($this->kpis['montoEsteMes'], 0)"
                icon="currency-dollar"
                color="brand"
            />
            <x-ui.stat-card
                :label="__('Documentos en alerta')"
                :value="$this->kpis['docsAlerta']"
                icon="document-text"
                :color="$this->kpis['docsAlerta'] > 0 ? 'warning' : 'slate'"
                :hint="__('próximos 30 días')"
                :href="route('alertas.index')"
            />
            <x-ui.stat-card
                :label="__('Mantenimientos urgentes')"
                :value="$this->kpis['mantUrgentes']"
                icon="wrench-screwdriver"
                :color="$this->kpis['mantUrgentes'] > 0 ? 'warning' : 'slate'"
                :hint="__('≤30 días o ≤1,000 km')"
                :href="route('alertas.index')"
            />
        </div>
    </div>

    {{-- Grid inferior --}}
    <div class="grid gap-6 lg:grid-cols-2">

        {{-- Últimos vehículos registrados --}}
        <x-ui.section-card :title="__('Últimos vehículos registrados')" :padded="false">
            <x-slot:actions>
                <flux:button :href="route('vehiculos.index')" variant="ghost" size="sm" wire:navigate>
                    {{ __('Ver todos') }}
                </flux:button>
            </x-slot:actions>

            @if ($this->ultimosVehiculos->isNotEmpty())
                <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($this->ultimosVehiculos as $vehiculo)
                        <li>
                            <a
                                href="{{ route('vehiculos.show', $vehiculo) }}"
                                wire:navigate
                                class="flex items-center justify-between gap-3 px-5 py-3 transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/50"
                            >
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono-data text-sm font-semibold text-slate-900 dark:text-white">{{ $vehiculo->placa }}</span>
                                        <x-ui.badge-status
                                            :status="$vehiculo->estado"
                                            :label="$this->estadoLabel($vehiculo->estado)"
                                        />
                                    </div>
                                    <p class="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400">
                                        {{ $vehiculo->marca }} {{ $vehiculo->modelo }}
                                        @if ($vehiculo->sucursal && auth()->user()->puedeVerTodo())
                                            · {{ $vehiculo->sucursal->nombre }}
                                        @endif
                                    </p>
                                </div>
                                <span class="whitespace-nowrap text-xs text-slate-400 dark:text-slate-500">
                                    {{ $vehiculo->created_at->diffForHumans() }}
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @else
                <x-ui.empty-state icon="truck" :title="__('Sin vehículos registrados')" />
            @endif
        </x-ui.section-card>

        {{-- Flota por sucursal (admin) o Mis envíos (otros) --}}
        @if (auth()->user()->puedeVerTodo())
            <x-ui.section-card :title="__('Flota por sucursal')" :padded="false">
                @if ($this->flotaPorSucursal->isNotEmpty())
                    <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($this->flotaPorSucursal as $sucursal)
                            <li class="flex items-center gap-3 px-5 py-3">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-slate-900 dark:text-white">{{ $sucursal->nombre }}</p>
                                    <div class="mt-1 flex gap-2 text-xs">
                                        @if ($sucursal->operativos_count > 0)
                                            <span class="text-brand-600 dark:text-brand-400">{{ $sucursal->operativos_count }} op.</span>
                                        @endif
                                        @if ($sucursal->parciales_count > 0)
                                            <span class="text-amber-600 dark:text-amber-400">{{ $sucursal->parciales_count }} parc.</span>
                                        @endif
                                        @if ($sucursal->fuera_servicio_count > 0)
                                            <span class="text-red-600 dark:text-red-400">{{ $sucursal->fuera_servicio_count }} fuera</span>
                                        @endif
                                        @if ($sucursal->vehiculos_count === 0)
                                            <span class="text-slate-400">{{ __('Sin vehículos') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex shrink-0 items-baseline gap-1.5">
                                    <span class="font-mono-data text-2xl font-semibold text-slate-900 dark:text-white">
                                        {{ $sucursal->vehiculos_count }}
                                    </span>
                                    <span class="text-xs text-slate-400">veh.</span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <x-ui.empty-state icon="building-office" :title="__('Sin sucursales')" />
                @endif
            </x-ui.section-card>

        @else

            <x-ui.section-card :title="__('Mis envíos recientes de combustible')" :padded="false">
                <x-slot:actions>
                    <flux:button :href="route('combustible.index')" variant="ghost" size="sm" wire:navigate>
                        {{ __('Ver todos') }}
                    </flux:button>
                </x-slot:actions>

                @if ($this->misEnviosRecientes->isNotEmpty())
                    <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($this->misEnviosRecientes as $envio)
                            <li>
                                <a
                                    href="{{ route('combustible.show', $envio) }}"
                                    wire:navigate
                                    class="flex items-center justify-between gap-3 px-5 py-3 transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/50"
                                >
                                    <div class="min-w-0 flex-1">
                                        <span class="font-mono-data text-sm font-semibold text-slate-900 dark:text-white">{{ $envio->vehiculo?->placa ?? '—' }}</span>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $envio->created_at->format('d/m/Y H:i') }}</p>
                                    </div>
                                    <x-ui.badge-status :status="$envio->estado" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <x-ui.empty-state icon="fire" :title="__('Sin envíos recientes')" />
                @endif
            </x-ui.section-card>
        @endif
    </div>
</section>
