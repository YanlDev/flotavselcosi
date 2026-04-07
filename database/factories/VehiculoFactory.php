<?php

namespace Database\Factories;

use App\Models\Sucursal;
use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehiculo>
 */
class VehiculoFactory extends Factory
{
    public function definition(): array
    {
        $tipos = ['moto', 'auto', 'camioneta', 'minivan', 'furgon', 'bus', 'vehiculo_pesado'];
        $combustibles = ['gasolina', 'diesel', 'glp', 'gnv', 'electrico', 'hibrido'];
        $estados = ['operativo', 'parcialmente', 'fuera_de_servicio'];
        $marcas = ['Toyota', 'Hyundai', 'Nissan', 'Chevrolet', 'Ford', 'Volkswagen', 'Honda'];

        return [
            'sucursal_id' => Sucursal::factory(),
            'creado_por' => User::factory(),
            'placa' => strtoupper(fake()->bothify('???-###')),
            'tipo' => fake()->randomElement($tipos),
            'marca' => fake()->randomElement($marcas),
            'modelo' => fake()->word(),
            'anio' => fake()->numberBetween(2005, 2024),
            'color' => fake()->colorName(),
            'estado' => 'operativo',
            'combustible' => fake()->randomElement($combustibles),
            'transmision' => fake()->randomElement(['manual', 'automatico']),
            'traccion' => fake()->randomElement(['4x2', '4x4']),
            'km_actuales' => fake()->numberBetween(1000, 150000),
        ];
    }

    public function fueraDeServicio(): static
    {
        return $this->state([
            'estado' => 'fuera_de_servicio',
            'problema_activo' => 'Motor averiado',
        ]);
    }

    public function parcialmente(): static
    {
        return $this->state([
            'estado' => 'parcialmente',
            'problema_activo' => 'Frenos en revisión',
        ]);
    }
}
