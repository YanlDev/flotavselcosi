<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistroCombustible extends Model
{
    protected $table = 'registros_combustible';

    protected $fillable = [
        'vehiculo_id', 'sucursal_id', 'enviado_por', 'foto_factura_key', 'foto_odometro_key',
        'observaciones_envio', 'estado', 'revisado_por', 'revisado_en', 'fecha_carga',
        'km_al_cargar', 'galones', 'precio_galon', 'monto_total', 'tipo_combustible',
        'proveedor', 'numero_voucher', 'observaciones_revision',
    ];

    protected function casts(): array
    {
        return [
            'revisado_en' => 'datetime',
            'fecha_carga' => 'date',
            'galones' => 'decimal:3',
            'precio_galon' => 'decimal:3',
            'monto_total' => 'decimal:2',
        ];
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function enviadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enviado_por');
    }

    public function revisadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisado_por');
    }

    public function scopePendientes(Builder $query): Builder
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopeAprobados(Builder $query): Builder
    {
        return $query->where('estado', 'aprobado');
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        if ($user->esAdmin()) {
            return $query;
        }

        return $query->where('sucursal_id', $user->sucursal_id);
    }
}
