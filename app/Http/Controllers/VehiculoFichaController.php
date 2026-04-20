<?php

namespace App\Http\Controllers;

use App\Models\DocumentoVehicular;
use App\Models\FotoVehiculo;
use App\Models\Mantenimiento;
use App\Models\Vehiculo;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VehiculoFichaController extends Controller
{
    /**
     * Renderiza la ficha técnica del vehículo como HTML imprimible.
     * El usuario guarda como PDF desde el diálogo de impresión del navegador.
     */
    public function __invoke(Request $request, Vehiculo $vehiculo): View
    {
        abort_unless($request->user()->can('view', $vehiculo), 403);

        $vehiculo->load('sucursal', 'conductor', 'creadoPor');

        $fotos = FotoVehiculo::where('vehiculo_id', $vehiculo->id)
            ->whereIn('categoria', ['frontal', 'lateral_der', 'lateral_izq', 'trasera'])
            ->get()
            ->groupBy('categoria')
            ->map(fn ($grupo) => $grupo->first());

        $ultimoMantenimiento = Mantenimiento::where('vehiculo_id', $vehiculo->id)
            ->orderByDesc('fecha_servicio')
            ->first();

        $documentos = DocumentoVehicular::where('vehiculo_id', $vehiculo->id)
            ->whereNotNull('vencimiento')
            ->orderBy('tipo')
            ->get();

        $logoUrl = $this->logoUrl();

        return view('vehiculos.ficha', [
            'vehiculo' => $vehiculo,
            'fotos' => $fotos,
            'ultimoMantenimiento' => $ultimoMantenimiento,
            'documentos' => $documentos,
            'generadoPor' => $request->user(),
            'generadoEn' => now(),
            'logoUrl' => $logoUrl,
        ]);
    }

    /**
     * URL pública del logo. Prefiere el logo branded horizontal; cae al ícono si falta.
     */
    private function logoUrl(): ?string
    {
        if (is_file(public_path('images/logo-selcosi.png'))) {
            return asset('images/logo-selcosi.png');
        }

        if (is_file(public_path('selcosilog.png'))) {
            return asset('selcosilog.png');
        }

        return null;
    }
}
