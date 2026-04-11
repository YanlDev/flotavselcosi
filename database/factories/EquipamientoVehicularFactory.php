<?php

namespace Database\Factories;

use App\Enums\EstadoEquipamiento;
use App\Enums\ItemEquipamiento;
use App\Models\EquipamientoVehicular;
use App\Models\Vehiculo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EquipamientoVehicular>
 */
class EquipamientoVehicularFactory extends Factory
{
    public function definition(): array
    {
        $item = fake()->randomElement(ItemEquipamiento::cases());

        return [
            'vehiculo_id' => Vehiculo::factory(),
            'item' => $item,
            'estado' => fake()->randomElement(EstadoEquipamiento::cases()),
            'vencimiento' => $item->tieneVencimiento()
                ? fake()->dateTimeBetween('+1 month', '+2 years')
                : null,
            'observaciones' => null,
        ];
    }

    public function extintor(): static
    {
        return $this->state([
            'item' => ItemEquipamiento::Extintor,
            'estado' => EstadoEquipamiento::Si,
            'vencimiento' => now()->addMonths(fake()->numberBetween(1, 24)),
        ]);
    }

    public function extintorPorVencer(int $dias = 15): static
    {
        return $this->state([
            'item' => ItemEquipamiento::Extintor,
            'estado' => EstadoEquipamiento::Renovar,
            'vencimiento' => now()->addDays($dias),
        ]);
    }

    public function noAplica(): static
    {
        return $this->state(['estado' => EstadoEquipamiento::NoAplica]);
    }
}
