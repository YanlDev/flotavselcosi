<?php

use App\Services\AlertasService;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Alertas')] class extends Component {

    #[Url]
    public string $tab = 'documentos';

    #[Computed]
    public function documentos(): \Illuminate\Database\Eloquent\Collection
    {
        return app(AlertasService::class)->documentosAlerta(auth()->user());
    }

    #[Computed]
    public function mantenimientos(): \Illuminate\Database\Eloquent\Collection
    {
        return app(AlertasService::class)->mantenimientosAlerta(auth()->user());
    }

    #[Computed]
    public function licencias(): \Illuminate\Database\Eloquent\Collection
    {
        return app(AlertasService::class)->licenciasAlerta(auth()->user());
    }

    public function estadoDocumento(?CarbonInterface $vencimiento): string
    {
        if (! $vencimiento) {
            return 'zinc';
        }

        return $vencimiento->isPast() ? 'red' : 'amber';
    }

    public function estadoDocumentoLabel(?CarbonInterface $vencimiento): string
    {
        if (! $vencimiento) {
            return '';
        }

        return $vencimiento->isPast() ? 'Vencido' : 'Por vencer';
    }

    public function diasRestantes(?CarbonInterface $vencimiento): ?int
    {
        if (! $vencimiento) {
            return null;
        }

        return (int) Carbon::now()->startOfDay()->diffInDays($vencimiento, false);
    }

    public function tipoDocumentoLabel(string $tipo): string
    {
        return match ($tipo) {
            'soat'              => 'SOAT',
            'revision_tecnica'  => 'Revisión técnica',
            'tarjeta_propiedad' => 'Tarjeta de propiedad',
            default             => 'Otro',
        };
    }

    public function categoriaMantenimientoLabel(string $categoria): string
    {
        return match ($categoria) {
            'aceite_filtros'       => 'Aceite y filtros',
            'llantas'              => 'Llantas',
            'frenos'               => 'Frenos',
            'liquidos'             => 'Líquidos',
            'bateria'              => 'Batería',
            'alineacion_balanceo'  => 'Alineación/Balanceo',
            'suspension'           => 'Suspensión',
            'transmision'          => 'Transmisión',
            'electricidad'         => 'Electricidad',
            'revision_general'     => 'Revisión general',
            default                => 'Otro',
        };
    }
}; ?>

<section class="w-full space-y-6">

    {{-- Encabezado --}}
    <div>
        <flux:heading size="xl">{{ __('Alertas') }}</flux:heading>
        <flux:text>
            {{ __('Documentos, mantenimientos y licencias que requieren atención.') }}
            @if (! auth()->user()->esAdmin() && auth()->user()->sucursal)
                — {{ auth()->user()->sucursal->nombre }}
            @endif
        </flux:text>
    </div>

    {{-- Resumen badges --}}
    <div class="grid grid-cols-3 gap-3">

        <button
            wire:click="$set('tab', 'documentos')"
            @class([
                'rounded-xl border p-4 text-left transition-colors',
                'border-amber-300 bg-amber-50 dark:border-amber-700 dark:bg-amber-950' => $tab === 'documentos',
                'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 hover:bg-zinc-50 dark:hover:bg-zinc-800/50' => $tab !== 'documentos',
            ])
        >
            <div class="flex items-center justify-between gap-2">
                <flux:icon name="document-text" @class([
                    'size-5',
                    'text-amber-500' => $this->documentos->isNotEmpty(),
                    'text-zinc-400' => $this->documentos->isEmpty(),
                ]) />
                @if ($this->documentos->isNotEmpty())
                    <flux:badge color="amber" size="sm">{{ $this->documentos->count() }}</flux:badge>
                @endif
            </div>
            <p class="mt-2 text-2xl font-bold {{ $this->documentos->isNotEmpty() ? 'text-amber-700 dark:text-amber-300' : 'text-zinc-700 dark:text-zinc-300' }}">
                {{ $this->documentos->count() }}
            </p>
            <p class="text-xs {{ $this->documentos->isNotEmpty() ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-500' }}">
                {{ __('Documentos') }}
            </p>
        </button>

        <button
            wire:click="$set('tab', 'mantenimientos')"
            @class([
                'rounded-xl border p-4 text-left transition-colors',
                'border-amber-300 bg-amber-50 dark:border-amber-700 dark:bg-amber-950' => $tab === 'mantenimientos',
                'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 hover:bg-zinc-50 dark:hover:bg-zinc-800/50' => $tab !== 'mantenimientos',
            ])
        >
            <div class="flex items-center justify-between gap-2">
                <flux:icon name="wrench-screwdriver" @class([
                    'size-5',
                    'text-amber-500' => $this->mantenimientos->isNotEmpty(),
                    'text-zinc-400' => $this->mantenimientos->isEmpty(),
                ]) />
                @if ($this->mantenimientos->isNotEmpty())
                    <flux:badge color="amber" size="sm">{{ $this->mantenimientos->count() }}</flux:badge>
                @endif
            </div>
            <p class="mt-2 text-2xl font-bold {{ $this->mantenimientos->isNotEmpty() ? 'text-amber-700 dark:text-amber-300' : 'text-zinc-700 dark:text-zinc-300' }}">
                {{ $this->mantenimientos->count() }}
            </p>
            <p class="text-xs {{ $this->mantenimientos->isNotEmpty() ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-500' }}">
                {{ __('Mantenimientos') }}
            </p>
        </button>

        <button
            wire:click="$set('tab', 'licencias')"
            @class([
                'rounded-xl border p-4 text-left transition-colors',
                'border-amber-300 bg-amber-50 dark:border-amber-700 dark:bg-amber-950' => $tab === 'licencias',
                'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 hover:bg-zinc-50 dark:hover:bg-zinc-800/50' => $tab !== 'licencias',
            ])
        >
            <div class="flex items-center justify-between gap-2">
                <flux:icon name="identification" @class([
                    'size-5',
                    'text-amber-500' => $this->licencias->isNotEmpty(),
                    'text-zinc-400' => $this->licencias->isEmpty(),
                ]) />
                @if ($this->licencias->isNotEmpty())
                    <flux:badge color="amber" size="sm">{{ $this->licencias->count() }}</flux:badge>
                @endif
            </div>
            <p class="mt-2 text-2xl font-bold {{ $this->licencias->isNotEmpty() ? 'text-amber-700 dark:text-amber-300' : 'text-zinc-700 dark:text-zinc-300' }}">
                {{ $this->licencias->count() }}
            </p>
            <p class="text-xs {{ $this->licencias->isNotEmpty() ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-500' }}">
                {{ __('Licencias') }}
            </p>
        </button>

    </div>

    {{-- Contenido por tab --}}

    {{-- Tab: Documentos --}}
    @if ($tab === 'documentos')
        <div>
            <h2 class="mb-3 text-sm font-semibold text-zinc-500 uppercase tracking-wide">
                {{ __('Documentos vehiculares') }}
            </h2>

            @if ($this->documentos->isNotEmpty())
                <div class="overflow-x-auto hidden sm:block">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>{{ __('Vehículo') }}</flux:table.column>
                            <flux:table.column>{{ __('Tipo') }}</flux:table.column>
                            <flux:table.column>{{ __('Nombre') }}</flux:table.column>
                            @if (auth()->user()->esAdmin())
                                <flux:table.column>{{ __('Sucursal') }}</flux:table.column>
                            @endif
                            <flux:table.column>{{ __('Vencimiento') }}</flux:table.column>
                            <flux:table.column>{{ __('Estado') }}</flux:table.column>
                            <flux:table.column></flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($this->documentos as $doc)
                                @php
                                    $dias  = $this->diasRestantes($doc->vencimiento);
                                    $color = $this->estadoDocumento($doc->vencimiento);
                                @endphp
                                <flux:table.row :key="$doc->id">
                                    <flux:table.cell class="font-mono font-semibold text-sm">
                                        {{ $doc->vehiculo?->placa ?? '—' }}
                                        <span class="block font-normal font-sans text-xs text-zinc-500">
                                            {{ $doc->vehiculo?->marca }} {{ $doc->vehiculo?->modelo }}
                                        </span>
                                    </flux:table.cell>
                                    <flux:table.cell class="text-sm">
                                        {{ $this->tipoDocumentoLabel($doc->tipo) }}
                                    </flux:table.cell>
                                    <flux:table.cell class="text-sm">
                                        {{ $doc->nombre }}
                                    </flux:table.cell>
                                    @if (auth()->user()->esAdmin())
                                        <flux:table.cell class="text-sm">
                                            {{ $doc->vehiculo?->sucursal?->nombre ?? '—' }}
                                        </flux:table.cell>
                                    @endif
                                    <flux:table.cell class="text-sm font-medium">
                                        {{ $doc->vencimiento?->format('d/m/Y') }}
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <flux:badge :color="$color" size="sm">
                                            @if ($dias === null)
                                                —
                                            @elseif ($dias < 0)
                                                {{ __('Vencido hace') }} {{ abs($dias) }}d
                                            @elseif ($dias === 0)
                                                {{ __('Vence hoy') }}
                                            @else
                                                {{ __('Vence en') }} {{ $dias }}d
                                            @endif
                                        </flux:badge>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        @if ($doc->vehiculo)
                                            <flux:button
                                                :href="route('vehiculos.show', $doc->vehiculo)"
                                                size="sm" variant="ghost" icon="arrow-right"
                                                inset="top bottom"
                                                wire:navigate
                                            />
                                        @endif
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>

                {{-- Mobile --}}
                <div class="sm:hidden space-y-3">
                    @foreach ($this->documentos as $doc)
                        @php
                            $dias  = $this->diasRestantes($doc->vencimiento);
                            $color = $this->estadoDocumento($doc->vencimiento);
                        @endphp
                        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 p-4">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-mono font-semibold text-sm">{{ $doc->vehiculo?->placa ?? '—' }}</span>
                                        <flux:badge :color="$color" size="sm">
                                            @if ($dias === null)
                                                —
                                            @elseif ($dias < 0)
                                                Vencido hace {{ abs($dias) }}d
                                            @elseif ($dias === 0)
                                                Vence hoy
                                            @else
                                                Vence en {{ $dias }}d
                                            @endif
                                        </flux:badge>
                                    </div>
                                    <p class="mt-1 text-xs text-zinc-500">
                                        {{ $this->tipoDocumentoLabel($doc->tipo) }} — {{ $doc->nombre }}
                                    </p>
                                    <p class="mt-0.5 text-xs text-zinc-400">
                                        {{ $doc->vencimiento?->format('d/m/Y') }}
                                        @if (auth()->user()->esAdmin() && $doc->vehiculo?->sucursal)
                                            · {{ $doc->vehiculo->sucursal->nombre }}
                                        @endif
                                    </p>
                                </div>
                                @if ($doc->vehiculo)
                                    <flux:button
                                        :href="route('vehiculos.show', $doc->vehiculo)"
                                        size="sm" variant="ghost" icon="arrow-right"
                                        wire:navigate
                                    />
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-12 text-center rounded-xl border border-dashed border-zinc-200 dark:border-zinc-700">
                    <flux:icon name="document-check" class="mx-auto mb-3 size-10 text-emerald-400" />
                    <flux:text>{{ __('Sin documentos por vencer en los próximos 30 días.') }}</flux:text>
                </div>
            @endif
        </div>
    @endif

    {{-- Tab: Mantenimientos --}}
    @if ($tab === 'mantenimientos')
        <div>
            <h2 class="mb-3 text-sm font-semibold text-zinc-500 uppercase tracking-wide">
                {{ __('Mantenimientos urgentes') }}
            </h2>
            <flux:text class="mb-4 text-xs">
                {{ __('Próxima fecha ≤ 30 días o km restantes ≤ 1,000.') }}
            </flux:text>

            @if ($this->mantenimientos->isNotEmpty())
                <div class="overflow-x-auto hidden sm:block">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>{{ __('Vehículo') }}</flux:table.column>
                            <flux:table.column>{{ __('Categoría') }}</flux:table.column>
                            @if (auth()->user()->esAdmin())
                                <flux:table.column>{{ __('Sucursal') }}</flux:table.column>
                            @endif
                            <flux:table.column>{{ __('Próxima fecha') }}</flux:table.column>
                            <flux:table.column>{{ __('Próx. km') }}</flux:table.column>
                            <flux:table.column>{{ __('Km actuales') }}</flux:table.column>
                            <flux:table.column></flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($this->mantenimientos as $mant)
                                @php
                                    $diasMant   = $mant->proxima_fecha ? $this->diasRestantes($mant->proxima_fecha) : null;
                                    $colorFecha = $diasMant !== null
                                        ? ($diasMant < 0 ? 'red' : 'amber')
                                        : null;
                                    $kmActuales  = $mant->vehiculo?->km_actuales;
                                    $kmRestantes = ($mant->proximo_km && $kmActuales)
                                        ? $mant->proximo_km - $kmActuales
                                        : null;
                                    $colorKm = $kmRestantes !== null
                                        ? ($kmRestantes <= 0 ? 'red' : 'amber')
                                        : null;
                                @endphp
                                <flux:table.row :key="$mant->id">
                                    <flux:table.cell class="font-mono font-semibold text-sm">
                                        {{ $mant->vehiculo?->placa ?? '—' }}
                                        <span class="block font-normal font-sans text-xs text-zinc-500">
                                            {{ $mant->vehiculo?->marca }} {{ $mant->vehiculo?->modelo }}
                                        </span>
                                    </flux:table.cell>
                                    <flux:table.cell class="text-sm">
                                        {{ $this->categoriaMantenimientoLabel($mant->categoria) }}
                                        <span class="block text-xs text-zinc-400">{{ ucfirst($mant->tipo) }}</span>
                                    </flux:table.cell>
                                    @if (auth()->user()->esAdmin())
                                        <flux:table.cell class="text-sm">
                                            {{ $mant->vehiculo?->sucursal?->nombre ?? '—' }}
                                        </flux:table.cell>
                                    @endif
                                    <flux:table.cell>
                                        @if ($mant->proxima_fecha && $colorFecha)
                                            <flux:badge :color="$colorFecha" size="sm">
                                                {{ $mant->proxima_fecha->format('d/m/Y') }}
                                                @if ($diasMant < 0)
                                                    ({{ abs($diasMant) }}d vencido)
                                                @elseif ($diasMant === 0)
                                                    (hoy)
                                                @else
                                                    (en {{ $diasMant }}d)
                                                @endif
                                            </flux:badge>
                                        @elseif ($mant->proxima_fecha)
                                            <span class="text-sm text-zinc-500">{{ $mant->proxima_fecha->format('d/m/Y') }}</span>
                                        @else
                                            <span class="text-zinc-400 text-sm">—</span>
                                        @endif
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        @if ($mant->proximo_km)
                                            <span class="text-sm">{{ number_format($mant->proximo_km) }} km</span>
                                        @else
                                            <span class="text-zinc-400 text-sm">—</span>
                                        @endif
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        @if ($kmRestantes !== null)
                                            <flux:badge :color="$colorKm" size="sm">
                                                @if ($kmRestantes <= 0)
                                                    {{ number_format(abs($kmRestantes)) }} km excedido
                                                @else
                                                    {{ number_format($kmRestantes) }} km rest.
                                                @endif
                                            </flux:badge>
                                        @elseif ($kmActuales)
                                            <span class="text-sm text-zinc-500">{{ number_format($kmActuales) }} km</span>
                                        @else
                                            <span class="text-zinc-400 text-sm">—</span>
                                        @endif
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        @if ($mant->vehiculo)
                                            <flux:button
                                                :href="route('vehiculos.show', $mant->vehiculo)"
                                                size="sm" variant="ghost" icon="arrow-right"
                                                inset="top bottom"
                                                wire:navigate
                                            />
                                        @endif
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>

                {{-- Mobile --}}
                <div class="sm:hidden space-y-3">
                    @foreach ($this->mantenimientos as $mant)
                        @php
                            $diasMant   = $mant->proxima_fecha ? $this->diasRestantes($mant->proxima_fecha) : null;
                            $colorFecha = $diasMant !== null ? ($diasMant < 0 ? 'red' : 'amber') : null;
                            $kmActuales  = $mant->vehiculo?->km_actuales;
                            $kmRestantes = ($mant->proximo_km && $kmActuales) ? $mant->proximo_km - $kmActuales : null;
                            $colorKm = $kmRestantes !== null ? ($kmRestantes <= 0 ? 'red' : 'amber') : null;
                        @endphp
                        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 p-4">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-mono font-semibold text-sm">{{ $mant->vehiculo?->placa ?? '—' }}</span>
                                        <span class="text-xs text-zinc-500">{{ $this->categoriaMantenimientoLabel($mant->categoria) }}</span>
                                    </div>
                                    <div class="mt-1 flex flex-wrap gap-1.5">
                                        @if ($mant->proxima_fecha && $colorFecha)
                                            <flux:badge :color="$colorFecha" size="sm">
                                                {{ $mant->proxima_fecha->format('d/m/Y') }}
                                                @if ($diasMant < 0)({{ abs($diasMant) }}d venc.)@elseif($diasMant === 0)(hoy)@else(en {{ $diasMant }}d)@endif
                                            </flux:badge>
                                        @endif
                                        @if ($kmRestantes !== null)
                                            <flux:badge :color="$colorKm" size="sm">
                                                @if ($kmRestantes <= 0)
                                                    {{ number_format(abs($kmRestantes)) }} km excedido
                                                @else
                                                    {{ number_format($kmRestantes) }} km rest.
                                                @endif
                                            </flux:badge>
                                        @endif
                                    </div>
                                    @if (auth()->user()->esAdmin() && $mant->vehiculo?->sucursal)
                                        <p class="mt-1 text-xs text-zinc-400">{{ $mant->vehiculo->sucursal->nombre }}</p>
                                    @endif
                                </div>
                                @if ($mant->vehiculo)
                                    <flux:button
                                        :href="route('vehiculos.show', $mant->vehiculo)"
                                        size="sm" variant="ghost" icon="arrow-right"
                                        wire:navigate
                                    />
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-12 text-center rounded-xl border border-dashed border-zinc-200 dark:border-zinc-700">
                    <flux:icon name="check-circle" class="mx-auto mb-3 size-10 text-emerald-400" />
                    <flux:text>{{ __('Sin mantenimientos urgentes en los próximos 30 días o ≤ 1,000 km.') }}</flux:text>
                </div>
            @endif
        </div>
    @endif

    {{-- Tab: Licencias --}}
    @if ($tab === 'licencias')
        <div>
            <h2 class="mb-3 text-sm font-semibold text-zinc-500 uppercase tracking-wide">
                {{ __('Licencias de conductores') }}
            </h2>

            @if ($this->licencias->isNotEmpty())
                <div class="overflow-x-auto hidden sm:block">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>{{ __('Conductor') }}</flux:table.column>
                            @if (auth()->user()->esAdmin())
                                <flux:table.column>{{ __('Sucursal') }}</flux:table.column>
                            @endif
                            <flux:table.column>{{ __('Categoría') }}</flux:table.column>
                            <flux:table.column>{{ __('Vencimiento') }}</flux:table.column>
                            <flux:table.column>{{ __('Estado') }}</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($this->licencias as $conductor)
                                @php
                                    $dias  = $this->diasRestantes($conductor->licencia_vencimiento);
                                    $color = $conductor->licencia_vencimiento->isPast() ? 'red' : 'amber';
                                @endphp
                                <flux:table.row :key="$conductor->id">
                                    <flux:table.cell>
                                        <p class="font-medium text-sm">{{ $conductor->nombre_completo }}</p>
                                        @if ($conductor->telefono)
                                            <p class="text-xs text-zinc-500">{{ $conductor->telefono }}</p>
                                        @endif
                                    </flux:table.cell>
                                    @if (auth()->user()->esAdmin())
                                        <flux:table.cell class="text-sm">
                                            {{ $conductor->sucursal?->nombre ?? '—' }}
                                        </flux:table.cell>
                                    @endif
                                    <flux:table.cell class="text-sm">
                                        {{ $conductor->licencia_categoria ?? '—' }}
                                    </flux:table.cell>
                                    <flux:table.cell class="text-sm font-medium">
                                        {{ $conductor->licencia_vencimiento->format('d/m/Y') }}
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <flux:badge :color="$color" size="sm">
                                            @if ($dias < 0)
                                                {{ __('Vencida hace') }} {{ abs($dias) }}d
                                            @elseif ($dias === 0)
                                                {{ __('Vence hoy') }}
                                            @else
                                                {{ __('Vence en') }} {{ $dias }}d
                                            @endif
                                        </flux:badge>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>

                {{-- Mobile --}}
                <div class="sm:hidden space-y-3">
                    @foreach ($this->licencias as $conductor)
                        @php
                            $dias  = $this->diasRestantes($conductor->licencia_vencimiento);
                            $color = $conductor->licencia_vencimiento->isPast() ? 'red' : 'amber';
                        @endphp
                        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 p-4">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-semibold text-sm">{{ $conductor->nombre_completo }}</span>
                                        <flux:badge :color="$color" size="sm">
                                            @if ($dias < 0)
                                                Vencida hace {{ abs($dias) }}d
                                            @elseif ($dias === 0)
                                                Vence hoy
                                            @else
                                                Vence en {{ $dias }}d
                                            @endif
                                        </flux:badge>
                                    </div>
                                    <p class="mt-0.5 text-xs text-zinc-500">
                                        DNI: {{ $conductor->dni }}
                                        @if ($conductor->licencia_categoria) · Licencia: {{ $conductor->licencia_categoria }} @endif
                                    </p>
                                    <p class="mt-0.5 text-xs text-zinc-400">
                                        {{ $conductor->licencia_vencimiento->format('d/m/Y') }}
                                        @if (auth()->user()->esAdmin() && $conductor->sucursal)
                                            · {{ $conductor->sucursal->nombre }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-12 text-center rounded-xl border border-dashed border-zinc-200 dark:border-zinc-700">
                    <flux:icon name="check-circle" class="mx-auto mb-3 size-10 text-emerald-400" />
                    <flux:text>{{ __('Sin licencias por vencer en los próximos 30 días.') }}</flux:text>
                </div>
            @endif
        </div>
    @endif

</section>
