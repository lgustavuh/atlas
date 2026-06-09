<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Atestado;
use App\Models\Colaborador;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Atestado>
 */
class AtestadoFactory extends Factory
{
    protected $model = Atestado::class;

    public function definition(): array
    {
        $inicio = fake()->dateTimeBetween('-3 months', 'now');
        $dias = fake()->numberBetween(1, 5);
        $fim = (clone $inicio)->modify("+{$dias} days");

        return [
            'colaborador_id' => Colaborador::factory(),
            'tipo' => fake()->randomElement(['medico', 'odontologico', 'acompanhante']),
            'data_inicio' => $inicio,
            'data_fim' => $fim,
            'dias_afastamento' => $dias + 1,
            'cid' => fake()->randomElement(['J11', 'M54.5', 'F32.0', 'R10.4', null]),
            'medico_nome' => 'Dr(a). ' . fake()->name(),
            'medico_crm' => fake()->numerify('######'),
            'medico_crm_uf' => fake()->randomElement(['MG', 'SP', 'RJ']),
            'status' => Atestado::STATUS_PENDENTE,
            // Arquivo é obrigatório no schema. Em testes, usar um placeholder.
            'arquivo_path' => 'private/atestados/fake-test.pdf',
            'arquivo_nome_original' => 'atestado.pdf',
            'arquivo_mime' => 'application/pdf',
            'arquivo_tamanho_bytes' => 102400,
            'arquivo_hash' => bin2hex(random_bytes(32)),
        ];
    }

    public function pendente(): static
    {
        return $this->state(fn () => ['status' => Atestado::STATUS_PENDENTE]);
    }

    public function aprovado(): static
    {
        return $this->state(fn () => [
            'status' => Atestado::STATUS_APROVADO,
            'data_aprovacao' => now(),
        ]);
    }

    public function rejeitado(string $motivo = 'Documento ilegível'): static
    {
        return $this->state(fn () => [
            'status' => Atestado::STATUS_REJEITADO,
            'data_aprovacao' => now(),
            'motivo_rejeicao' => $motivo,
        ]);
    }
}
