<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Colaborador;
use App\Models\Fornecedor;
use App\Models\PedidoCompra;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PedidoCompra>
 */
class PedidoCompraFactory extends Factory
{
    protected $model = PedidoCompra::class;

    public function definition(): array
    {
        static $seq = 0;
        $seq++;

        return [
            'numero' => now()->year . '/' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
            'fornecedor_id' => Fornecedor::factory(),
            'solicitante_id' => Colaborador::factory(),
            'data_pedido' => now()->subDays(fake()->numberBetween(0, 30)),
            'data_entrega_prevista' => now()->addDays(fake()->numberBetween(5, 30)),
            'status' => PedidoCompra::STATUS_RASCUNHO,
            'valor_total' => 0,
            'valor_desconto' => 0,
            'valor_frete' => 0,
            'valor_final' => 0,
            'parcelas' => 1,
        ];
    }

    public function aguardandoLiberacao(): static
    {
        return $this->state(fn () => ['status' => PedidoCompra::STATUS_AGUARDANDO_LIBERACAO]);
    }

    public function aguardandoAprovacao(): static
    {
        return $this->state(fn () => ['status' => PedidoCompra::STATUS_AGUARDANDO_APROVACAO]);
    }

    public function aprovado(): static
    {
        return $this->state(fn () => ['status' => PedidoCompra::STATUS_APROVADO]);
    }

    public function enviado(): static
    {
        return $this->state(fn () => ['status' => PedidoCompra::STATUS_ENVIADO]);
    }
}
