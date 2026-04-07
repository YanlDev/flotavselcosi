<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Models\Invitacion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user via invitation token.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'token' => ['required', 'string'],
            'password' => $this->passwordRules(),
        ])->validate();

        $invitacion = Invitacion::where('token', $input['token'])->first();

        if (! $invitacion || ! $invitacion->estaActiva()) {
            throw ValidationException::withMessages([
                'token' => __('La invitación no es válida o ha expirado.'),
            ]);
        }

        return DB::transaction(function () use ($input, $invitacion): User {
            $user = User::create([
                'name' => $input['name'],
                'email' => $invitacion->email,
                'password' => $input['password'],
                'sucursal_id' => $invitacion->sucursal_id,
                'activo' => true,
            ]);

            $user->assignRole($invitacion->rol);

            $invitacion->update(['usado_en' => now()]);

            return $user;
        });
    }
}
