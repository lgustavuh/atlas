<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Vaga;

class VagaPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

    public function viewAny(User $user): bool { return $user->can('recrutamento.view-any'); }
    public function view(User $user, Vaga $v): bool { return $user->can('recrutamento.view'); }
    public function create(User $user): bool { return $user->can('recrutamento.create'); }
    public function update(User $user, Vaga $v): bool { return $user->can('recrutamento.update'); }
    public function delete(User $user, Vaga $v): bool
    {
        // Não excluir vagas com candidatos contratados (histórico)
        if ($v->candidatos()->where('status', 'contratado')->exists()) {
            return false;
        }
        return $user->can('recrutamento.delete');
    }
}
