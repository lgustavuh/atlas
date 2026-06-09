<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Colaborador;
use App\Models\User;

class ColaboradorPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }
        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('colaboradores.view-any');
    }

    public function view(User $user, Colaborador $colaborador): bool
    {
        // Próprio colaborador sempre pode ver os próprios dados
        if ($user->colaborador_id === $colaborador->id) {
            return true;
        }
        return $user->can('colaboradores.view');
    }

    public function create(User $user): bool
    {
        return $user->can('colaboradores.create');
    }

    public function update(User $user, Colaborador $colaborador): bool
    {
        return $user->can('colaboradores.update');
    }

    public function delete(User $user, Colaborador $colaborador): bool
    {
        return $user->can('colaboradores.delete');
    }

    public function restore(User $user, Colaborador $colaborador): bool
    {
        return $user->can('colaboradores.restore');
    }

    /**
     * Ver salário é uma permissão à parte (informação sensível).
     */
    public function viewSalary(User $user): bool
    {
        return $user->can('colaboradores.view-salary');
    }

    public function export(User $user): bool
    {
        return $user->can('colaboradores.export');
    }
}
