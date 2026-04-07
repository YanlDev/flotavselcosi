<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActividadLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'accion', 'entidad_tipo', 'entidad_id', 'detalle', 'ip'];

    protected function casts(): array
    {
        return [
            'detalle' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function registrar(string $accion, Model $entidad, ?array $detalle = null): void
    {
        if (! auth()->check()) {
            return;
        }

        static::create([
            'user_id' => auth()->id(),
            'accion' => $accion,
            'entidad_tipo' => get_class($entidad),
            'entidad_id' => $entidad->getKey(),
            'detalle' => $detalle,
            'ip' => request()->ip(),
        ]);
    }
}
