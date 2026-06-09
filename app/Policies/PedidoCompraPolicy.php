<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PedidoCompra;
use App\Models\User;

class PedidoCompraPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

    public function viewAny(User $user): bool { return $user->can('pedidos-compra.view-any'); }
    public function view(User $user, PedidoCompra $p): bool { return $user->can('pedidos-compra.view'); }
    public function create(User $user): bool { return $user->can('pedidos-compra.create'); }

    public function update(User $user, PedidoCompra $p): bool
    {
        if (!$p->podeEditar()) {
            return false;
        }
        return $user->can('pedidos-compra.update');
    }

    public function delete(User $user, PedidoCompra $p): bool
    {
        // Só rascunhos podem ser excluídos
        if ($p->status !== PedidoCompra::STATUS_RASCUNHO) {
            return false;
        }
        return $user->can('pedidos-compra.delete');
    }

    /**
     * Liberar = 1ª etapa de aprovação.
     */
    public function liberar(User $user, PedidoCompra $p): bool
    {
        if ($p->status !== PedidoCompra::STATUS_AGUARDANDO_LIBERACAO) {
            return false;
        }
        return $user->can('pedidos-compra.liberar');
    }

    /**
     * Aprovar = 2ª etapa (após liberação).
     */
    public function aprovar(User $user, PedidoCompra $p): bool
    {
        if ($p->status !== PedidoCompra::STATUS_AGUARDANDO_APROVACAO) {
            return false;
        }
        return $user->can('pedidos-compra.aprovar');
    }

    public function receber(User $user, PedidoCompra $p): bool
    {
        if (!in_array($p->status, [PedidoCompra::STATUS_ENVIADO, PedidoCompra::STATUS_PARCIAL])) {
            return false;
        }
        return $user->can('pedidos-compra.receber');
    }

    public function cancelar(User $user, PedidoCompra $p): bool
    {
        if (!$p->podeCancelar()) {
            return false;
        }
        return $user->can('pedidos-compra.update');
    }
}
