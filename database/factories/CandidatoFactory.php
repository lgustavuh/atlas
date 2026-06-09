<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Candidato;
use App\Models\Vaga;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Candidato>
 */
class CandidatoFactory extends Factory
{
    protected $model = Candidato::class;

    public function definition(): array
    {
        return [
            'vaga_id' => Vaga::factory(),
            'nome' => fake()->name(),
            'cpf' => self::gerarCpfValido(),
            'email' => fake()->unique()->safeEmail(),
            'telefone' => fake()->numerify('(##) #####-####'),
            'experiencia' => fake()->paragraph(),
            'status' => Candidato::STATUS_INSCRITO,
            'pontuacao' => fake()->numberBetween(0, 100),
        ];
    }

    public function inscrito(): static
    {
        return $this->state(fn () => ['status' => Candidato::STATUS_INSCRITO]);
    }

    public function aprovado(): static
    {
        return $this->state(fn () => ['status' => Candidato::STATUS_APROVADO]);
    }

    public function contratado(): static
    {
        return $this->state(fn () => ['status' => Candidato::STATUS_CONTRATADO]);
    }

    /**
     * Gera CPF válido para testes (com dígitos verificadores corretos).
     */
    private static function gerarCpfValido(): string
    {
        // 9 dígitos aleatórios
        $cpf = '';
        for ($i = 0; $i < 9; $i++) {
            $cpf .= random_int(0, 9);
        }
        // Calcula dígito 1
        $soma = 0;
        for ($i = 0, $peso = 10; $i < 9; $i++, $peso--) {
            $soma += (int) $cpf[$i] * $peso;
        }
        $d1 = ($soma * 10) % 11;
        if ($d1 === 10) {
            $d1 = 0;
        }
        $cpf .= $d1;
        // Calcula dígito 2
        $soma = 0;
        for ($i = 0, $peso = 11; $i < 10; $i++, $peso--) {
            $soma += (int) $cpf[$i] * $peso;
        }
        $d2 = ($soma * 10) % 11;
        if ($d2 === 10) {
            $d2 = 0;
        }
        $cpf .= $d2;
        return $cpf;
    }
}
