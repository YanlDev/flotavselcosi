<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vehiculo;

class VehiculoPolicy
{
    /** Todos los roles pueden ver el listado (el scope forUser filtra por sucursal) */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Se puede ver si es admin, o si el vehículo pertenece a su sucursal */
    public function view(User $user, Vehiculo $vehiculo): bool
    {
        if ($user->esAdmin()) {
            return true;
        }

        return $user->sucursal_id === $vehiculo->sucursal_id;
    }

    /** Solo admin crea vehículos */
    public function create(User $user): bool
    {
        return $user->esAdmin();
    }

    /** Solo admin edita vehículos */
    public function update(User $user, Vehiculo $vehiculo): bool
    {
        return $user->esAdmin();
    }

    /** Solo admin elimina (soft delete) */
    public function delete(User $user, Vehiculo $vehiculo): bool
    {
        return $user->esAdmin();
    }

    public function restore(User $user, Vehiculo $vehiculo): bool
    {
        return $user->esAdmin();
    }

    public function forceDelete(User $user, Vehiculo $vehiculo): bool
    {
        return $user->esAdmin();
    }
}
