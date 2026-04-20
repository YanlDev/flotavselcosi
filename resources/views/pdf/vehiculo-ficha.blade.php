<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ficha técnica - {{ $vehiculo->placa }}</title>
    <style>
        @page {
            margin: 32px 36px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1a1a1a;
            line-height: 1.4;
            margin: 0;
        }

        /* Header */
        .header {
            border-bottom: 3px solid #16a34a;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: middle;
        }

        .brand {
            color: #16a34a;
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .brand-sub {
            color: #666;
            font-size: 9px;
            margin-top: 2px;
        }

        .title-block {
            text-align: right;
        }

        .title {
            font-size: 14px;
            font-weight: bold;
            color: #1a1a1a;
            margin: 0;
        }

        .subtitle {
            font-size: 9px;
            color: #666;
        }

        /* Plate hero */
        .plate-hero {
            background: #111827;
            color: #fff;
            padding: 14px 20px;
            border-radius: 6px;
            margin-bottom: 16px;
        }

        .plate-hero .placa {
            font-size: 28px;
            font-weight: bold;
            letter-spacing: 4px;
            font-family: Courier, monospace;
        }

        .plate-hero .meta {
            font-size: 10px;
            color: #d1d5db;
            margin-top: 4px;
        }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-green  { background: #dcfce7; color: #166534; }
        .badge-amber  { background: #fef3c7; color: #92400e; }
        .badge-red    { background: #fee2e2; color: #991b1b; }
        .badge-zinc   { background: #e5e7eb; color: #374151; }

        /* Sections */
        .section {
            margin-bottom: 14px;
        }

        .section-title {
            font-size: 10px;
            font-weight: bold;
            color: #16a34a;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 4px;
            margin-bottom: 8px;
        }

        /* Data grid — 2-column layout via table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table td {
            padding: 3px 6px;
            vertical-align: top;
        }

        .data-table .label {
            color: #6b7280;
            width: 28%;
            font-size: 9px;
        }

        .data-table .value {
            font-weight: bold;
            color: #1a1a1a;
            width: 22%;
            font-size: 10px;
        }

        .mono {
            font-family: Courier, monospace;
        }

        /* Photos */
        .fotos-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 4px;
        }

        .fotos-grid td {
            width: 25%;
            vertical-align: top;
            text-align: center;
        }

        .foto-box {
            border: 1px solid #d1d5db;
            border-radius: 4px;
            padding: 3px;
            background: #f9fafb;
            height: 90px;
            overflow: hidden;
        }

        .foto-box img {
            width: 100%;
            height: 70px;
            object-fit: cover;
            border-radius: 3px;
        }

        .foto-box .foto-sin {
            font-size: 8px;
            color: #9ca3af;
            padding-top: 28px;
        }

        .foto-caption {
            font-size: 8px;
            color: #6b7280;
            margin-top: 2px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Table de docs/mantenimiento */
        .data-list {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }

        .data-list th {
            background: #f3f4f6;
            color: #374151;
            text-align: left;
            padding: 5px 6px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8px;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #d1d5db;
        }

        .data-list td {
            padding: 5px 6px;
            border-bottom: 1px solid #e5e7eb;
        }

        .text-right { text-align: right; }

        .muted { color: #9ca3af; }

        /* Footer */
        .footer {
            position: fixed;
            bottom: -18px;
            left: 0;
            right: 0;
            font-size: 8px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 6px;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }

        .footer-table td {
            padding: 0;
        }
    </style>
</head>
<body>

    {{-- Header --}}
    <div class="header">
        <table class="header-table">
            <tr>
                <td style="width:65%;">
                    @if ($logoBase64)
                        <img src="{{ $logoBase64 }}" alt="Selcosi Xport SAC" style="height:48px; max-width:240px;">
                    @else
                        <div class="brand">SELCOSI XPORT SAC</div>
                        <div class="brand-sub">Sistema de gestión vehicular</div>
                    @endif
                </td>
                <td class="title-block">
                    <div class="title">FICHA TÉCNICA VEHICULAR</div>
                    <div class="subtitle">Emitido: {{ $generadoEn->format('d/m/Y H:i') }}</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Plate hero --}}
    <div class="plate-hero">
        <table style="width:100%; border-collapse:collapse;">
            <tr>
                <td>
                    <div class="placa">{{ $vehiculo->placa }}</div>
                    <div class="meta">
                        {{ $vehiculo->marca }} {{ $vehiculo->modelo }}
                        @if ($vehiculo->anio) · {{ $vehiculo->anio }} @endif
                        @if ($vehiculo->color) · {{ ucfirst($vehiculo->color) }} @endif
                    </div>
                </td>
                <td style="text-align:right; vertical-align:top;">
                    @php
                        $estadoColor = match ($vehiculo->estado) {
                            'operativo' => 'green',
                            'parcialmente' => 'amber',
                            'fuera_de_servicio' => 'red',
                            default => 'zinc',
                        };
                        $estadoLabel = match ($vehiculo->estado) {
                            'operativo' => 'Operativo',
                            'parcialmente' => 'Parcialmente operativo',
                            'fuera_de_servicio' => 'Fuera de servicio',
                            default => $vehiculo->estado,
                        };
                        $tipoLabel = match ($vehiculo->tipo) {
                            'moto' => 'Moto',
                            'auto' => 'Auto',
                            'camioneta' => 'Camioneta',
                            'minivan' => 'Minivan',
                            'furgon' => 'Furgón',
                            'bus' => 'Bus',
                            'vehiculo_pesado' => 'Vehículo pesado',
                            default => $vehiculo->tipo,
                        };
                    @endphp
                    <span class="badge badge-{{ $estadoColor }}">{{ $estadoLabel }}</span>
                    <div style="margin-top:6px; color:#d1d5db; font-size:9px;">
                        {{ $tipoLabel }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    @if ($vehiculo->problema_activo)
        <div style="background:#fef3c7; color:#78350f; padding:8px 12px; border-left:3px solid #f59e0b; border-radius:3px; margin-bottom:14px; font-size:9.5px;">
            <strong>Problema activo:</strong> {{ $vehiculo->problema_activo }}
        </div>
    @endif

    {{-- Identificación --}}
    <div class="section">
        <div class="section-title">Identificación del vehículo</div>
        <table class="data-table">
            <tr>
                <td class="label">Placa</td>
                <td class="value mono">{{ $vehiculo->placa }}</td>
                <td class="label">Tipo</td>
                <td class="value">{{ $tipoLabel }}</td>
            </tr>
            <tr>
                <td class="label">Marca</td>
                <td class="value">{{ $vehiculo->marca ?? '—' }}</td>
                <td class="label">Modelo</td>
                <td class="value">{{ $vehiculo->modelo ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label">Año</td>
                <td class="value">{{ $vehiculo->anio ?? '—' }}</td>
                <td class="label">Color</td>
                <td class="value">{{ $vehiculo->color ? ucfirst($vehiculo->color) : '—' }}</td>
            </tr>
            <tr>
                <td class="label">N° Motor</td>
                <td class="value mono">{{ $vehiculo->num_motor ?? '—' }}</td>
                <td class="label">N° Chasis</td>
                <td class="value mono">{{ $vehiculo->num_chasis ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label">VIN</td>
                <td class="value mono">{{ $vehiculo->vin ?? '—' }}</td>
                <td class="label">Combustible</td>
                <td class="value">{{ $vehiculo->combustible ? ucfirst($vehiculo->combustible) : '—' }}</td>
            </tr>
            <tr>
                <td class="label">Transmisión</td>
                <td class="value">{{ $vehiculo->transmision ? ucfirst($vehiculo->transmision) : '—' }}</td>
                <td class="label">Tracción</td>
                <td class="value">{{ $vehiculo->traccion ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label">Capacidad de carga</td>
                <td class="value">{{ $vehiculo->capacidad_carga ?? '—' }}</td>
                <td class="label">GPS</td>
                <td class="value">{{ $vehiculo->tiene_gps ? 'Sí' : 'No' }}</td>
            </tr>
        </table>
    </div>

    {{-- Propiedad y operación --}}
    <div class="section">
        <div class="section-title">Propiedad y operación</div>
        <table class="data-table">
            <tr>
                <td class="label">Propietario</td>
                <td class="value" colspan="3">{{ $vehiculo->propietario ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label">RUC propietario</td>
                <td class="value mono">{{ $vehiculo->ruc_propietario ?? '—' }}</td>
                <td class="label">Fecha adquisición</td>
                <td class="value">{{ $vehiculo->fecha_adquisicion?->format('d/m/Y') ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label">Sucursal</td>
                <td class="value">{{ $vehiculo->sucursal?->nombre ?? '—' }}</td>
                <td class="label">Km actuales</td>
                <td class="value mono">{{ $vehiculo->km_actuales ? number_format($vehiculo->km_actuales) : '—' }}</td>
            </tr>
            <tr>
                <td class="label">Conductor asignado</td>
                <td class="value" colspan="3">
                    {{ $vehiculo->conductor?->nombre_completo ?? $vehiculo->conductor_nombre ?? '—' }}
                    @if ($vehiculo->conductor?->dni)
                        · DNI: {{ $vehiculo->conductor->dni }}
                    @endif
                    @if ($vehiculo->conductor?->telefono ?? $vehiculo->conductor_tel)
                        · Tel: {{ $vehiculo->conductor?->telefono ?? $vehiculo->conductor_tel }}
                    @endif
                </td>
            </tr>
            @if ($vehiculo->conductor?->licencia_numero)
                <tr>
                    <td class="label">Licencia</td>
                    <td class="value mono">{{ $vehiculo->conductor->licencia_numero }}</td>
                    <td class="label">Vence</td>
                    <td class="value">{{ $vehiculo->conductor->licencia_vencimiento?->format('d/m/Y') ?? '—' }}</td>
                </tr>
            @endif
        </table>
    </div>

    {{-- Fotos --}}
    @if ($fotosBase64->filter()->isNotEmpty())
        <div class="section">
            <div class="section-title">Fotografías</div>
            <table class="fotos-grid">
                <tr>
                    @foreach (['frontal' => 'Frontal', 'lateral_der' => 'Lateral der.', 'lateral_izq' => 'Lateral izq.', 'trasera' => 'Trasera'] as $cat => $label)
                        <td>
                            <div class="foto-box">
                                @if ($fotosBase64[$cat] ?? null)
                                    <img src="{{ $fotosBase64[$cat] }}" alt="{{ $label }}">
                                @else
                                    <div class="foto-sin">Sin foto</div>
                                @endif
                            </div>
                            <div class="foto-caption">{{ $label }}</div>
                        </td>
                    @endforeach
                </tr>
            </table>
        </div>
    @endif

    {{-- Documentos --}}
    @if ($documentos->isNotEmpty())
        <div class="section">
            <div class="section-title">Documentos con vencimiento</div>
            <table class="data-list">
                <thead>
                    <tr>
                        <th style="width:25%;">Tipo</th>
                        <th>Nombre</th>
                        <th style="width:18%;" class="text-right">Vence</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($documentos as $doc)
                        <tr>
                            <td>{{ ucfirst(str_replace('_', ' ', $doc->tipo)) }}</td>
                            <td>{{ $doc->nombre }}</td>
                            <td class="text-right mono">{{ $doc->vencimiento?->format('d/m/Y') ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Último mantenimiento --}}
    @if ($ultimoMantenimiento)
        <div class="section">
            <div class="section-title">Último mantenimiento registrado</div>
            <table class="data-table">
                <tr>
                    <td class="label">Fecha</td>
                    <td class="value">{{ $ultimoMantenimiento->fecha_servicio->format('d/m/Y') }}</td>
                    <td class="label">Tipo</td>
                    <td class="value">{{ ucfirst($ultimoMantenimiento->tipo) }}</td>
                </tr>
                <tr>
                    <td class="label">Categoría</td>
                    <td class="value">{{ ucfirst(str_replace('_', ' ', $ultimoMantenimiento->categoria)) }}</td>
                    <td class="label">Taller</td>
                    <td class="value">{{ $ultimoMantenimiento->taller ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="label">Km al servicio</td>
                    <td class="value mono">{{ $ultimoMantenimiento->km_servicio ? number_format($ultimoMantenimiento->km_servicio) : '—' }}</td>
                    <td class="label">Próximo km</td>
                    <td class="value mono">{{ $ultimoMantenimiento->proximo_km ? number_format($ultimoMantenimiento->proximo_km) : '—' }}</td>
                </tr>
                @if ($ultimoMantenimiento->descripcion)
                    <tr>
                        <td class="label">Descripción</td>
                        <td class="value" colspan="3" style="font-weight:normal;">{{ $ultimoMantenimiento->descripcion }}</td>
                    </tr>
                @endif
            </table>
        </div>
    @endif

    {{-- Observaciones --}}
    @if ($vehiculo->observaciones)
        <div class="section">
            <div class="section-title">Observaciones</div>
            <div style="padding:6px 8px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:3px; font-size:9.5px;">
                {{ $vehiculo->observaciones }}
            </div>
        </div>
    @endif

    {{-- Footer --}}
    <div class="footer">
        <table class="footer-table">
            <tr>
                <td>
                    Generado por {{ $generadoPor->name }} · {{ $generadoEn->format('d/m/Y H:i') }}
                </td>
                <td class="text-right">
                    Selcosi Flota Vehicular · Ficha {{ $vehiculo->placa }}
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
