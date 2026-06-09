<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Republica;
use App\Models\User;

class RepublicaPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

    public function viewAny(User $user): bool { return $user->can('republicas.view-any'); }
    public function view(User $user, Republica $r): bool { return $user->can('republicas.view'); }
    public function create(User $user): bool { return $user->can('republicas.create'); }
    public function update(User $user, Republica $r): bool { return $user->can('republicas.update'); }
    public function delete(User $user, Republica $r): bool
    {
        // Não excluir república com ocupações ativas
        if ($r->ocupacoesAtuais()->exists()) {
            return false;
        }
        return $user->can('republicas.delete');
    }
}
