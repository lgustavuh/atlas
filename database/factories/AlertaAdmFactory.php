<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AlertaAdm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AlertaAdm>
 */
class AlertaAdmFactory extends Factory
{
    protected $model = AlertaAdm::class;

    public function definition(): array
    {
        return [
            'titulo' => fake()->sentence(5),
            'mensagem' => fake()->paragraph(2),
            'prioridade' => fake()->randomElement(['baixa', 'normal', 'alta', 'critica']),
            'ativo' => true,
            'data_inicio' => null,
            'data_fim' => null,
        ];
    }

    public function critico(): static
    {
        return $this->state(fn () => ['prioridade' => AlertaAdm::PRIORIDADE_CRITICA]);
    }

    public function inativo(): static
    {
        return $this->state(fn () => ['ativo' => false]);
    }

    public function expirado(): static
    {
        return $this->state(fn () => [
            'ativo' => true,
            'data_fim' => now()->subDays(10),
        ]);
    }

    public function futuro(): static
    {
        return $this->state(fn () => [
            'ativo' => true,
            'data_inicio' => now()->addDays(10),
        ]);
    }
}
