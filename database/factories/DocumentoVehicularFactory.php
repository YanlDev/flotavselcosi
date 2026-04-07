<?php

namespace Database\Factories;

use App\Models\DocumentoVehicular;
use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentoVehicular>
 */
class DocumentoVehicularFactory extends Factory
{
    public function definition(): array
    {
        $tipos = ['soat', 'revision_tecnica', 'tarjeta_propiedad', 'otro'];
        $tipo = fake()->randomElement($tipos);

        return [
            'vehiculo_id' => Vehiculo::factory(),
            'subido_por' => User::factory(),
            'tipo' => $tipo,
            'nombre' => match ($tipo) {
                'soat' => 'SOAT '.fake()->year(),
                'revision_tecnica' => 'Revisión técnica '.fake()->year(),
                'tarjeta_propiedad' => 'Tarjeta de propiedad',
                default => fake()->words(3, true),
            },
            'archivo_key' => 'vehiculos/'.fake()->numberBetween(1, 100).'/documentos/'.fake()->uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'tamano_bytes' => fake()->numberBetween(50000, 5000000),
            'vencimiento' => in_array($tipo, ['soat', 'revision_tecnica'])
                ? fake()->dateTimeBetween('+1 day', '+2 years')
                : null,
            'observaciones' => null,
        ];
    }

    public function vencido(): static
    {
        return $this->state([
            'tipo' => 'soat',
            'nombre' => 'SOAT vencido',
            'vencimiento' => fake()->dateTimeBetween('-1 year', '-1 day'),
        ]);
    }

    public function proximoAVencer(int $dias = 15): static
    {
        return $this->state([
            'tipo' => 'soat',
            'nombre' => 'SOAT próximo a vencer',
            'vencimiento' => now()->addDays($dias),
        ]);
    }
}
