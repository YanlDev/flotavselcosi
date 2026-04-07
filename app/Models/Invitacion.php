<?php

namespace App\Models;

use Database\Factories\InvitacionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Invitacion extends Model
{
    /** @use HasFactory<InvitacionFactory> */
    use HasFactory;

    protected $table = 'invitaciones';

    protected $fillable = [
        'token', 'email', 'rol', 'sucursal_id', 'invitado_por', 'usado_en', 'expira_en',
    ];

    protected function casts(): array
    {
        return [
            'usado_en' => 'datetime',
            'expira_en' => 'datetime',
        ];
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function invitadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invitado_por');
    }

    public function estaUsada(): bool
    {
        return $this->usado_en !== null;
    }

    public function estaExpirada(): bool
    {
        return Carbon::now()->isAfter($this->expira_en);
    }

    public function estaActiva(): bool
    {
        return ! $this->estaUsada() && ! $this->estaExpirada();
    }

    public function getEstadoAttribute(): string
    {
        if ($this->estaUsada()) {
            return 'usado';
        }
        if ($this->estaExpirada()) {
            return 'expirado';
        }

        return 'activo';
    }

    public function scopeActivas(Builder $query): Builder
    {
        return $query->whereNull('usado_en')->where('expira_en', '>', now());
    }
}
