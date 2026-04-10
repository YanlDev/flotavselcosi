<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImageService
{
    private ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver);
    }

    /**
     * Genera un thumbnail WebP a partir de un archivo subido y lo almacena en S3.
     *
     * @param  UploadedFile  $file  Archivo original (puede ser TemporaryUploadedFile de Livewire)
     * @param  string  $folder  Carpeta destino en S3 (ej: "vehiculos/5/fotos")
     * @param  int  $size  Tamaño máximo del thumbnail (cover crop)
     * @param  int  $quality  Calidad WebP (0-100)
     * @return string Key del thumbnail en S3
     */
    public function generateThumbnail(
        UploadedFile $file,
        string $folder,
        int $size = 400,
        int $quality = 80
    ): string {
        // Leer imagen con Intervention
        if ($file instanceof TemporaryUploadedFile) {
            $stream = $file->readStream();
            $contents = stream_get_contents($stream);

            if (is_resource($stream)) {
                fclose($stream);
            }

            $image = $this->manager->read($contents);
        } else {
            $image = $this->manager->read($file->get());
        }

        // Redimensionar con cover crop (rellena el cuadrado sin distorsionar)
        $image->cover($size, $size);

        // Codificar a WebP
        $encoded = $image->toWebp($quality)->toString();

        // Generar key y subir a S3
        $key = $folder.'/'.Str::uuid().'_thumb.webp';
        Storage::disk(app(StorageService::class)->disk())->put($key, $encoded, 'private');

        return $key;
    }
}
