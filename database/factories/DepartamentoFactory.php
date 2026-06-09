<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Departamento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Departamento>
 */
class DepartamentoFactory extends Factory
{
    protected $model = Departamento::class;

    public function definition(): array
    {
        $deps = [
            ['nome' => 'Tecnologia da Informação', 'sigla' => 'TI'],
            ['nome' => 'Recursos Humanos', 'sigla' => 'RH'],
            ['nome' => 'Financeiro', 'sigla' => 'FIN'],
            ['nome' => 'Comercial', 'sigla' => 'COM'],
            ['nome' => 'Produção', 'sigla' => 'PROD'],
            ['nome' => 'Administrativo', 'sigla' => 'ADM'],
            ['nome' => 'Logística', 'sigla' => 'LOG'],
            ['nome' => 'Marketing', 'sigla' => 'MKT'],
        ];

        $escolhido = fake()->randomElement($deps);

        return [
            'nome' => $escolhido['nome'] . ' ' . fake()->unique()->numerify('##'),
            'sigla' => $escolhido['sigla'],
            'descricao' => fake()->sentence(),
        ];
    }
}
