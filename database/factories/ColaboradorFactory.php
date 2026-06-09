<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Colaborador;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Colaborador>
 */
class ColaboradorFactory extends Factory
{
    protected $model = Colaborador::class;

    public function definition(): array
    {
        $faker = fake('pt_BR');

        return [
            'nome' => $faker->name(),
            'cpf' => $this->gerarCpfValido(),
            'rg' => $faker->numerify('##.###.###'),
            'data_nascimento' => $faker->dateTimeBetween('-65 years', '-18 years'),
            'sexo' => $faker->randomElement(['M', 'F']),
            'estado_civil' => $faker->randomElement(['solteiro', 'casado', 'divorciado']),
            'nacionalidade' => 'Brasileira',
            'nome_pai' => $faker->name('male'),
            'nome_mae' => $faker->name('female'),
            'telefone_celular' => $faker->cellPhoneNumber(),
            'email' => $faker->unique()->safeEmail(),
            'matricula' => 'M' . $faker->unique()->numerify('#####'),
            'data_admissao' => $faker->dateTimeBetween('-10 years', 'now'),
            'regime_contratacao' => 'clt',
            'salario' => $faker->randomFloat(2, 1500, 15000),
            'jornada' => 'integral',
            'horario_entrada' => '08:00',
            'horario_saida' => '17:00',
            'doador_orgaos' => $faker->boolean(30),
            'pcd' => false,
        ];
    }

    public function pcd(): static
    {
        return $this->state(fn () => [
            'pcd' => true,
            'pcd_descricao' => fake()->randomElement([
                'Deficiência auditiva',
                'Mobilidade reduzida',
                'Deficiência visual parcial',
            ]),
        ]);
    }

    public function demitido(): static
    {
        return $this->state(fn () => [
            'data_demissao' => fake()->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    public function comCargo(int $cargoId): static
    {
        return $this->state(fn () => ['cargo_id' => $cargoId]);
    }

    /**
     * Gera um CPF válido (com dígitos verificadores corretos).
     * Necessário porque a validação real recusa CPFs inválidos.
     */
    private function gerarCpfValido(): string
    {
        $base = [];
        for ($i = 0; $i < 9; $i++) {
            $base[] = random_int(0, 9);
        }

        // Primeiro dígito verificador
        $soma = 0;
        for ($i = 0; $i < 9; $i++) {
            $soma += $base[$i] * (10 - $i);
        }
        $d1 = (($soma * 10) % 11) % 10;
        $base[] = $d1;

        // Segundo dígito verificador
        $soma = 0;
        for ($i = 0; $i < 10; $i++) {
            $soma += $base[$i] * (11 - $i);
        }
        $d2 = (($soma * 10) % 11) % 10;
        $base[] = $d2;

        return implode('', $base);
    }
}
