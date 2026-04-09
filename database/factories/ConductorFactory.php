<?php

namespace Database\Factories;

use App\Models\Conductor;
use App\Models\Sucursal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conductor>
 */
class ConductorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sucursal_id' => Sucursal::factory(),
            'nombre_completo' => fake()->name(),
            'dni' => fake()->unique()->numerify('########'),
            'telefono' => fake()->optional()->numerify('9########'),
            'email' => fake()->optional()->safeEmail(),
            'licencia_numero' => fake()->optional()->bothify('Q########'),
            'licencia_categoria' => fake()->optional()->randomElement(['A-I', 'A-IIa', 'A-IIb', 'B-IIb', 'C-IIa']),
            'licencia_vencimiento' => fake()->optional()->dateTimeBetween('+1 month', '+3 years'),
            'activo' => true,
        ];
    }
}
