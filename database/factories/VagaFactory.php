<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Vaga;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vaga>
 */
class VagaFactory extends Factory
{
    protected $model = Vaga::class;

    public function definition(): array
    {
        $titulos = [
            'Auxiliar Administrativo', 'Engenheiro Civil', 'Motorista de Caminhão',
            'Pedreiro', 'Operador de Máquina', 'Técnico em Segurança do Trabalho',
        ];

        return [
            'titulo' => fake()->randomElement($titulos),
            'descricao' => fake()->paragraph(3),
            'requisitos' => fake()->paragraph(2),
            'beneficios' => 'Vale-refeição, vale-transporte, plano de saúde',
            'salario_de' => fake()->randomFloat(2, 1500, 3000),
            'salario_ate' => fake()->randomFloat(2, 3500, 6000),
            'salario_publicar' => true,
            'quantidade_vagas' => fake()->numberBetween(1, 5),
            'status' => Vaga::STATUS_RASCUNHO,
        ];
    }

    public function aberta(): static
    {
        return $this->state(fn () => [
            'status' => Vaga::STATUS_ABERTA,
            'data_abertura' => now(),
        ]);
    }

    public function preenchida(): static
    {
        return $this->state(fn () => ['status' => Vaga::STATUS_PREENCHIDA]);
    }

    public function expirada(): static
    {
        return $this->state(fn () => [
            'status' => Vaga::STATUS_ABERTA,
            'data_fechamento' => now()->subDays(7),
        ]);
    }
}
