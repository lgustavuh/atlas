<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Veiculo;
use App\Models\VeiculoManutencao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VeiculoManutencao>
 */
class VeiculoManutencaoFactory extends Factory
{
    protected $model = VeiculoManutencao::class;

    public function definition(): array
    {
        return [
            'veiculo_id' => Veiculo::factory(),
            'tipo' => fake()->randomElement(array_keys(VeiculoManutencao::tiposComLabel())),
            'data_manutencao' => fake()->dateTimeBetween('-1 year', 'now'),
            'km_no_momento' => fake()->numberBetween(10000, 200000),
            'descricao' => fake()->sentence(8),
            'servicos_realizados' => fake()->paragraph(2),
            'valor' => fake()->randomFloat(2, 50, 5000),
            'nota_fiscal' => (string) fake()->numerify('NF#######'),
        ];
    }

    public function preventiva(): static
    {
        return $this->state(fn () => ['tipo' => VeiculoManutencao::TIPO_PREVENTIVA]);
    }

    public function corretiva(): static
    {
        return $this->state(fn () => ['tipo' => VeiculoManutencao::TIPO_CORRETIVA]);
    }
}
