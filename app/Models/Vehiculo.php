<?php

namespace App\Models;

use Database\Factories\VehiculoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehiculo extends Model
{
    /** @use HasFactory<VehiculoFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sucursal_id', 'creado_por', 'placa', 'tipo', 'marca', 'modelo', 'anio', 'color',
        'num_motor', 'num_chasis', 'vin', 'propietario', 'ruc_propietario',
        'estado', 'problema_activo', 'combustible', 'transmision', 'traccion',
        'km_actuales', 'capacidad_carga', 'conductor_nombre', 'conductor_tel',
        'fecha_adquisicion', 'tiene_gps', 'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha_adquisicion' => 'date',
            'km_actuales' => 'integer',
            'anio' => 'integer',
            'tiene_gps' => 'boolean',
        ];
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function conductor(): HasMany
    {
        return $this->hasMany(Conductor::class);
    }

    public function fotos(): HasMany
    {
        return $this->hasMany(FotoVehiculo::class);
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(DocumentoVehicular::class);
    }

    public function mantenimientos(): HasMany
    {
        return $this->hasMany(Mantenimiento::class);
    }

    public function registrosCombustible(): HasMany
    {
        return $this->hasMany(RegistroCombustible::class);
    }

    /** Aplica filtro de sucursal según el rol del usuario */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        if ($user->puedeVerTodo()) {
            return $query;
        }

        return $query->where('sucursal_id', $user->sucursal_id);
    }

    public function scopeSearch(Builder $query, string $termino): Builder
    {
        return $query->where(function (Builder $q) use ($termino) {
            $q->where('placa', 'like', "%{$termino}%")
                ->orWhere('marca', 'like', "%{$termino}%")
                ->orWhere('modelo', 'like', "%{$termino}%")
                ->orWhere('conductor_nombre', 'like', "%{$termino}%");
        });
    }

    public function scopeOperativos(Builder $query): Builder
    {
        return $query->where('estado', 'operativo');
    }
}
