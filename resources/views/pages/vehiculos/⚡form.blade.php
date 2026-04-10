<?php

use App\Models\Conductor;
use App\Models\Sucursal;
use App\Models\Vehiculo;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Vehículo')] class extends Component {
    public ?int $editingId = null;

    // Básico
    public int|string $sucursalId = '';
    public string $placa = '';
    public string $tipo = '';
    public string $marca = '';
    public string $modelo = '';
    public string $anio = '';
    public string $color = '';

    // SUNARP
    public string $numMotor = '';
    public string $numChasis = '';
    public string $vin = '';
    public string $propietario = '';
    public string $rucPropietario = '';

    // Estado
    public string $estado = 'operativo';
    public string $problemaActivo = '';

    // Técnico
    public string $combustible = '';
    public string $transmision = '';
    public string $traccion = '';
    public string $kmActuales = '';
    public string $capacidadCarga = '';

    // Conductor
    public int|string $conductorId = '';

    // Administrativo
    public string $fechaAdquisicion = '';
    public bool $tieneGps = false;
    public string $observaciones = '';

    public function mount(?Vehiculo $vehiculo = null): void
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        if ($vehiculo?->exists) {
            $this->editingId = $vehiculo->id;
            $this->sucursalId = $vehiculo->sucursal_id;
            $this->placa = $vehiculo->placa;
            $this->tipo = $vehiculo->tipo;
            $this->marca = $vehiculo->marca;
            $this->modelo = $vehiculo->modelo;
            $this->anio = (string) $vehiculo->anio;
            $this->color = $vehiculo->color ?? '';
            $this->numMotor = $vehiculo->num_motor ?? '';
            $this->numChasis = $vehiculo->num_chasis ?? '';
            $this->vin = $vehiculo->vin ?? '';
            $this->propietario = $vehiculo->propietario ?? '';
            $this->rucPropietario = $vehiculo->ruc_propietario ?? '';
            $this->estado = $vehiculo->estado;
            $this->problemaActivo = $vehiculo->problema_activo ?? '';
            $this->combustible = $vehiculo->combustible;
            $this->transmision = $vehiculo->transmision ?? '';
            $this->traccion = $vehiculo->traccion ?? '';
            $this->kmActuales = (string) ($vehiculo->km_actuales ?? '');
            $this->capacidadCarga = $vehiculo->capacidad_carga ?? '';
            $conductor = Conductor::where('vehiculo_id', $vehiculo->id)->activos()->first();
            $this->conductorId = $conductor?->id ?? '';
            $this->fechaAdquisicion = $vehiculo->fecha_adquisicion?->format('Y-m-d') ?? '';
            $this->tieneGps = (bool) $vehiculo->tiene_gps;
            $this->observaciones = $vehiculo->observaciones ?? '';
        }
    }

    #[Computed]
    public function sucursales(): \Illuminate\Database\Eloquent\Collection
    {
        return Sucursal::activas()->orderBy('nombre')->get();
    }

    #[Computed]
    public function conductores(): \Illuminate\Database\Eloquent\Collection
    {
        return Conductor::activos()
            ->where(function ($q) {
                $q->whereNull('vehiculo_id')
                    ->orWhere('vehiculo_id', $this->editingId);
            })
            ->orderBy('nombre_completo')
            ->get();
    }

    public function save(): void
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $placaRule = $this->editingId
            ? 'unique:vehiculos,placa,'.$this->editingId
            : 'unique:vehiculos,placa';

        $validated = $this->validate([
            'sucursalId' => ['required', 'exists:sucursales,id'],
            'placa' => ['required', 'string', 'max:20', $placaRule],
            'tipo' => ['required', 'in:moto,auto,camioneta,minivan,furgon,bus,vehiculo_pesado'],
            'marca' => ['required', 'string', 'max:100'],
            'modelo' => ['required', 'string', 'max:100'],
            'anio' => ['required', 'integer', 'min:1900', 'max:'.date('Y')],
            'color' => ['nullable', 'string', 'max:50'],
            'numMotor' => ['nullable', 'string', 'max:50'],
            'numChasis' => ['nullable', 'string', 'max:50'],
            'vin' => ['nullable', 'string', 'max:50'],
            'propietario' => ['nullable', 'string', 'max:200'],
            'rucPropietario' => ['nullable', 'string', 'digits:11'],
            'estado' => ['required', 'in:operativo,parcialmente,fuera_de_servicio'],
            'problemaActivo' => ['nullable', 'string'],
            'combustible' => ['required', 'in:gasolina,diesel,glp,gnv,electrico,hibrido'],
            'transmision' => ['nullable', 'in:manual,automatico'],
            'traccion' => ['nullable', 'in:4x2,4x4'],
            'kmActuales' => ['nullable', 'integer', 'min:0'],
            'capacidadCarga' => ['nullable', 'string', 'max:50'],
            'conductorId' => ['nullable', 'exists:conductores,id'],
            'fechaAdquisicion' => ['nullable', 'date'],
            'tieneGps' => ['boolean'],
            'observaciones' => ['nullable', 'string'],
        ]);

        $data = [
            'sucursal_id' => $validated['sucursalId'],
            'placa' => strtoupper($validated['placa']),
            'tipo' => $validated['tipo'],
            'marca' => $validated['marca'],
            'modelo' => $validated['modelo'],
            'anio' => $validated['anio'],
            'color' => $validated['color'] ?: null,
            'num_motor' => $validated['numMotor'] ?: null,
            'num_chasis' => $validated['numChasis'] ?: null,
            'vin' => $validated['vin'] ?: null,
            'propietario' => $validated['propietario'] ?: null,
            'ruc_propietario' => $validated['rucPropietario'] ?: null,
            'estado' => $validated['estado'],
            'problema_activo' => $validated['problemaActivo'] ?: null,
            'combustible' => $validated['combustible'],
            'transmision' => $validated['transmision'] ?: null,
            'traccion' => $validated['traccion'] ?: null,
            'km_actuales' => $validated['kmActuales'] ?: null,
            'capacidad_carga' => $validated['capacidadCarga'] ?: null,
            'fecha_adquisicion' => $validated['fechaAdquisicion'] ?: null,
            'tiene_gps' => $validated['tieneGps'],
            'observaciones' => $validated['observaciones'] ?: null,
        ];

        // Resolver conductor seleccionado para campos de compatibilidad
        $conductorSeleccionado = $validated['conductorId']
            ? Conductor::find($validated['conductorId'])
            : null;

        $data['conductor_nombre'] = $conductorSeleccionado?->nombre_completo;
        $data['conductor_tel'] = $conductorSeleccionado?->telefono;

        if ($this->editingId) {
            $vehiculo = Vehiculo::findOrFail($this->editingId);
            // Desasignar conductor anterior si cambió
            Conductor::where('vehiculo_id', $this->editingId)->update(['vehiculo_id' => null]);
            $vehiculo->update($data);
        } else {
            $data['creado_por'] = auth()->id();
            $vehiculo = Vehiculo::create($data);
        }

        // Asignar el nuevo conductor al vehículo
        if ($conductorSeleccionado) {
            $conductorSeleccionado->update(['vehiculo_id' => $vehiculo->id]);
        }

        $this->redirect(route('vehiculos.show', $vehiculo), navigate: true);
    }

    public function getTitle(): string
    {
        return $this->editingId ? 'Editar vehículo' : 'Nuevo vehículo';
    }
}; ?>

<section class="w-full max-w-4xl mx-auto px-3 py-4 sm:p-6 lg:p-8">
    <x-ui.page-header
        :title="$editingId ? __('Editar vehículo') : __('Nuevo vehículo')"
        :subtitle="$editingId ? __('Actualiza los datos del vehículo') : __('Registra un nuevo vehículo en la flota')"
        :breadcrumbs="[
            ['label' => __('Dashboard'), 'href' => route('dashboard')],
            ['label' => __('Vehículos'), 'href' => route('vehiculos.index')],
            ['label' => $editingId ? __('Editar') : __('Nuevo')],
        ]"
    >
        <x-slot:actions>
            <flux:button :href="route('vehiculos.index')" variant="ghost" icon="arrow-left" wire:navigate>
                {{ __('Volver') }}
            </flux:button>
        </x-slot:actions>
    </x-ui.page-header>

    <form wire:submit="save" class="space-y-8">

        {{-- Datos básicos --}}
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Identificación') }}</flux:heading>
            <flux:separator />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:select wire:model="sucursalId" :label="__('Sucursal')" required>
                    <flux:select.option value="">{{ __('Seleccionar sucursal') }}</flux:select.option>
                    @foreach ($this->sucursales as $s)
                        <flux:select.option :value="$s->id">{{ $s->nombre }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="placa" :label="__('Placa')" placeholder="ABC-123" class="uppercase" required />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <flux:select wire:model="tipo" :label="__('Tipo')" required>
                    <flux:select.option value="">{{ __('Seleccionar') }}</flux:select.option>
                    <flux:select.option value="moto">{{ __('Moto') }}</flux:select.option>
                    <flux:select.option value="auto">{{ __('Auto') }}</flux:select.option>
                    <flux:select.option value="camioneta">{{ __('Camioneta') }}</flux:select.option>
                    <flux:select.option value="minivan">{{ __('Minivan') }}</flux:select.option>
                    <flux:select.option value="furgon">{{ __('Furgón') }}</flux:select.option>
                    <flux:select.option value="bus">{{ __('Bus') }}</flux:select.option>
                    <flux:select.option value="vehiculo_pesado">{{ __('Vehículo pesado') }}</flux:select.option>
                </flux:select>

                <flux:input wire:model="marca" :label="__('Marca')" placeholder="Toyota" required />
                <flux:input wire:model="modelo" :label="__('Modelo')" placeholder="Hilux" required />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:input wire:model="anio" :label="__('Año')" type="number" min="1900" :max="date('Y')" required />
                <flux:input wire:model="color" :label="__('Color')" placeholder="Blanco" />
            </div>
        </div>

        {{-- SUNARP --}}
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Tarjeta de propiedad (SUNARP)') }}</flux:heading>
            <flux:separator />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:input wire:model="numMotor" :label="__('N° Motor')" />
                <flux:input wire:model="numChasis" :label="__('N° Chasis')" />
                <flux:input wire:model="vin" :label="__('VIN / Serie')" />
                <flux:input wire:model="propietario" :label="__('Propietario registrado')" />
                <flux:input wire:model="rucPropietario" :label="__('RUC propietario')" maxlength="11" />
            </div>
        </div>

        {{-- Estado --}}
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Estado operativo') }}</flux:heading>
            <flux:separator />

            <flux:select wire:model.live="estado" :label="__('Estado')" required>
                <flux:select.option value="operativo">{{ __('Operativo') }}</flux:select.option>
                <flux:select.option value="parcialmente">{{ __('Parcialmente operativo') }}</flux:select.option>
                <flux:select.option value="fuera_de_servicio">{{ __('Fuera de servicio') }}</flux:select.option>
            </flux:select>

            @if ($estado !== 'operativo')
                <flux:textarea
                    wire:model="problemaActivo"
                    :label="__('Descripción del problema')"
                    rows="2"
                />
            @endif
        </div>

        {{-- Técnico --}}
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Datos técnicos') }}</flux:heading>
            <flux:separator />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <flux:select wire:model="combustible" :label="__('Combustible')" required>
                    <flux:select.option value="">{{ __('Seleccionar') }}</flux:select.option>
                    <flux:select.option value="gasolina">{{ __('Gasolina') }}</flux:select.option>
                    <flux:select.option value="diesel">{{ __('Diésel') }}</flux:select.option>
                    <flux:select.option value="glp">{{ __('GLP') }}</flux:select.option>
                    <flux:select.option value="gnv">{{ __('GNV') }}</flux:select.option>
                    <flux:select.option value="electrico">{{ __('Eléctrico') }}</flux:select.option>
                    <flux:select.option value="hibrido">{{ __('Híbrido') }}</flux:select.option>
                </flux:select>

                <flux:select wire:model="transmision" :label="__('Transmisión')">
                    <flux:select.option value="">{{ __('No especificada') }}</flux:select.option>
                    <flux:select.option value="manual">{{ __('Manual') }}</flux:select.option>
                    <flux:select.option value="automatico">{{ __('Automático') }}</flux:select.option>
                </flux:select>

                <flux:select wire:model="traccion" :label="__('Tracción')">
                    <flux:select.option value="">{{ __('No especificada') }}</flux:select.option>
                    <flux:select.option value="4x2">4x2</flux:select.option>
                    <flux:select.option value="4x4">4x4</flux:select.option>
                </flux:select>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:input wire:model="kmActuales" :label="__('Km actuales')" type="number" min="0" />
                <flux:input wire:model="capacidadCarga" :label="__('Capacidad de carga')" placeholder="1.5 ton" />
            </div>
        </div>

        {{-- Conductor --}}
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Conductor asignado') }}</flux:heading>
            <flux:separator />

            @if ($this->conductores->isEmpty())
                <flux:callout color="zinc" icon="user-minus">
                    <flux:callout.text>
                        {{ __('No hay conductores registrados disponibles. Registra conductores desde el módulo de conductores.') }}
                    </flux:callout.text>
                </flux:callout>
            @else
                <flux:select wire:model="conductorId" :label="__('Conductor')">
                    <flux:select.option value="">{{ __('Sin conductor asignado') }}</flux:select.option>
                    @foreach ($this->conductores as $conductor)
                        <flux:select.option :value="$conductor->id">
                            {{ $conductor->nombre_completo }}
                            @if ($conductor->licencia_categoria) · {{ $conductor->licencia_categoria }} @endif
                        </flux:select.option>
                    @endforeach
                </flux:select>
            @endif
        </div>

        {{-- Administrativo --}}
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Datos administrativos') }}</flux:heading>
            <flux:separator />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:input wire:model="fechaAdquisicion" :label="__('Fecha de adquisición')" type="date" />
                <div class="flex items-center gap-3 pt-6">
                    <flux:checkbox wire:model="tieneGps" id="tieneGps" />
                    <label for="tieneGps" class="text-sm font-medium cursor-pointer select-none">
                        {{ __('Cuenta con GPS') }}
                    </label>
                </div>
            </div>

            <flux:textarea wire:model="observaciones" :label="__('Observaciones')" rows="3" />
        </div>

        {{-- Acciones --}}
        <div class="flex justify-end gap-3 pb-8">
            <flux:button :href="route('vehiculos.index')" variant="ghost" wire:navigate>
                {{ __('Cancelar') }}
            </flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                {{ $editingId ? __('Actualizar vehículo') : __('Crear vehículo') }}
            </flux:button>
        </div>

    </form>
</section>
