<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'sucursal_id', 'activo'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function vehiculosCreados(): HasMany
    {
        return $this->hasMany(Vehiculo::class, 'creado_por');
    }

    public function invitacionesEnviadas(): HasMany
    {
        return $this->hasMany(Invitacion::class, 'invitado_por');
    }

    public function registrosCombustible(): HasMany
    {
        return $this->hasMany(RegistroCombustible::class, 'enviado_por');
    }

    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function esAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function esJefeResguardo(): bool
    {
        return $this->hasRole('jefe_resguardo');
    }

    public function esVisor(): bool
    {
        return $this->hasRole('visor');
    }

    /** Puede ver datos de todas las sucursales (sin restricción de scope). */
    public function puedeVerTodo(): bool
    {
        return $this->esAdmin() || $this->esVisor();
    }
}
