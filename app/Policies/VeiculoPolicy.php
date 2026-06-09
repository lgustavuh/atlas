<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Veiculo;

class VeiculoPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

    public function viewAny(User $user): bool { return $user->can('veiculos.view-any'); }
    public function view(User $user, Veiculo $v): bool { return $user->can('veiculos.view'); }
    public function create(User $user): bool { return $user->can('veiculos.create'); }
    public function update(User $user, Veiculo $v): bool { return $user->can('veiculos.update'); }
    public function delete(User $user, Veiculo $v): bool { return $user->can('veiculos.delete'); }
    public function restore(User $user, Veiculo $v): bool { return $user->can('veiculos.restore'); }
}
