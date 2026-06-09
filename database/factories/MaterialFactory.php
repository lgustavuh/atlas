<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Material;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Material>
 */
class MaterialFactory extends Factory
{
    protected $model = Material::class;

    public function definition(): array
    {
        $materiais = [
            'Cimento CP-II 50kg', 'Vergalhão 10mm', 'Tijolo cerâmico',
            'Fio elétrico 2,5mm', 'Tubo PVC 100mm', 'Parafuso sextavado',
            'Luva de raspa', 'Capacete de segurança', 'Tinta acrílica 18L',
            'Areia média m³', 'Brita 1 m³', 'Cal hidratada 20kg',
        ];

        $atual = fake()->randomFloat(2, 0, 500);
        $minimo = fake()->randomFloat(2, 10, 100);

        return [
            'codigo' => strtoupper(fake()->unique()->bothify('MAT-####')),
            'nome' => fake()->randomElement($materiais) . ' ' . fake()->randomNumber(3),
            'descricao' => fake()->optional()->sentence(),
            'unidade_medida' => fake()->randomElement(['UN', 'KG', 'M', 'L', 'CX', 'M3']),
            'estoque_atual' => $atual,
            'estoque_minimo' => $minimo,
            'estoque_maximo' => $minimo * 10,
            'preco_referencia' => fake()->randomFloat(2, 1, 1000),
        ];
    }

    public function estoqueBaixo(): static
    {
        return $this->state(fn () => [
            'estoque_atual' => 5,
            'estoque_minimo' => 50,
        ]);
    }
}
