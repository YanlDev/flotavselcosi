<?php

namespace App\Http\Controllers;

use App\Models\FotoVehiculo;
use App\Models\Vehiculo;
use App\Services\StorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FotoVehiculoController extends Controller
{
    /**
     * Sirve el thumbnail de una foto con headers de caché HTTP.
     * Si no existe thumbnail, usa el original como fallback.
     */
    public function thumbnail(Request $request, Vehiculo $vehiculo, FotoVehiculo $foto, StorageService $storage): StreamedResponse
    {
        abort_unless($request->user()->can('view', $vehiculo), 403);
        abort_unless($foto->vehiculo_id === $vehiculo->id, 404);

        $key = $foto->thumbnail_key ?? $foto->key;

        return $this->streamWithCache($request, $key, $foto, $storage);
    }

    /**
     * Sirve la foto original en resolución completa con headers de caché HTTP.
     */
    public function original(Request $request, Vehiculo $vehiculo, FotoVehiculo $foto, StorageService $storage): StreamedResponse
    {
        abort_unless($request->user()->can('view', $vehiculo), 403);
        abort_unless($foto->vehiculo_id === $vehiculo->id, 404);

        return $this->streamWithCache($request, $foto->key, $foto, $storage);
    }

    /**
     * Streams un archivo desde S3 con headers de caché HTTP (ETag, Cache-Control, Last-Modified).
     * Retorna 304 Not Modified si el cliente ya tiene la versión actual.
     */
    private function streamWithCache(Request $request, string $key, FotoVehiculo $foto, StorageService $storage): StreamedResponse
    {
        $disk = Storage::disk($storage->disk());
        $etag = '"'.md5($key).'"';
        $lastModified = $foto->updated_at;

        // 304 Not Modified — el navegador ya tiene esta versión
        if ($request->header('If-None-Match') === $etag) {
            return response('', 304)
                ->header('ETag', $etag)
                ->header('Cache-Control', 'private, max-age=604800');
        }

        $mimeType = $disk->mimeType($key) ?: 'image/jpeg';

        return response()->stream(
            function () use ($disk, $key) {
                $stream = $disk->readStream($key);
                fpassthru($stream);

                if (is_resource($stream)) {
                    fclose($stream);
                }
            },
            200,
            [
                'Content-Type' => $mimeType,
                'Cache-Control' => 'private, max-age=604800', // 7 días
                'ETag' => $etag,
                'Last-Modified' => $lastModified->toRfc7231String(),
            ]
        );
    }
}
