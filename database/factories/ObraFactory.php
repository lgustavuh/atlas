<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Obra;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Obra>
 */
class ObraFactory extends Factory
{
    protected $model = Obra::class;

    public function definition(): array
    {
        static $seq = 0;
        $seq++;

        $tipos = [
            'Pavimentação', 'Reforma', 'Construção', 'Ampliação',
            'Drenagem', 'Iluminação Pública', 'Praça', 'Escola',
        ];
        $locais = ['Centro', 'Bairro Vila Nova', 'Distrito Industrial', 'Rua Principal', 'Av. Brasil'];

        return [
            'codigo' => 'OBR-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT) . '-' . now()->year,
            'nome' => fake()->randomElement($tipos) . ' - ' . fake()->randomElement($locais),
            'descricao' => fake()->paragraph(2),
            'endereco' => fake()->streetAddress(),
            'data_inicio' => fake()->dateTimeBetween('-6 months', 'now'),
            'data_termino_previsto' => fake()->dateTimeBetween('+1 month', '+1 year'),
            'orcamento' => fake()->randomFloat(2, 50000, 5000000),
            'status' => Obra::STATUS_PLANEJAMENTO,
        ];
    }

    public function emAndamento(): static
    {
        return $this->state(fn () => ['status' => Obra::STATUS_EM_ANDAMENTO]);
    }

    public function concluida(): static
    {
        return $this->state(fn () => [
            'status' => Obra::STATUS_CONCLUIDA,
            'data_termino_real' => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }

    public function atrasada(): static
    {
        return $this->state(fn () => [
            'status' => Obra::STATUS_EM_ANDAMENTO,
            'data_termino_previsto' => now()->subDays(30),
        ]);
    }
}
