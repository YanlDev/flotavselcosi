<?php

namespace App\Http\Controllers;

use App\Models\DocumentoVehicular;
use App\Models\FotoVehiculo;
use App\Models\Mantenimiento;
use App\Models\Vehiculo;
use App\Services\StorageService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class VehiculoFichaPdfController extends Controller
{
    /**
     * Genera la ficha técnica de un vehículo en PDF.
     */
    public function __invoke(Request $request, Vehiculo $vehiculo, StorageService $storage): Response
    {
        abort_unless($request->user()->can('view', $vehiculo), 403);

        // DomPDF + imágenes base64 + fuente DejaVu consume ~300-400MB.
        // set_time_limit por los fetch S3 secuenciales.
        ini_set('memory_limit', '512M');
        set_time_limit(60);

        try {
            $vehiculo->load('sucursal', 'conductor', 'creadoPor');

            // Flag de diagnóstico: ?sin_fotos=1 salta la descarga S3 + embed base64.
            // Útil para aislar si el 502/OOM viene de las imágenes o del render DomPDF.
            $sinFotos = $request->boolean('sin_fotos');

            if ($sinFotos) {
                $fotosBase64 = collect();
            } else {
                $fotos = FotoVehiculo::where('vehiculo_id', $vehiculo->id)
                    ->whereIn('categoria', ['frontal', 'lateral_der', 'lateral_izq', 'trasera'])
                    ->get()
                    ->groupBy('categoria')
                    ->map(fn ($grupo) => $grupo->first());

                $fotosBase64 = $fotos->map(fn (FotoVehiculo $f) => $this->leerComoBase64($storage, $f->thumbnail_key ?? $f->key));
            }

            $ultimoMantenimiento = Mantenimiento::where('vehiculo_id', $vehiculo->id)
                ->orderByDesc('fecha_servicio')
                ->first();

            $documentos = DocumentoVehicular::where('vehiculo_id', $vehiculo->id)
                ->whereNotNull('vencimiento')
                ->orderBy('tipo')
                ->get();

            $pdf = Pdf::loadView('pdf.vehiculo-ficha', [
                'vehiculo' => $vehiculo,
                'fotosBase64' => $fotosBase64,
                'ultimoMantenimiento' => $ultimoMantenimiento,
                'documentos' => $documentos,
                'generadoPor' => $request->user(),
                'generadoEn' => now(),
                'logoBase64' => $this->logoBase64(),
            ])->setPaper('a4', 'portrait');

            $nombre = 'ficha-'.str($vehiculo->placa)->slug().'-'.now()->format('Ymd').'.pdf';

            return $pdf->download($nombre);
        } catch (\Throwable $e) {
            report($e);

            abort(500, 'No se pudo generar la ficha: '.$e->getMessage());
        }
    }

    /**
     * Logo de Selcosi como data-URL base64. Prefiere el logo horizontal con texto;
     * cae al icono circular si el primero no existe.
     */
    private function logoBase64(): ?string
    {
        $candidatos = [
            public_path('images/logo-selcosi.png'),
            public_path('selcosilog.png'),
        ];

        foreach ($candidatos as $ruta) {
            if (is_file($ruta)) {
                $mime = mime_content_type($ruta) ?: 'image/png';

                return 'data:'.$mime.';base64,'.base64_encode(file_get_contents($ruta));
            }
        }

        return null;
    }

    /**
     * Lee un archivo del storage S3-compatible y lo devuelve como data-URL base64.
     * Devuelve null si falla (red, permisos) — la vista debe manejar null.
     */
    private function leerComoBase64(StorageService $storage, ?string $key): ?string
    {
        if (! $key) {
            return null;
        }

        try {
            $disk = Storage::disk($storage->disk());

            if (! $disk->exists($key)) {
                return null;
            }

            $contenido = $disk->get($key);
            $mime = $disk->mimeType($key) ?: 'image/jpeg';

            return 'data:'.$mime.';base64,'.base64_encode($contenido);
        } catch (\Throwable) {
            return null;
        }
    }
}
