<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Classificacao;
use App\Models\User;

class ClassificacaoPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

    public function viewAny(User $user): bool { return $user->can('classificacoes.view-any'); }
    public function view(User $user, Classificacao $c): bool { return $user->can('classificacoes.view'); }
    public function create(User $user): bool { return $user->can('classificacoes.create'); }
    public function update(User $user, Classificacao $c): bool { return $user->can('classificacoes.update'); }
    public function delete(User $user, Classificacao $c): bool { return $user->can('classificacoes.delete'); }
    public function restore(User $user, Classificacao $c): bool { return $user->can('classificacoes.restore'); }
}
