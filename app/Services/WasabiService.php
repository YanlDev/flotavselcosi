<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WasabiService
{
    private const DISK = 'wasabi';

    /**
     * Sube un archivo a Wasabi y retorna la key almacenada.
     */
    public function upload(UploadedFile $file, string $folder): string
    {
        $key = $folder.'/'.Str::uuid().'.'.$file->getClientOriginalExtension();

        Storage::disk(self::DISK)->put($key, $file->get(), 'private');

        return $key;
    }

    /**
     * Genera una URL temporal firmada para descarga (default: 60 minutos).
     */
    public function temporaryUrl(string $key, int $minutes = 60): string
    {
        return Storage::disk(self::DISK)->temporaryUrl($key, now()->addMinutes($minutes));
    }

    /**
     * Elimina un archivo de Wasabi.
     */
    public function delete(string $key): void
    {
        Storage::disk(self::DISK)->delete($key);
    }
}
