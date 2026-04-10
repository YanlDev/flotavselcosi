<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class StorageService
{
    /**
     * Sube un archivo al disco cloud y retorna la key almacenada.
     *
     * Soporta TemporaryUploadedFile de Livewire (local o S3) y
     * UploadedFile estándar (tests, API).
     */
    public function upload(UploadedFile $file, string $folder): string
    {
        $ext = $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin';
        $key = $folder.'/'.Str::uuid().'.'.$ext;

        if ($file instanceof TemporaryUploadedFile) {
            // Livewire ya sabe leer del disco correcto (S3, local, etc.)
            $stream = $file->readStream();
            Storage::disk($this->disk())->put($key, $stream, 'private');

            if (is_resource($stream)) {
                fclose($stream);
            }
        } else {
            // UploadedFile regular (tests, API)
            Storage::disk($this->disk())->put($key, $file->get(), 'private');
        }

        return $key;
    }

    /**
     * Genera una URL temporal firmada para descarga (default: 60 minutos).
     */
    public function temporaryUrl(string $key, int $minutes = 60): string
    {
        return Storage::disk($this->disk())->temporaryUrl($key, now()->addMinutes($minutes));
    }

    /**
     * Elimina un archivo del disco cloud.
     */
    public function delete(string $key): void
    {
        Storage::disk($this->disk())->delete($key);
    }

    /**
     * Nombre del disco S3-compatible configurado.
     */
    public function disk(): string
    {
        return config('filesystems.cloud', 'cloud');
    }
}
