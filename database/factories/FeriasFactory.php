<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Colaborador;
use App\Models\Ferias;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ferias>
 */
class FeriasFactory extends Factory
{
    protected $model = Ferias::class;

    public function definition(): array
    {
        $aquisitivoInicio = fake()->dateTimeBetween('-2 years', '-13 months');
        $aquisitivoFim = (clone $aquisitivoInicio)->modify('+1 year -1 day');

        $gozoInicio = fake()->dateTimeBetween($aquisitivoFim, '+3 months');
        $diasGozo = fake()->randomElement([15, 20, 30]);
        $gozoFim = (clone $gozoInicio)->modify("+{$diasGozo} days -1 day");

        return [
            'colaborador_id' => Colaborador::factory(),
            'periodo_aquisitivo_inicio' => $aquisitivoInicio,
            'periodo_aquisitivo_fim' => $aquisitivoFim,
            'data_inicio_gozo' => $gozoInicio,
            'data_fim_gozo' => $gozoFim,
            'dias_gozo' => $diasGozo,
            'abono_pecuniario' => $diasGozo === 20,
            'dias_abono' => $diasGozo === 20 ? 10 : 0,
            'adiantar_13_salario' => fake()->boolean(30),
            'status' => Ferias::STATUS_PROGRAMADA,
        ];
    }

    public function programada(): static
    {
        return $this->state(fn () => ['status' => Ferias::STATUS_PROGRAMADA]);
    }

    public function aprovada(): static
    {
        return $this->state(fn () => [
            'status' => Ferias::STATUS_APROVADA,
            'data_aprovacao' => now(),
        ]);
    }

    public function emGozo(): static
    {
        return $this->state(fn () => [
            'status' => Ferias::STATUS_EM_GOZO,
            'data_aprovacao' => now()->subDays(10),
            'data_inicio_gozo' => now()->subDays(3),
            'data_fim_gozo' => now()->addDays(27),
        ]);
    }

    public function concluida(): static
    {
        return $this->state(fn () => [
            'status' => Ferias::STATUS_CONCLUIDA,
            'data_aprovacao' => now()->subDays(60),
            'data_inicio_gozo' => now()->subDays(45),
            'data_fim_gozo' => now()->subDays(15),
        ]);
    }
}
