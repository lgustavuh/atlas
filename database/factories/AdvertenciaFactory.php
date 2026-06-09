<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Advertencia;
use App\Models\Colaborador;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Advertencia>
 */
class AdvertenciaFactory extends Factory
{
    protected $model = Advertencia::class;

    public function definition(): array
    {
        $tipo = fake()->randomElement(['verbal', 'escrita', 'suspensao']);

        return [
            'colaborador_id' => Colaborador::factory(),
            'tipo' => $tipo,
            'data_ocorrencia' => fake()->dateTimeBetween('-1 year', '-1 day'),
            'data_aplicacao' => fake()->dateTimeBetween('-1 year', 'now'),
            'motivo' => fake()->sentence(),
            'descricao_ocorrencia' => fake()->paragraph(),
            'dias_suspensao' => $tipo === 'suspensao' ? fake()->numberBetween(1, 5) : null,
            'ciente_colaborador' => fake()->boolean(70),
        ];
    }

    public function verbal(): static
    {
        return $this->state(fn () => ['tipo' => 'verbal', 'dias_suspensao' => null]);
    }

    public function escrita(): static
    {
        return $this->state(fn () => ['tipo' => 'escrita', 'dias_suspensao' => null]);
    }

    public function suspensao(int $dias = 3): static
    {
        return $this->state(fn () => ['tipo' => 'suspensao', 'dias_suspensao' => $dias]);
    }
}
