<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Veiculo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Veiculo>
 */
class VeiculoFactory extends Factory
{
    protected $model = Veiculo::class;

    public function definition(): array
    {
        $marcas = [
            'Volkswagen' => ['Gol', 'Polo', 'Saveiro', 'Amarok', 'Constellation'],
            'Fiat' => ['Strada', 'Toro', 'Uno', 'Ducato'],
            'Chevrolet' => ['Onix', 'S10', 'Tracker'],
            'Ford' => ['Ka', 'Ranger', 'Cargo'],
            'Toyota' => ['Hilux', 'Corolla', 'Yaris'],
        ];
        $marca = fake()->randomElement(array_keys($marcas));
        $modelo = fake()->randomElement($marcas[$marca]);
        $anoFab = fake()->numberBetween(2010, (int) now()->year);

        return [
            'placa' => self::gerarPlacaMercosul(),
            'renavam' => (string) fake()->numerify('###########'),
            'chassi' => strtoupper(fake()->regexify('[A-HJ-NPR-Z0-9]{17}')),
            'marca' => $marca,
            'modelo' => $modelo,
            'ano_fabricacao' => $anoFab,
            'ano_modelo' => $anoFab + fake()->numberBetween(0, 1),
            'cor' => fake()->randomElement(['Branco', 'Prata', 'Preto', 'Cinza', 'Vermelho', 'Azul']),
            'combustivel' => fake()->randomElement(['flex', 'diesel', 'gasolina']),
            'categoria' => fake()->randomElement(['passeio', 'utilitario', 'caminhao']),
            'km_atual' => fake()->numberBetween(0, 200000),
            'data_aquisicao' => fake()->dateTimeBetween('-10 years', 'now'),
            'valor_aquisicao' => fake()->randomFloat(2, 30000, 250000),
            'status' => 'disponivel',
            'licenciamento_vencimento' => fake()->dateTimeBetween('+1 month', '+1 year'),
            'seguro_vencimento' => fake()->dateTimeBetween('+1 month', '+1 year'),
        ];
    }

    public function emManutencao(): static
    {
        return $this->state(fn () => ['status' => 'em_manutencao']);
    }

    public function inativo(): static
    {
        return $this->state(fn () => ['status' => 'inativo']);
    }

    public function licenciamentoVencido(): static
    {
        return $this->state(fn () => ['licenciamento_vencimento' => now()->subDays(10)]);
    }

    /**
     * Gera placa padrão Mercosul válida (AAA1A23).
     */
    private static function gerarPlacaMercosul(): string
    {
        $letras = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $digitos = '0123456789';

        return $letras[random_int(0, 25)] . $letras[random_int(0, 25)] . $letras[random_int(0, 25)]
             . $digitos[random_int(0, 9)]
             . $letras[random_int(0, 25)]
             . $digitos[random_int(0, 9)] . $digitos[random_int(0, 9)];
    }
}
