<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Colaborador;
use App\Models\TransporteHospedagem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransporteHospedagem>
 */
class TransporteHospedagemFactory extends Factory
{
    protected $model = TransporteHospedagem::class;

    public function definition(): array
    {
        return [
            'tipo' => TransporteHospedagem::TIPO_TRANSPORTE,
            'colaborador_id' => Colaborador::factory(),
            'data_inicio' => fake()->dateTimeBetween('-1 month', '+1 month'),
            'data_fim' => fake()->optional(0.7)->dateTimeBetween('+1 day', '+2 months'),
            'origem' => fake()->city() . ' - ' . fake()->stateAbbr(),
            'destino' => fake()->city() . ' - ' . fake()->stateAbbr(),
            'meio_transporte' => fake()->randomElement(['onibus', 'aviao', 'carro_empresa', 'van']),
            'valor' => fake()->randomFloat(2, 100, 3000),
        ];
    }

    public function hospedagem(): static
    {
        return $this->state(fn () => [
            'tipo' => TransporteHospedagem::TIPO_HOSPEDAGEM,
            'origem' => null,
            'destino' => null,
            'meio_transporte' => null,
            'hospedagem_local' => 'Hotel ' . fake()->lastName(),
            'hospedagem_endereco' => fake()->streetAddress(),
        ]);
    }

    public function ambos(): static
    {
        return $this->state(fn () => [
            'tipo' => TransporteHospedagem::TIPO_AMBOS,
            'hospedagem_local' => 'Hotel ' . fake()->lastName(),
            'hospedagem_endereco' => fake()->streetAddress(),
        ]);
    }

    public function emAndamento(): static
    {
        return $this->state(fn () => [
            'data_inicio' => now()->subDays(5),
            'data_fim' => now()->addDays(5),
        ]);
    }

    public function futuro(): static
    {
        return $this->state(fn () => [
            'data_inicio' => now()->addDays(10),
            'data_fim' => now()->addDays(15),
        ]);
    }
}
