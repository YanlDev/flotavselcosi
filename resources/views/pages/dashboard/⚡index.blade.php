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
            $sucursalId = $user->esAdmin() ? null : $user->sucursal_id;

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

            // Combustible pendiente de revisión (solo admin)
            $combustiblePendiente = $user->esAdmin()
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
        if (! auth()->user()->esAdmin()) {
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
        if (! auth()->user()->esAdmin()) {
            return collect();
        }

        return RegistroCombustible::where('estado', 'pendiente')
            ->with(['vehiculo', 'sucursal', 'enviadoPor'])
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

<section class="w-full space-y-8">

    {{-- Encabezado --}}
    <div>
        <flux:heading size="xl">{{ __('Dashboard') }}</flux:heading>
        <flux:text>
            {{ __('Bienvenido,') }} {{ auth()->user()->name }}.
            @if (! auth()->user()->esAdmin() && auth()->user()->sucursal)
                {{ auth()->user()->sucursal->nombre }}
            @endif
        </flux:text>
    </div>

    {{-- KPIs: Flota --}}
    <div>
        <h2 class="mb-3 text-sm font-semibold text-zinc-500 uppercase tracking-wide">{{ __('Estado de la flota') }}</h2>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">

            {{-- Total --}}
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 p-4">
                <div class="flex items-center justify-between gap-2">
                    <flux:icon name="truck" class="size-5 text-zinc-400" />
                    <span class="text-xs text-zinc-500">{{ __('Total') }}</span>
                </div>
                <p class="mt-2 text-3xl font-bold text-zinc-800 dark:text-zinc-100">
                    {{ $this->kpis['totalFlota'] }}
                </p>
                <p class="mt-0.5 text-xs text-zinc-500">{{ __('vehículos') }}</p>
            </div>

            {{-- Operativos --}}
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-950 p-4">
                <div class="flex items-center justify-between gap-2">
                    <flux:icon name="check-circle" class="size-5 text-emerald-500" />
                    <span class="text-xs text-emerald-600 dark:text-emerald-400">{{ __('Operativos') }}</span>
                </div>
                <p class="mt-2 text-3xl font-bold text-emerald-700 dark:text-emerald-300">
                    {{ $this->kpis['totalOperativos'] }}
                </p>
                <p class="mt-0.5 text-xs text-emerald-600 dark:text-emerald-400">
                    @if ($this->kpis['totalFlota'] > 0)
                        {{ round($this->kpis['totalOperativos'] / $this->kpis['totalFlota'] * 100) }}%
                    @else
                        0%
                    @endif
                </p>
            </div>

            {{-- Parciales --}}
            <div class="rounded-xl border border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-950 p-4">
                <div class="flex items-center justify-between gap-2">
                    <flux:icon name="exclamation-triangle" class="size-5 text-amber-500" />
                    <span class="text-xs text-amber-600 dark:text-amber-400">{{ __('Parciales') }}</span>
                </div>
                <p class="mt-2 text-3xl font-bold text-amber-700 dark:text-amber-300">
                    {{ $this->kpis['totalParciales'] }}
                </p>
                <p class="mt-0.5 text-xs text-amber-600 dark:text-amber-400">{{ __('con problemas') }}</p>
            </div>

            {{-- Fuera de servicio --}}
            <div class="rounded-xl border border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-950 p-4">
                <div class="flex items-center justify-between gap-2">
                    <flux:icon name="x-circle" class="size-5 text-red-500" />
                    <span class="text-xs text-red-600 dark:text-red-400">{{ __('Fuera de servicio') }}</span>
                </div>
                <p class="mt-2 text-3xl font-bold text-red-700 dark:text-red-300">
                    {{ $this->kpis['totalFueraServicio'] }}
                </p>
                <p class="mt-0.5 text-xs text-red-600 dark:text-red-400">{{ __('inoperativos') }}</p>
            </div>

        </div>
    </div>

    {{-- KPIs: Operaciones del mes --}}
    <div>
        <h2 class="mb-3 text-sm font-semibold text-zinc-500 uppercase tracking-wide">
            {{ __('Este mes') }} — {{ now()->translatedFormat('F Y') }}
        </h2>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">

            {{-- Galones combustible --}}
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 p-4">
                <div class="flex items-center justify-between gap-2">
                    <flux:icon name="fire" class="size-5 text-orange-400" />
                    <span class="text-xs text-zinc-500">{{ __('Combustible') }}</span>
                </div>
                <p class="mt-2 text-2xl font-bold text-zinc-800 dark:text-zinc-100">
                    {{ number_format($this->kpis['galonesEsteMes'], 1) }}
                </p>
                <p class="mt-0.5 text-xs text-zinc-500">{{ __('galones cargados') }}</p>
            </div>

            {{-- Monto combustible --}}
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 p-4">
                <div class="flex items-center justify-between gap-2">
                    <flux:icon name="currency-dollar" class="size-5 text-green-400" />
                    <span class="text-xs text-zinc-500">{{ __('Gasto comb.') }}</span>
                </div>
                <p class="mt-2 text-2xl font-bold text-zinc-800 dark:text-zinc-100">
                    S/ {{ number_format($this->kpis['montoEsteMes'], 0) }}
                </p>
                <p class="mt-0.5 text-xs text-zinc-500">{{ __('monto total') }}</p>
            </div>

            {{-- Documentos por vencer --}}
            <a
                href="{{ route('alertas.index') }}"
                wire:navigate
                class="rounded-xl border p-4 transition-colors
                    {{ $this->kpis['docsAlerta'] > 0
                        ? 'border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-950'
                        : 'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900' }}"
            >
                <div class="flex items-center justify-between gap-2">
                    <flux:icon name="document-text" class="size-5 {{ $this->kpis['docsAlerta'] > 0 ? 'text-amber-500' : 'text-zinc-400' }}" />
                    <span class="text-xs {{ $this->kpis['docsAlerta'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-500' }}">
                        {{ __('Docs. alerta') }}
                    </span>
                </div>
                <p class="mt-2 text-2xl font-bold {{ $this->kpis['docsAlerta'] > 0 ? 'text-amber-700 dark:text-amber-300' : 'text-zinc-800 dark:text-zinc-100' }}">
                    {{ $this->kpis['docsAlerta'] }}
                </p>
                <p class="mt-0.5 text-xs {{ $this->kpis['docsAlerta'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-500' }}">
                    {{ __('próximos 30 días') }}
                </p>
            </a>

            {{-- Mantenimientos urgentes --}}
            <a
                href="{{ route('alertas.index') }}"
                wire:navigate
                class="rounded-xl border p-4 transition-colors
                    {{ $this->kpis['mantUrgentes'] > 0
                        ? 'border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-950'
                        : 'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900' }}"
            >
                <div class="flex items-center justify-between gap-2">
                    <flux:icon name="wrench-screwdriver" class="size-5 {{ $this->kpis['mantUrgentes'] > 0 ? 'text-amber-500' : 'text-zinc-400' }}" />
                    <span class="text-xs {{ $this->kpis['mantUrgentes'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-500' }}">
                        {{ __('Mant. urgentes') }}
                    </span>
                </div>
                <p class="mt-2 text-2xl font-bold {{ $this->kpis['mantUrgentes'] > 0 ? 'text-amber-700 dark:text-amber-300' : 'text-zinc-800 dark:text-zinc-100' }}">
                    {{ $this->kpis['mantUrgentes'] }}
                </p>
                <p class="mt-0.5 text-xs {{ $this->kpis['mantUrgentes'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-500' }}">
                    {{ __('≤30 días o ≤1,000 km') }}
                </p>
            </a>

        </div>
    </div>

    {{-- Combustible pendiente (solo admin) --}}
    @if (auth()->user()->esAdmin() && $this->kpis['combustiblePendiente'] > 0)
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
    @endif

    {{-- Grid inferior: Últimos vehículos + Flota por sucursal --}}
    <div class="grid gap-6 lg:grid-cols-2">

        {{-- Últimos 5 vehículos registrados --}}
        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between border-b border-zinc-100 dark:border-zinc-800 px-4 py-3">
                <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                    {{ __('Últimos vehículos registrados') }}
                </h3>
                <flux:button
                    :href="route('vehiculos.index')"
                    variant="ghost" size="sm"
                    wire:navigate
                >
                    {{ __('Ver todos') }}
                </flux:button>
            </div>

            @if ($this->ultimosVehiculos->isNotEmpty())
                <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($this->ultimosVehiculos as $vehiculo)
                        <li>
                            <a
                                href="{{ route('vehiculos.show', $vehiculo) }}"
                                wire:navigate
                                class="flex items-center justify-between gap-3 px-4 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors"
                            >
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono font-semibold text-sm">{{ $vehiculo->placa }}</span>
                                        <flux:badge
                                            :color="$this->estadoBadgeColor($vehiculo->estado)"
                                            size="sm"
                                        >
                                            {{ $this->estadoLabel($vehiculo->estado) }}
                                        </flux:badge>
                                    </div>
                                    <p class="text-xs text-zinc-500 truncate">
                                        {{ $vehiculo->marca }} {{ $vehiculo->modelo }}
                                        @if ($vehiculo->sucursal && auth()->user()->esAdmin())
                                            · {{ $vehiculo->sucursal->nombre }}
                                        @endif
                                    </p>
                                </div>
                                <span class="text-xs text-zinc-400 whitespace-nowrap">
                                    {{ $vehiculo->created_at->diffForHumans() }}
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="px-4 py-10 text-center">
                    <flux:icon name="truck" class="mx-auto mb-2 size-8 text-zinc-300 dark:text-zinc-600" />
                    <flux:text class="text-sm">{{ __('Sin vehículos registrados.') }}</flux:text>
                </div>
            @endif
        </div>

        {{-- Flota por sucursal (solo admin) | Combustible pendiente reciente --}}
        @if (auth()->user()->esAdmin())
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between border-b border-zinc-100 dark:border-zinc-800 px-4 py-3">
                    <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                        {{ __('Flota por sucursal') }}
                    </h3>
                </div>

                @if ($this->flotaPorSucursal->isNotEmpty())
                    <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($this->flotaPorSucursal as $sucursal)
                            <li class="flex items-center gap-3 px-4 py-3">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium truncate">{{ $sucursal->nombre }}</p>
                                    <div class="mt-1 flex gap-2">
                                        @if ($sucursal->operativos_count > 0)
                                            <span class="text-xs text-emerald-600 dark:text-emerald-400">
                                                {{ $sucursal->operativos_count }} op.
                                            </span>
                                        @endif
                                        @if ($sucursal->parciales_count > 0)
                                            <span class="text-xs text-amber-600 dark:text-amber-400">
                                                {{ $sucursal->parciales_count }} parc.
                                            </span>
                                        @endif
                                        @if ($sucursal->fuera_servicio_count > 0)
                                            <span class="text-xs text-red-600 dark:text-red-400">
                                                {{ $sucursal->fuera_servicio_count }} fuera
                                            </span>
                                        @endif
                                        @if ($sucursal->vehiculos_count === 0)
                                            <span class="text-xs text-zinc-400">{{ __('Sin vehículos') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <span class="text-2xl font-bold text-zinc-700 dark:text-zinc-300">
                                        {{ $sucursal->vehiculos_count }}
                                    </span>
                                    <span class="text-xs text-zinc-400">veh.</span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="px-4 py-10 text-center">
                        <flux:text class="text-sm">{{ __('Sin datos.') }}</flux:text>
                    </div>
                @endif
            </div>

        @else
            {{-- jefe_resguardo / visor: cargas pendientes recientes (sus envíos) --}}
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between border-b border-zinc-100 dark:border-zinc-800 px-4 py-3">
                    <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                        {{ __('Mis envíos recientes de combustible') }}
                    </h3>
                    <flux:button
                        :href="route('combustible.index')"
                        variant="ghost" size="sm"
                        wire:navigate
                    >
                        {{ __('Ver todos') }}
                    </flux:button>
                </div>

                @php
                    $misEnvios = \App\Models\RegistroCombustible::where('enviado_por', auth()->id())
                        ->with('vehiculo')
                        ->latest()
                        ->limit(5)
                        ->get();
                @endphp

                @if ($misEnvios->isNotEmpty())
                    <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($misEnvios as $envio)
                            <li>
                                <a
                                    href="{{ route('combustible.show', $envio) }}"
                                    wire:navigate
                                    class="flex items-center justify-between gap-3 px-4 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors"
                                >
                                    <div class="min-w-0 flex-1">
                                        <span class="font-mono font-semibold text-sm">{{ $envio->vehiculo?->placa ?? '—' }}</span>
                                        <p class="text-xs text-zinc-500">{{ $envio->created_at->format('d/m/Y H:i') }}</p>
                                    </div>
                                    <flux:badge
                                        :color="match($envio->estado) { 'aprobado' => 'green', 'rechazado' => 'red', default => 'amber' }"
                                        size="sm"
                                    >
                                        {{ ucfirst($envio->estado) }}
                                    </flux:badge>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="px-4 py-10 text-center">
                        <flux:icon name="fire" class="mx-auto mb-2 size-8 text-zinc-300 dark:text-zinc-600" />
                        <flux:text class="text-sm">{{ __('Sin envíos recientes.') }}</flux:text>
                    </div>
                @endif
            </div>
        @endif

    </div>

</section>
