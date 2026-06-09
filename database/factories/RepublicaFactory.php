<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Republica;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Republica>
 */
class RepublicaFactory extends Factory
{
    protected $model = Republica::class;

    public function definition(): array
    {
        static $seq = 0;
        $seq++;

        return [
            'nome' => 'República ' . fake()->randomElement(['Vila Nova', 'Centro', 'Jardim Florestal', 'Bela Vista']) . " #{$seq}",
            'endereco' => fake()->streetAddress(),
            'capacidade_total' => fake()->numberBetween(2, 8),
            'aluguel_mensal' => fake()->randomFloat(2, 800, 3000),
            'responsavel_externo_nome' => fake()->name(),
            'responsavel_externo_telefone' => fake()->numerify('(##) #####-####'),
            'ativa' => true,
        ];
    }

    public function inativa(): static
    {
        return $this->state(fn () => ['ativa' => false]);
    }
}
