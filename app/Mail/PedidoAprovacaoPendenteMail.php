<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\PedidoCompra;

/**
 * E-mail avisando que um pedido de compra entrou em fluxo de aprovação.
 */
class PedidoAprovacaoPendenteMail extends NotificacaoBaseMail
{
    public function __construct(
        public readonly PedidoCompra $pedido,
        public readonly string $etapa, // 'liberacao' ou 'aprovacao'
        public readonly string $linkAcesso,
    ) {
        $this->pedido->loadMissing(['fornecedor:id,razao_social,nome_fantasia', 'solicitante:id,nome']);
    }

    protected function subjectShort(): string
    {
        $numero = $this->pedido->numero ?? "#{$this->pedido->id}";
        $tipoAprov = $this->etapa === 'liberacao' ? 'Liberação' : 'Aprovação final';
        return "{$tipoAprov} pendente: Pedido {$numero}";
    }

    protected function viewName(): string
    {
        return 'emails.pedido-aprovacao';
    }

    protected function viewData(): array
    {
        return [
            'titulo' => 'Pedido aguardando aprovação',
            'tipoNotificacao' => $this->etapa === 'liberacao' ? 'Liberação pendente' : 'Aprovação final pendente',
            'pedido' => $this->pedido,
            'etapa' => $this->etapa,
            'etapaLabel' => $this->etapa === 'liberacao' ? 'Liberação' : 'Aprovação final',
            'linkAcesso' => $this->linkAcesso,
        ];
    }
}
