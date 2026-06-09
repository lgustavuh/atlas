<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BibliotecaArea;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BibliotecaArea>
 */
class BibliotecaAreaFactory extends Factory
{
    protected $model = BibliotecaArea::class;

    public function definition(): array
    {
        $areas = [
            'Recursos Humanos', 'Compras', 'Engenharia', 'Jurídico',
            'Financeiro', 'Frota', 'Saúde Ocupacional', 'TI',
            'Manutenção', 'Almoxarifado', 'Qualidade', 'Segurança do Trabalho',
            'Diretoria', 'Logística', 'Comercial', 'Operações',
        ];

        return [
            'nome' => fake()->unique()->randomElement($areas),
            'descricao' => fake()->optional()->sentence(),
        ];
    }
}
