<?php

namespace App\Models;

use App\Enums\EstadoEquipamiento;
use App\Enums\ItemEquipamiento;
use Database\Factories\EquipamientoVehicularFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class EquipamientoVehicular extends Model
{
    /** @use HasFactory<EquipamientoVehicularFactory> */
    use HasFactory;

    protected $table = 'equipamiento_vehiculos';

    protected $fillable = ['vehiculo_id', 'item', 'estado', 'vencimiento', 'observaciones'];

    protected function casts(): array
    {
        return [
            'item' => ItemEquipamiento::class,
            'estado' => EstadoEquipamiento::class,
            'vencimiento' => 'date',
        ];
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function diasParaVencer(): ?int
    {
        if (! $this->vencimiento) {
            return null;
        }

        return (int) Carbon::now()->startOfDay()->diffInDays($this->vencimiento, false);
    }

    public function scopeConVencimiento(Builder $query): Builder
    {
        return $query->whereNotNull('vencimiento')
            ->where('vencimiento', '<=', now()->addDays(30)->endOfDay());
    }

    public function scopeEnAlerta(Builder $query): Builder
    {
        return $query->whereIn('estado', [
            EstadoEquipamiento::No->value,
            EstadoEquipamiento::Renovar->value,
            EstadoEquipamiento::Reparar->value,
        ]);
    }
}
