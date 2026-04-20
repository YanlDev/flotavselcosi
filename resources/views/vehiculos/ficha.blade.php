<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ficha técnica - {{ $vehiculo->placa }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            line-height: 1.4;
            background: #f3f4f6;
        }

        /* ── Toolbar (screen only) ── */
        .toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 12px 20px;
            display: flex;
            gap: 12px;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }

        .toolbar-actions { display: flex; gap: 8px; flex: 1; }
        .toolbar-hint {
            color: #6b7280;
            font-size: 11px;
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            border: 1px solid #d1d5db;
            background: white;
            color: #374151;
            cursor: pointer;
            transition: background 0.15s;
        }
        .btn:hover { background: #f9fafb; }
        .btn-primary { background: #16a34a; color: white; border-color: #16a34a; }
        .btn-primary:hover { background: #15803d; }

        /* ── Page ── */
        .page {
            max-width: 820px;
            margin: 24px auto;
            background: white;
            padding: 40px 44px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            border-radius: 4px;
        }

        /* Header */
        .header {
            border-bottom: 3px solid #16a34a;
            padding-bottom: 14px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 16px;
        }

        .header-logo { flex: 1; }
        .header-logo img { max-height: 64px; max-width: 220px; display: block; }
        .brand-text { color: #16a34a; font-size: 18px; font-weight: 900; letter-spacing: 1px; }
        .brand-sub { color: #6b7280; font-size: 10px; margin-top: 2px; }

        .header-right { text-align: right; }
        .doc-title { font-size: 15px; font-weight: 700; color: #1a1a1a; }
        .doc-date { font-size: 10px; color: #6b7280; margin-top: 2px; }

        /* Plate hero */
        .plate-hero {
            background: #111827;
            color: white;
            padding: 18px 24px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .placa {
            font-size: 34px;
            font-weight: 900;
            letter-spacing: 5px;
            font-family: "Courier New", monospace;
        }

        .plate-meta { font-size: 11px; color: #d1d5db; margin-top: 4px; }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge-green { background: #dcfce7; color: #166534; }
        .badge-amber { background: #fef3c7; color: #92400e; }
        .badge-red   { background: #fee2e2; color: #991b1b; }
        .badge-zinc  { background: #e5e7eb; color: #374151; }

        .plate-tipo { margin-top: 6px; color: #d1d5db; font-size: 10px; }

        /* Problema activo */
        .alert-warning {
            background: #fef3c7;
            color: #78350f;
            padding: 10px 14px;
            border-left: 3px solid #f59e0b;
            border-radius: 4px;
            margin-bottom: 18px;
            font-size: 11px;
        }

        /* Sections */
        .section { margin-bottom: 20px; }
        .section-title {
            font-size: 11px;
            font-weight: 700;
            color: #16a34a;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }

        /* Data grid */
        .data-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px 24px;
        }

        .data-row {
            display: grid;
            grid-template-columns: 38% 1fr;
            gap: 8px;
            padding: 2px 0;
        }

        .data-row.full { grid-column: 1 / -1; }

        .data-label {
            color: #6b7280;
            font-size: 10px;
        }

        .data-value {
            font-weight: 600;
            color: #111827;
            font-size: 11px;
        }

        .mono { font-family: "Courier New", monospace; }

        /* Fotos */
        .fotos-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
        }

        .foto-box {
            border: 1px solid #d1d5db;
            border-radius: 4px;
            overflow: hidden;
            background: #f9fafb;
            aspect-ratio: 4 / 3;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .foto-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .foto-sin { color: #9ca3af; font-size: 10px; }

        .foto-caption {
            font-size: 9px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: center;
            margin-top: 4px;
        }

        /* Table */
        .data-list {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        .data-list th {
            background: #f3f4f6;
            color: #374151;
            text-align: left;
            padding: 6px 8px;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #d1d5db;
        }

        .data-list td {
            padding: 6px 8px;
            border-bottom: 1px solid #e5e7eb;
        }

        .text-right { text-align: right; }

        .obs-box {
            padding: 10px 12px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            font-size: 11px;
        }

        /* Footer */
        .footer {
            margin-top: 24px;
            padding-top: 12px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            font-size: 9px;
            color: #9ca3af;
        }

        /* ═══════ Print ═══════ */
        @page {
            size: A4 portrait;
            margin: 1.2cm;
        }

        @media print {
            html, body { background: white; }
            .toolbar { display: none !important; }
            .page {
                max-width: none;
                margin: 0;
                padding: 0;
                box-shadow: none;
                border-radius: 0;
            }
            .foto-box img { max-height: 120px; }
        }
    </style>
</head>
<body>

    {{-- Toolbar — solo en pantalla --}}
    <div class="toolbar">
        <div class="toolbar-actions">
            <a class="btn" href="{{ route('vehiculos.show', $vehiculo) }}">
                ← Volver
            </a>
            <button class="btn btn-primary" onclick="window.print()" type="button">
                🖨 Imprimir / Guardar como PDF
            </button>
        </div>
        <div class="toolbar-hint">
            Tip: en el diálogo de impresión desactiva "Encabezados y pies de página" para un PDF limpio.
        </div>
    </div>

    <div class="page">

        {{-- Header --}}
        <div class="header">
            <div class="header-logo">
                @if ($logoUrl)
                    <img src="{{ $logoUrl }}" alt="Selcosi Xport SAC">
                @else
                    <div class="brand-text">SELCOSI XPORT SAC</div>
                    <div class="brand-sub">Sistema de gestión vehicular</div>
                @endif
            </div>
            <div class="header-right">
                <div class="doc-title">FICHA TÉCNICA VEHICULAR</div>
                <div class="doc-date">Emitido: {{ $generadoEn->format('d/m/Y H:i') }}</div>
            </div>
        </div>

        {{-- Plate hero --}}
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
        <div class="plate-hero">
            <div>
                <div class="placa">{{ $vehiculo->placa }}</div>
                <div class="plate-meta">
                    {{ $vehiculo->marca }} {{ $vehiculo->modelo }}
                    @if ($vehiculo->anio) · {{ $vehiculo->anio }} @endif
                    @if ($vehiculo->color) · {{ ucfirst($vehiculo->color) }} @endif
                </div>
            </div>
            <div style="text-align:right;">
                <span class="badge badge-{{ $estadoColor }}">{{ $estadoLabel }}</span>
                <div class="plate-tipo">{{ $tipoLabel }}</div>
            </div>
        </div>

        @if ($vehiculo->problema_activo)
            <div class="alert-warning">
                <strong>Problema activo:</strong> {{ $vehiculo->problema_activo }}
            </div>
        @endif

        {{-- Identificación --}}
        <div class="section">
            <div class="section-title">Identificación del vehículo</div>
            <div class="data-grid">
                <div class="data-row"><span class="data-label">Placa</span><span class="data-value mono">{{ $vehiculo->placa }}</span></div>
                <div class="data-row"><span class="data-label">Tipo</span><span class="data-value">{{ $tipoLabel }}</span></div>
                <div class="data-row"><span class="data-label">Marca</span><span class="data-value">{{ $vehiculo->marca ?? '—' }}</span></div>
                <div class="data-row"><span class="data-label">Modelo</span><span class="data-value">{{ $vehiculo->modelo ?? '—' }}</span></div>
                <div class="data-row"><span class="data-label">Año</span><span class="data-value">{{ $vehiculo->anio ?? '—' }}</span></div>
                <div class="data-row"><span class="data-label">Color</span><span class="data-value">{{ $vehiculo->color ? ucfirst($vehiculo->color) : '—' }}</span></div>
                <div class="data-row"><span class="data-label">N° Motor</span><span class="data-value mono">{{ $vehiculo->num_motor ?? '—' }}</span></div>
                <div class="data-row"><span class="data-label">N° Chasis</span><span class="data-value mono">{{ $vehiculo->num_chasis ?? '—' }}</span></div>
                <div class="data-row"><span class="data-label">VIN</span><span class="data-value mono">{{ $vehiculo->vin ?? '—' }}</span></div>
                <div class="data-row"><span class="data-label">Combustible</span><span class="data-value">{{ $vehiculo->combustible ? ucfirst($vehiculo->combustible) : '—' }}</span></div>
                <div class="data-row"><span class="data-label">Transmisión</span><span class="data-value">{{ $vehiculo->transmision ? ucfirst($vehiculo->transmision) : '—' }}</span></div>
                <div class="data-row"><span class="data-label">Tracción</span><span class="data-value">{{ $vehiculo->traccion ?? '—' }}</span></div>
                <div class="data-row"><span class="data-label">Capacidad carga</span><span class="data-value">{{ $vehiculo->capacidad_carga ?? '—' }}</span></div>
                <div class="data-row"><span class="data-label">GPS</span><span class="data-value">{{ $vehiculo->tiene_gps ? 'Sí' : 'No' }}</span></div>
            </div>
        </div>

        {{-- Propiedad y operación --}}
        <div class="section">
            <div class="section-title">Propiedad y operación</div>
            <div class="data-grid">
                <div class="data-row full"><span class="data-label">Propietario</span><span class="data-value">{{ $vehiculo->propietario ?? '—' }}</span></div>
                <div class="data-row"><span class="data-label">RUC propietario</span><span class="data-value mono">{{ $vehiculo->ruc_propietario ?? '—' }}</span></div>
                <div class="data-row"><span class="data-label">Fecha adquisición</span><span class="data-value">{{ $vehiculo->fecha_adquisicion?->format('d/m/Y') ?? '—' }}</span></div>
                <div class="data-row"><span class="data-label">Sucursal</span><span class="data-value">{{ $vehiculo->sucursal?->nombre ?? '—' }}</span></div>
                <div class="data-row"><span class="data-label">Km actuales</span><span class="data-value mono">{{ $vehiculo->km_actuales ? number_format($vehiculo->km_actuales) : '—' }}</span></div>
                <div class="data-row full"><span class="data-label">Conductor asignado</span>
                    <span class="data-value">
                        {{ $vehiculo->conductor?->nombre_completo ?? $vehiculo->conductor_nombre ?? '—' }}
                        @if ($vehiculo->conductor?->dni) · DNI: {{ $vehiculo->conductor->dni }} @endif
                        @if ($vehiculo->conductor?->telefono ?? $vehiculo->conductor_tel) · Tel: {{ $vehiculo->conductor?->telefono ?? $vehiculo->conductor_tel }} @endif
                    </span>
                </div>
                @if ($vehiculo->conductor?->licencia_numero)
                    <div class="data-row"><span class="data-label">Licencia</span><span class="data-value mono">{{ $vehiculo->conductor->licencia_numero }}</span></div>
                    <div class="data-row"><span class="data-label">Vence</span><span class="data-value">{{ $vehiculo->conductor->licencia_vencimiento?->format('d/m/Y') ?? '—' }}</span></div>
                @endif
            </div>
        </div>

        {{-- Fotos --}}
        @if ($fotos->isNotEmpty())
            <div class="section">
                <div class="section-title">Fotografías</div>
                <div class="fotos-grid">
                    @foreach (['frontal' => 'Frontal', 'lateral_der' => 'Lateral der.', 'lateral_izq' => 'Lateral izq.', 'trasera' => 'Trasera'] as $cat => $label)
                        <div>
                            <div class="foto-box">
                                @if ($fotos[$cat] ?? null)
                                    <img src="{{ route('vehiculos.fotos.thumbnail', [$vehiculo, $fotos[$cat]]) }}" alt="{{ $label }}">
                                @else
                                    <span class="foto-sin">Sin foto</span>
                                @endif
                            </div>
                            <div class="foto-caption">{{ $label }}</div>
                        </div>
                    @endforeach
                </div>
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
                <div class="data-grid">
                    <div class="data-row"><span class="data-label">Fecha</span><span class="data-value">{{ $ultimoMantenimiento->fecha_servicio->format('d/m/Y') }}</span></div>
                    <div class="data-row"><span class="data-label">Tipo</span><span class="data-value">{{ ucfirst($ultimoMantenimiento->tipo) }}</span></div>
                    <div class="data-row"><span class="data-label">Categoría</span><span class="data-value">{{ ucfirst(str_replace('_', ' ', $ultimoMantenimiento->categoria)) }}</span></div>
                    <div class="data-row"><span class="data-label">Taller</span><span class="data-value">{{ $ultimoMantenimiento->taller ?? '—' }}</span></div>
                    <div class="data-row"><span class="data-label">Km al servicio</span><span class="data-value mono">{{ $ultimoMantenimiento->km_servicio ? number_format($ultimoMantenimiento->km_servicio) : '—' }}</span></div>
                    <div class="data-row"><span class="data-label">Próximo km</span><span class="data-value mono">{{ $ultimoMantenimiento->proximo_km ? number_format($ultimoMantenimiento->proximo_km) : '—' }}</span></div>
                    @if ($ultimoMantenimiento->descripcion)
                        <div class="data-row full"><span class="data-label">Descripción</span><span class="data-value" style="font-weight:normal;">{{ $ultimoMantenimiento->descripcion }}</span></div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Observaciones --}}
        @if ($vehiculo->observaciones)
            <div class="section">
                <div class="section-title">Observaciones</div>
                <div class="obs-box">{{ $vehiculo->observaciones }}</div>
            </div>
        @endif

        {{-- Footer --}}
        <div class="footer">
            <span>Generado por {{ $generadoPor->name }} · {{ $generadoEn->format('d/m/Y H:i') }}</span>
            <span>Selcosi Flota · Ficha {{ $vehiculo->placa }}</span>
        </div>

    </div>

</body>
</html>
