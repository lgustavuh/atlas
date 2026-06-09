<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Cargo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cargo>
 */
class CargoFactory extends Factory
{
    protected $model = Cargo::class;

    public function definition(): array
    {
        $cargos = [
            'Analista de Sistemas', 'Desenvolvedor Backend', 'Desenvolvedor Frontend',
            'Engenheiro de Software', 'Gerente de TI', 'Assistente Administrativo',
            'Auxiliar de Produção', 'Operador de Máquinas', 'Supervisor de Produção',
            'Contador', 'Analista Financeiro', 'Comprador',
        ];

        $base = fake()->randomFloat(2, 2000, 8000);

        return [
            'nome' => fake()->randomElement($cargos) . ' ' . fake()->randomElement(['Jr', 'Pl', 'Sr']),
            'cbo' => fake()->numerify('######'),
            'descricao' => fake()->sentence(),
            'salario_minimo' => $base,
            'salario_maximo' => $base * 1.5,
        ];
    }
}
