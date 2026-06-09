<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Candidato;
use App\Models\User;

class CandidatoPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

    public function viewAny(User $user): bool { return $user->can('recrutamento.view-any'); }
    public function view(User $user, Candidato $c): bool { return $user->can('recrutamento.view'); }
    public function create(User $user): bool { return $user->can('recrutamento.create'); }
    public function update(User $user, Candidato $c): bool { return $user->can('recrutamento.update'); }
    public function delete(User $user, Candidato $c): bool
    {
        // Não excluir candidato contratado (histórico)
        if ($c->status === Candidato::STATUS_CONTRATADO) {
            return false;
        }
        return $user->can('recrutamento.delete');
    }
}
