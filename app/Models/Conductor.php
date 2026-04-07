<?php

namespace App\Models;

use Database\Factories\ConductorFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conductor extends Model
{
    /** @use HasFactory<ConductorFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'conductores';

    protected $fillable = [
        'sucursal_id', 'vehiculo_id', 'nombre_completo', 'dni', 'telefono', 'email',
        'foto_path', 'licencia_numero', 'licencia_categoria', 'licencia_vencimiento', 'activo',
    ];

    protected function casts(): array
    {
        return [
            'licencia_vencimiento' => 'date',
            'activo' => 'boolean',
        ];
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        if ($user->esAdmin()) {
            return $query;
        }

        return $query->where('sucursal_id', $user->sucursal_id);
    }
}
