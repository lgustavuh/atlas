<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Obra;
use App\Models\User;

class ObraPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

    public function viewAny(User $user): bool { return $user->can('obras.view-any'); }
    public function view(User $user, Obra $o): bool { return $user->can('obras.view'); }
    public function create(User $user): bool { return $user->can('obras.create'); }
    public function update(User $user, Obra $o): bool { return $user->can('obras.update'); }
    public function delete(User $user, Obra $o): bool
    {
        // Não excluir obras concluídas (histórico)
        if ($o->status === Obra::STATUS_CONCLUIDA) {
            return false;
        }
        return $user->can('obras.delete');
    }
}
