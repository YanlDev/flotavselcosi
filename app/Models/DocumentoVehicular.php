<?php

namespace App\Models;

use Database\Factories\DocumentoVehicularFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class DocumentoVehicular extends Model
{
    /** @use HasFactory<DocumentoVehicularFactory> */
    use HasFactory;

    protected $table = 'documentos_vehiculares';

    protected $fillable = [
        'vehiculo_id', 'subido_por', 'tipo', 'nombre',
        'archivo_key', 'mime_type', 'tamano_bytes', 'vencimiento', 'observaciones',
    ];

    protected function casts(): array
    {
        return ['vencimiento' => 'date'];
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function subidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subido_por');
    }

    public function diasParaVencer(): ?int
    {
        if (! $this->vencimiento) {
            return null;
        }

        return (int) Carbon::now()->startOfDay()->diffInDays($this->vencimiento, false);
    }

    public function scopeProximosAVencer(Builder $query, int $dias = 30): Builder
    {
        return $query->whereBetween('vencimiento', [now()->startOfDay(), now()->addDays($dias)->endOfDay()]);
    }

    public function scopeVencidos(Builder $query): Builder
    {
        return $query->where('vencimiento', '<', now()->startOfDay());
    }
}
