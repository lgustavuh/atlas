<?php

declare(strict_types=1);

namespace App\Pdf;

use App\Models\PedidoCompra;

/**
 * Comprovante de Pedido de Compra (ordem de compra para enviar ao fornecedor).
 */
class PedidoCompraPdf extends BasePdf
{
    public function __construct(
        protected PedidoCompra $pedido,
    ) {
        $this->pedido->loadMissing([
            'fornecedor',
            'solicitante',
            'itens.material',
        ]);
    }

    protected function view(): string
    {
        return 'pdfs.pedido-compra';
    }

    protected function dados(): array
    {
        return [
            'titulo' => 'Pedido de Compra ' . ($this->pedido->numero ?? "#{$this->pedido->id}"),
            'pedido' => $this->pedido,
            'orgaoSecundario' => 'Departamento de Compras',
        ];
    }

    protected function nome(): string
    {
        $numero = $this->pedido->numero ? str_replace(['/', ' '], '-', $this->pedido->numero) : "id-{$this->pedido->id}";
        return "pedido-compra_{$numero}";
    }
}
