<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mantenimiento extends Model
{
    protected $fillable = [
        'vehiculo_id', 'registrado_por', 'categoria', 'tipo', 'descripcion',
        'taller', 'costo', 'fecha_servicio', 'km_servicio', 'proximo_km',
        'proxima_fecha', 'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha_servicio' => 'date',
            'proxima_fecha' => 'date',
            'costo' => 'decimal:2',
        ];
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
