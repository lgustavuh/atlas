<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BibliotecaDocumento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BibliotecaDocumento>
 */
class BibliotecaDocumentoFactory extends Factory
{
    protected $model = BibliotecaDocumento::class;

    public function definition(): array
    {
        $titulos = [
            'Manual do Servidor', 'Política de Compras', 'Modelo de Contrato',
            'Norma de Segurança do Trabalho', 'Guia de Atendimento',
            'Procedimento de Almoxarifado',
        ];

        return [
            'titulo' => fake()->randomElement($titulos) . ' v' . fake()->numberBetween(1, 5),
            'descricao' => fake()->paragraph(),
            'versao' => fake()->numberBetween(1, 5) . '.' . fake()->numberBetween(0, 9),
            'arquivo_path' => 'private/biblioteca/' . fake()->sha256() . '.pdf',
            'arquivo_nome_original' => fake()->word() . '.pdf',
            'arquivo_mime' => 'application/pdf',
            'arquivo_tamanho_bytes' => fake()->numberBetween(50000, 5000000),
            'arquivo_hash' => fake()->sha256(),
            'downloads_count' => fake()->numberBetween(0, 100),
        ];
    }
}
