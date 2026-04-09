<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FotoVehiculo extends Model
{
    protected $table = 'fotos_vehiculos';

    protected $fillable = ['vehiculo_id', 'subido_por', 'key', 'categoria', 'descripcion'];

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function subidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subido_por');
    }
}
