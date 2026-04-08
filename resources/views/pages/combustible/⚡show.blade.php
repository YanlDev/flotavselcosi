<?php

use App\Models\RegistroCombustible;
use App\Services\WasabiService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Detalle de carga')] class extends Component {

    public RegistroCombustible $registroCombustible;

    // Campos de revisión (admin)
    public string $fechaCarga = '';
    public string $kmAlCargar = '';
    public string $galones = '';
    public string $precioGalon = '';
    public string $montoTotal = '';
    public string $tipoCombustible = '';
    public string $proveedor = '';
    public string $numeroVoucher = '';
    public string $observacionesRevision = '';

    // Preview fotos
    public ?string $previewUrl = null;
    public string $previewMime = '';
    public string $previewNombre = '';

    // Confirm rechazar
    public bool $showRechazarModal = false;
    public string $motivoRechazo = '';

    public function mount(RegistroCombustible $registroCombustible): void
    {
        // Solo admin, jefe_resguardo o el usuario que lo envió puede ver
        $user = auth()->user();
        abort_unless(
            $user->esAdmin()
                || $user->esJefeResguardo()
                || $registroCombustible->enviado_por === $user->id,
            403
        );

        // Jefe_resguardo solo su sucursal
        if ($user->esJefeResguardo()) {
            abort_unless($registroCombustible->sucursal_id === $user->sucursal_id, 403);
        }

        $this->registroCombustible = $registroCombustible;

        // Pre-llenar si ya tiene datos de revisión
        if ($registroCombustible->fecha_carga) {
            $this->fechaCarga           = $registroCombustible->fecha_carga->format('Y-m-d');
            $this->kmAlCargar           = (string) ($registroCombustible->km_al_cargar ?? '');
            $this->galones              = (string) ($registroCombustible->galones ?? '');
            $this->precioGalon          = (string) ($registroCombustible->precio_galon ?? '');
            $this->montoTotal           = (string) ($registroCombustible->monto_total ?? '');
            $this->tipoCombustible      = $registroCombustible->tipo_combustible ?? '';
            $this->proveedor            = $registroCombustible->proveedor ?? '';
            $this->numeroVoucher        = $registroCombustible->numero_voucher ?? '';
            $this->observacionesRevision = $registroCombustible->observaciones_revision ?? '';
        }
    }

    public function updatedGalones(): void
    {
        $this->calcularMonto();
    }

    public function updatedPrecioGalon(): void
    {
        $this->calcularMonto();
    }

    private function calcularMonto(): void
    {
        $g = (float) str_replace(',', '.', $this->galones);
        $p = (float) str_replace(',', '.', $this->precioGalon);

        if ($g > 0 && $p > 0) {
            $this->montoTotal = number_format($g * $p, 2, '.', '');
        }
    }

    public function verFoto(string $campo, WasabiService $wasabi): void
    {
        $key = match ($campo) {
            'factura'  => $this->registroCombustible->foto_factura_key,
            'odometro' => $this->registroCombustible->foto_odometro_key,
            default    => null,
        };

        abort_unless($key, 404);

        $this->previewUrl    = $wasabi->temporaryUrl($key);
        $this->previewMime   = str_ends_with($key, '.pdf') ? 'application/pdf' : 'image/jpeg';
        $this->previewNombre = $campo === 'factura' ? 'Factura' : 'Odómetro';

        $this->dispatch('abrir-preview-combustible',
            url: $this->previewUrl,
            mime: $this->previewMime,
            nombre: $this->previewNombre,
        );
    }

    public function aprobar(): void
    {
        abort_unless(auth()->user()->esAdmin(), 403);
        abort_unless($this->registroCombustible->estado === 'pendiente', 422);

        $this->validate([
            'fechaCarga'      => ['required', 'date'],
            'kmAlCargar'      => ['required', 'integer', 'min:0'],
            'galones'         => ['required', 'numeric', 'min:0.001'],
            'precioGalon'     => ['required', 'numeric', 'min:0.001'],
            'montoTotal'      => ['required', 'numeric', 'min:0.01'],
            'tipoCombustible' => ['required', 'in:gasolina,diesel,glp,gnv,electrico,hibrido'],
            'proveedor'       => ['nullable', 'string', 'max:200'],
            'numeroVoucher'   => ['nullable', 'string', 'max:100'],
            'observacionesRevision' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->registroCombustible->update([
            'estado'                  => 'aprobado',
            'revisado_por'            => auth()->id(),
            'revisado_en'             => now(),
            'fecha_carga'             => $this->fechaCarga,
            'km_al_cargar'            => (int) $this->kmAlCargar,
            'galones'                 => $this->galones,
            'precio_galon'            => $this->precioGalon,
            'monto_total'             => $this->montoTotal,
            'tipo_combustible'        => $this->tipoCombustible,
            'proveedor'               => $this->proveedor ?: null,
            'numero_voucher'          => $this->numeroVoucher ?: null,
            'observaciones_revision'  => $this->observacionesRevision ?: null,
        ]);

        $this->registroCombustible->refresh();
    }

    public function abrirRechazar(): void
    {
        abort_unless(auth()->user()->esAdmin(), 403);
        abort_unless($this->registroCombustible->estado === 'pendiente', 422);
        $this->motivoRechazo = '';
        $this->showRechazarModal = true;
    }

    public function rechazar(): void
    {
        abort_unless(auth()->user()->esAdmin(), 403);
        abort_unless($this->registroCombustible->estado === 'pendiente', 422);

        $this->validate([
            'motivoRechazo' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->registroCombustible->update([
            'estado'                 => 'rechazado',
            'revisado_por'           => auth()->id(),
            'revisado_en'            => now(),
            'observaciones_revision' => $this->motivoRechazo ?: null,
        ]);

        $this->registroCombustible->refresh();
        $this->showRechazarModal = false;
    }

    public function estadoBadgeColor(string $estado): string
    {
        return match ($estado) {
            'pendiente' => 'amber',
            'aprobado'  => 'green',
            'rechazado' => 'red',
            default     => 'zinc',
        };
    }

    public function tipoCombustibleLabel(string $tipo): string
    {
        return match ($tipo) {
            'gasolina'  => 'Gasolina',
            'diesel'    => 'Diésel',
            'glp'       => 'GLP',
            'gnv'       => 'GNV',
            'electrico' => 'Eléctrico',
            'hibrido'   => 'Híbrido',
            default     => $tipo,
        };
    }
}; ?>

<div
    class="w-full space-y-6"
    x-data="{ show: false, url: '', mime: '', nombre: '' }"
    x-on:abrir-preview-combustible.window="show = true; url = $event.detail.url; mime = $event.detail.mime; nombre = $event.detail.nombre"
>

    {{-- Breadcrumb / Encabezado --}}
    <div class="flex items-center gap-3">
        <flux:button
            :href="route('combustible.index')"
            variant="subtle"
            icon="arrow-left"
            size="sm"
            wire:navigate
        />
        <div>
            <flux:heading size="xl">{{ __('Carga de combustible') }}</flux:heading>
            <flux:text class="text-sm">
                {{ $registroCombustible->vehiculo?->placa }} —
                {{ $registroCombustible->created_at->format('d/m/Y H:i') }}
            </flux:text>
        </div>
        <flux:spacer />
        <flux:badge
            :color="$this->estadoBadgeColor($registroCombustible->estado)"
            size="lg"
        >
            {{ ucfirst($registroCombustible->estado) }}
        </flux:badge>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">

        {{-- ── Columna izquierda: Info del envío ── --}}
        <div class="space-y-4">

            {{-- Datos del vehículo --}}
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 p-5 space-y-3">
                <flux:heading size="sm">{{ __('Vehículo') }}</flux:heading>
                <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                    <dt class="text-zinc-500">{{ __('Placa') }}</dt>
                    <dd class="font-mono font-semibold">{{ $registroCombustible->vehiculo?->placa ?? '—' }}</dd>

                    <dt class="text-zinc-500">{{ __('Marca / Modelo') }}</dt>
                    <dd>{{ $registroCombustible->vehiculo?->marca }} {{ $registroCombustible->vehiculo?->modelo }}</dd>

                    @if (auth()->user()->esAdmin())
                        <dt class="text-zinc-500">{{ __('Sucursal') }}</dt>
                        <dd>{{ $registroCombustible->sucursal?->nombre ?? '—' }}</dd>
                    @endif

                    <dt class="text-zinc-500">{{ __('Enviado por') }}</dt>
                    <dd>{{ $registroCombustible->enviadoPor?->name ?? '—' }}</dd>

                    <dt class="text-zinc-500">{{ __('Fecha envío') }}</dt>
                    <dd>{{ $registroCombustible->created_at->format('d/m/Y H:i') }}</dd>
                </dl>

                @if ($registroCombustible->observaciones_envio)
                    <div class="mt-2 rounded-lg bg-zinc-50 dark:bg-zinc-800 px-3 py-2 text-sm text-zinc-600 dark:text-zinc-400">
                        {{ $registroCombustible->observaciones_envio }}
                    </div>
                @endif
            </div>

            {{-- Fotos --}}
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 p-5 space-y-3">
                <flux:heading size="sm">{{ __('Fotos adjuntas') }}</flux:heading>
                <div class="grid grid-cols-2 gap-3">
                    <flux:button
                        wire:click="verFoto('factura')"
                        variant="outline"
                        icon="document-text"
                        class="h-16 flex-col gap-1 text-xs"
                    >
                        {{ __('Ver factura') }}
                    </flux:button>
                    <flux:button
                        wire:click="verFoto('odometro')"
                        variant="outline"
                        icon="photo"
                        class="h-16 flex-col gap-1 text-xs"
                    >
                        {{ __('Ver odómetro') }}
                    </flux:button>
                </div>
            </div>

            {{-- Datos aprobados (solo si aprobado) --}}
            @if ($registroCombustible->estado === 'aprobado')
                <div class="rounded-xl border border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-950 p-5 space-y-3">
                    <flux:heading size="sm" class="text-green-700 dark:text-green-400">
                        {{ __('Datos de la carga') }}
                    </flux:heading>
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                        <dt class="text-zinc-500">{{ __('Fecha carga') }}</dt>
                        <dd>{{ $registroCombustible->fecha_carga?->format('d/m/Y') ?? '—' }}</dd>

                        <dt class="text-zinc-500">{{ __('Km al cargar') }}</dt>
                        <dd>{{ number_format($registroCombustible->km_al_cargar ?? 0) }} km</dd>

                        <dt class="text-zinc-500">{{ __('Galones') }}</dt>
                        <dd>{{ $registroCombustible->galones }}</dd>

                        <dt class="text-zinc-500">{{ __('Precio / galón') }}</dt>
                        <dd>S/ {{ $registroCombustible->precio_galon }}</dd>

                        <dt class="text-zinc-500 font-medium">{{ __('Monto total') }}</dt>
                        <dd class="font-bold text-green-700 dark:text-green-400">
                            S/ {{ $registroCombustible->monto_total }}
                        </dd>

                        <dt class="text-zinc-500">{{ __('Tipo') }}</dt>
                        <dd>{{ $this->tipoCombustibleLabel($registroCombustible->tipo_combustible ?? '') }}</dd>

                        @if ($registroCombustible->proveedor)
                            <dt class="text-zinc-500">{{ __('Proveedor') }}</dt>
                            <dd>{{ $registroCombustible->proveedor }}</dd>
                        @endif

                        @if ($registroCombustible->numero_voucher)
                            <dt class="text-zinc-500">{{ __('Voucher') }}</dt>
                            <dd class="font-mono">{{ $registroCombustible->numero_voucher }}</dd>
                        @endif

                        <dt class="text-zinc-500">{{ __('Revisado por') }}</dt>
                        <dd>{{ $registroCombustible->revisadoPor?->name ?? '—' }}</dd>
                    </dl>

                    @if ($registroCombustible->observaciones_revision)
                        <div class="mt-2 rounded-lg bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-zinc-600 dark:text-zinc-400">
                            {{ $registroCombustible->observaciones_revision }}
                        </div>
                    @endif
                </div>
            @endif

            {{-- Rechazado --}}
            @if ($registroCombustible->estado === 'rechazado')
                <div class="rounded-xl border border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-950 p-5 space-y-2">
                    <flux:heading size="sm" class="text-red-700 dark:text-red-400">
                        {{ __('Registro rechazado') }}
                    </flux:heading>
                    <p class="text-sm text-zinc-500">
                        {{ __('Revisado por') }}: {{ $registroCombustible->revisadoPor?->name ?? '—' }}
                        · {{ $registroCombustible->revisado_en?->format('d/m/Y H:i') }}
                    </p>
                    @if ($registroCombustible->observaciones_revision)
                        <div class="rounded-lg bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-zinc-600 dark:text-zinc-400">
                            {{ $registroCombustible->observaciones_revision }}
                        </div>
                    @endif
                </div>
            @endif

        </div>

        {{-- ── Columna derecha: Formulario revisión (admin, pendiente) ── --}}
        @if (auth()->user()->esAdmin() && $registroCombustible->estado === 'pendiente')
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 p-5">
                <flux:heading size="sm" class="mb-4">{{ __('Completar y aprobar') }}</flux:heading>

                <form wire:submit="aprobar" class="space-y-4">

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <flux:input
                            wire:model="fechaCarga"
                            :label="__('Fecha de carga')"
                            type="date"
                            required
                        />

                        <flux:input
                            wire:model="kmAlCargar"
                            :label="__('Km al cargar')"
                            type="number"
                            min="0"
                            :placeholder="__('Ej: 45320')"
                            required
                        />
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <flux:input
                            wire:model.live="galones"
                            :label="__('Galones')"
                            type="number"
                            step="0.001"
                            min="0"
                            placeholder="10.500"
                            required
                        />

                        <flux:input
                            wire:model.live="precioGalon"
                            :label="__('Precio / galón (S/)')"
                            type="number"
                            step="0.001"
                            min="0"
                            placeholder="16.000"
                            required
                        />

                        <flux:input
                            wire:model="montoTotal"
                            :label="__('Monto total (S/)')"
                            type="number"
                            step="0.01"
                            min="0"
                            required
                        />
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <flux:select wire:model="tipoCombustible" :label="__('Tipo de combustible')" required>
                            <flux:select.option value="">{{ __('Seleccionar') }}</flux:select.option>
                            <flux:select.option value="gasolina">{{ __('Gasolina') }}</flux:select.option>
                            <flux:select.option value="diesel">{{ __('Diésel') }}</flux:select.option>
                            <flux:select.option value="glp">{{ __('GLP') }}</flux:select.option>
                            <flux:select.option value="gnv">{{ __('GNV') }}</flux:select.option>
                            <flux:select.option value="electrico">{{ __('Eléctrico') }}</flux:select.option>
                            <flux:select.option value="hibrido">{{ __('Híbrido') }}</flux:select.option>
                        </flux:select>

                        <flux:input
                            wire:model="proveedor"
                            :label="__('Proveedor (opcional)')"
                            :placeholder="__('Ej: Primax')"
                        />
                    </div>

                    <flux:input
                        wire:model="numeroVoucher"
                        :label="__('N° voucher (opcional)')"
                        :placeholder="__('Ej: 0001-00123456')"
                    />

                    <flux:textarea
                        wire:model="observacionesRevision"
                        :label="__('Observaciones (opcional)')"
                        rows="2"
                    />

                    <div class="flex justify-between gap-2 pt-2">
                        <flux:button
                            type="button"
                            variant="danger"
                            wire:click="abrirRechazar"
                            icon="x-mark"
                        >
                            {{ __('Rechazar') }}
                        </flux:button>

                        <flux:button
                            type="submit"
                            variant="primary"
                            icon="check"
                            wire:loading.attr="disabled"
                        >
                            <span wire:loading.remove wire:target="aprobar">{{ __('Aprobar carga') }}</span>
                            <span wire:loading wire:target="aprobar">{{ __('Guardando...') }}</span>
                        </flux:button>
                    </div>
                </form>
            </div>
        @endif

    </div>

    {{-- Modal confirmar rechazo --}}
    <flux:modal wire:model.self="showRechazarModal" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Rechazar registro') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Puedes indicar el motivo del rechazo.') }}</flux:text>
            </div>
            <flux:textarea
                wire:model="motivoRechazo"
                :label="__('Motivo (opcional)')"
                rows="3"
                :placeholder="__('Foto ilegible, datos incorrectos, etc.')"
            />
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancelar') }}</flux:button>
                </flux:modal.close>
                <flux:button wire:click="rechazar" variant="danger" wire:loading.attr="disabled">
                    {{ __('Confirmar rechazo') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Modal preview fotos (Alpine.js) --}}
    <div
        x-show="show"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="display: none;"
    >
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" x-on:click="show = false"></div>

        <div class="relative z-10 flex max-h-[90vh] w-full max-w-4xl flex-col rounded-2xl bg-white shadow-2xl dark:bg-zinc-900">
            <div class="flex items-center justify-between border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                <p class="truncate text-sm font-medium" x-text="nombre"></p>
                <div class="flex gap-2 ml-2 shrink-0">
                    <a
                        :href="url"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700"
                    >
                        <flux:icon name="arrow-down-tray" class="size-3.5" />
                        {{ __('Descargar') }}
                    </a>
                    <button
                        type="button"
                        x-on:click="show = false"
                        class="rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800"
                    >
                        <flux:icon name="x-mark" class="size-5" />
                    </button>
                </div>
            </div>

            <div class="flex-1 overflow-auto p-2">
                <template x-if="mime.startsWith('image/')">
                    <img :src="url" :alt="nombre" class="mx-auto max-h-[75vh] rounded-lg object-contain" />
                </template>
                <template x-if="mime === 'application/pdf'">
                    <iframe :src="url" class="h-[75vh] w-full rounded-lg border-0" :title="nombre"></iframe>
                </template>
            </div>
        </div>
    </div>

</div>
