<?php

namespace Database\Factories;

use App\Models\Invitacion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Invitacion>
 */
class InvitacionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'token' => Str::random(64),
            'email' => fake()->unique()->safeEmail(),
            'rol' => 'visor',
            'sucursal_id' => null,
            'invitado_por' => User::factory(),
            'usado_en' => null,
            'expira_en' => now()->addDays(7),
        ];
    }

    /** Invitación ya usada. */
    public function usada(): static
    {
        return $this->state(['usado_en' => now()->subMinute()]);
    }

    /** Invitación expirada. */
    public function expirada(): static
    {
        return $this->state(['expira_en' => now()->subDay()]);
    }

    /** Para rol jefe_resguardo. */
    public function jefeResguardo(): static
    {
        return $this->state(['rol' => 'jefe_resguardo']);
    }

    /** Para rol admin. */
    public function admin(): static
    {
        return $this->state(['rol' => 'admin']);
    }
}
