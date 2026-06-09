<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\VeiculoManutencao;

class VeiculoManutencaoPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

    public function viewAny(User $user): bool { return $user->can('manutencoes.view-any'); }
    public function view(User $user, VeiculoManutencao $m): bool { return $user->can('manutencoes.view'); }
    public function create(User $user): bool { return $user->can('manutencoes.create'); }
    public function update(User $user, VeiculoManutencao $m): bool { return $user->can('manutencoes.update'); }
    public function delete(User $user, VeiculoManutencao $m): bool { return $user->can('manutencoes.delete'); }
}
