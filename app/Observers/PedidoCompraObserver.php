<?php

declare(strict_types=1);

namespace App\Observers;

use App\Mail\PedidoAprovacaoPendenteMail;
use App\Models\PedidoCompra;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * Observa mudanças de status em PedidoCompra para disparar e-mails
 * quando o pedido entra em janela de aprovação.
 *
 * Eventos disparados:
 *   - aguardando_liberacao  → manda email para aprovador de liberação
 *   - aguardando_aprovacao  → manda email para aprovador final
 */
class PedidoCompraObserver
{
    public function updated(PedidoCompra $pedido): void
    {
        if (!$pedido->wasChanged('status')) {
            return;
        }

        $linkAcesso = config('app.url') . '/pedidos-compra/' . $pedido->id;

        match ($pedido->status) {
            PedidoCompra::STATUS_AGUARDANDO_LIBERACAO => $this->notificar(
                $pedido,
                'liberacao',
                'pedidos-compra.liberar',
                $linkAcesso,
            ),
            PedidoCompra::STATUS_AGUARDANDO_APROVACAO => $this->notificar(
                $pedido,
                'aprovacao',
                'pedidos-compra.aprovar',
                $linkAcesso,
            ),
            default => null,
        };
    }

    private function notificar(PedidoCompra $pedido, string $etapa, string $permissao, string $link): void
    {
        $destinatarios = User::query()
            ->where('active', true)
            ->whereNotNull('email')
            ->whereHas('permissions', fn ($q) => $q->where('name', $permissao))
            ->orWhereHas('roles.permissions', fn ($q) => $q->where('name', $permissao))
            ->get(['id', 'email']);

        foreach ($destinatarios as $u) {
            Mail::to($u->email)->queue(new PedidoAprovacaoPendenteMail(
                pedido: $pedido,
                etapa: $etapa,
                linkAcesso: $link,
            ));
        }
    }
}
