<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Atestado;
use App\Models\User;

class AtestadoPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

    public function viewAny(User $user): bool { return $user->can('atestados.view-any'); }

    public function view(User $user, Atestado $atestado): bool
    {
        // Colaborador pode ver seus próprios atestados
        if ($user->colaborador_id === $atestado->colaborador_id) {
            return true;
        }
        return $user->can('atestados.view');
    }

    public function create(User $user): bool { return $user->can('atestados.create'); }

    public function update(User $user, Atestado $atestado): bool
    {
        // Não pode editar atestado já aprovado/rejeitado (apenas admin via before)
        if ($atestado->status !== Atestado::STATUS_PENDENTE) {
            return false;
        }
        return $user->can('atestados.update');
    }

    public function delete(User $user, Atestado $atestado): bool { return $user->can('atestados.delete'); }

    public function approve(User $user, Atestado $atestado): bool
    {
        if ($atestado->status !== Atestado::STATUS_PENDENTE) {
            return false;
        }
        return $user->can('atestados.approve');
    }

    public function reject(User $user, Atestado $atestado): bool
    {
        if ($atestado->status !== Atestado::STATUS_PENDENTE) {
            return false;
        }
        return $user->can('atestados.reject');
    }
}
