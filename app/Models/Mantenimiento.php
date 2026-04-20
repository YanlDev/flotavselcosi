<?php

namespace App\Models;

use Database\Factories\MantenimientoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mantenimiento extends Model
{
    /** @use HasFactory<MantenimientoFactory> */
    use HasFactory;

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

    /**
     * Mantiene solo el registro más reciente por (vehiculo_id, categoria).
     * Usa MAX(id) como desempate — el auto-increment crece en orden temporal.
     */
    public function scopeUltimoPorCategoria(Builder $query): Builder
    {
        return $query->whereIn('id', function ($sub) {
            $sub->selectRaw('MAX(id)')
                ->from('mantenimientos')
                ->groupBy('vehiculo_id', 'categoria');
        });
    }
}
