<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Ferias;
use App\Models\User;

class FeriasPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('ferias.view-any');
    }

    public function view(User $user, Ferias $ferias): bool
    {
        // Colaborador pode ver suas próprias férias
        if ($user->colaborador_id === $ferias->colaborador_id) {
            return true;
        }
        return $user->can('ferias.view');
    }

    public function create(User $user): bool
    {
        return $user->can('ferias.create');
    }

    public function update(User $user, Ferias $ferias): bool
    {
        // Concluídas/canceladas não podem ser editadas
        if (in_array($ferias->status, [Ferias::STATUS_CONCLUIDA, Ferias::STATUS_CANCELADA])) {
            return false;
        }
        return $user->can('ferias.update');
    }

    public function delete(User $user, Ferias $ferias): bool
    {
        // Em gozo ou concluída — não excluir, apenas cancelar
        if (in_array($ferias->status, [Ferias::STATUS_EM_GOZO, Ferias::STATUS_CONCLUIDA])) {
            return false;
        }
        return $user->can('ferias.delete');
    }

    public function approve(User $user, Ferias $ferias): bool
    {
        if ($ferias->status !== Ferias::STATUS_PROGRAMADA) {
            return false;
        }
        return $user->can('ferias.approve');
    }

    public function reject(User $user, Ferias $ferias): bool
    {
        if ($ferias->status !== Ferias::STATUS_PROGRAMADA) {
            return false;
        }
        return $user->can('ferias.reject');
    }
}
