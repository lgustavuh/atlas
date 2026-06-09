<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\GrupoMaterial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GrupoMaterial>
 */
class GrupoMaterialFactory extends Factory
{
    protected $model = GrupoMaterial::class;

    public function definition(): array
    {
        $grupos = [
            'Material de Construção', 'Material Elétrico', 'Material Hidráulico',
            'Ferramentas', 'EPI', 'Material de Escritório', 'Limpeza',
            'Pintura', 'Madeira', 'Metais',
        ];

        return [
            'nome' => fake()->unique()->randomElement($grupos),
            'codigo' => strtoupper(fake()->unique()->lexify('???')),
            'descricao' => fake()->optional()->sentence(),
        ];
    }
}
