<?php

namespace Database\Factories;

use App\Models\Mantenimiento;
use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mantenimiento>
 */
class MantenimientoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categorias = [
            'aceite_filtros', 'llantas', 'frenos', 'liquidos', 'bateria',
            'alineacion_balanceo', 'suspension', 'transmision',
            'electricidad', 'revision_general', 'otro',
        ];

        return [
            'vehiculo_id' => Vehiculo::factory(),
            'registrado_por' => User::factory(),
            'categoria' => fake()->randomElement($categorias),
            'tipo' => fake()->randomElement(['preventivo', 'correctivo']),
            'descripcion' => fake()->optional()->sentence(),
            'taller' => fake()->optional()->company(),
            'costo' => fake()->optional()->randomFloat(2, 50, 2000),
            'fecha_servicio' => fake()->dateTimeBetween('-2 years', 'now'),
            'km_servicio' => fake()->optional()->numberBetween(5000, 100000),
            'proximo_km' => null,
            'proxima_fecha' => null,
            'observaciones' => null,
        ];
    }
}
