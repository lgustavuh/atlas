<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Fornecedor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fornecedor>
 */
class FornecedorFactory extends Factory
{
    protected $model = Fornecedor::class;

    public function definition(): array
    {
        $faker = fake('pt_BR');
        $empresa = $faker->company();

        return [
            'tipo_pessoa' => 'juridica',
            'razao_social' => $empresa,
            'nome_fantasia' => $faker->companySuffix() . ' ' . $faker->word(),
            'cnpj_cpf' => $this->gerarCnpjValido(),
            'email' => $faker->unique()->companyEmail(),
            'celular' => $faker->cellPhoneNumber(),
            'homologado' => $faker->boolean(60),
            'avaliacao' => $faker->optional()->numberBetween(1, 5),
        ];
    }

    public function pessoaFisica(): static
    {
        return $this->state(fn () => [
            'tipo_pessoa' => 'fisica',
            'razao_social' => fake('pt_BR')->name(),
            'nome_fantasia' => null,
            'cnpj_cpf' => $this->gerarCpfValido(),
        ]);
    }

    public function homologado(): static
    {
        return $this->state(fn () => ['homologado' => true]);
    }

    /**
     * Gera CNPJ válido com dígitos verificadores corretos.
     */
    private function gerarCnpjValido(): string
    {
        $n = [];
        for ($i = 0; $i < 8; $i++) {
            $n[] = random_int(0, 9);
        }
        // Filial 0001
        array_push($n, 0, 0, 0, 1);

        $pesos1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $soma = 0;
        for ($i = 0; $i < 12; $i++) {
            $soma += $n[$i] * $pesos1[$i];
        }
        $resto = $soma % 11;
        $n[] = $resto < 2 ? 0 : 11 - $resto;

        $pesos2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $soma = 0;
        for ($i = 0; $i < 13; $i++) {
            $soma += $n[$i] * $pesos2[$i];
        }
        $resto = $soma % 11;
        $n[] = $resto < 2 ? 0 : 11 - $resto;

        return implode('', $n);
    }

    /**
     * Gera CPF válido.
     */
    private function gerarCpfValido(): string
    {
        $base = [];
        for ($i = 0; $i < 9; $i++) {
            $base[] = random_int(0, 9);
        }
        $soma = 0;
        for ($i = 0; $i < 9; $i++) {
            $soma += $base[$i] * (10 - $i);
        }
        $d1 = (($soma * 10) % 11) % 10;
        $base[] = $d1;
        $soma = 0;
        for ($i = 0; $i < 10; $i++) {
            $soma += $base[$i] * (11 - $i);
        }
        $d2 = (($soma * 10) % 11) % 10;
        $base[] = $d2;

        return implode('', $base);
    }
}
