<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Cargo;
use App\Models\User;

class CargoPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

    public function viewAny(User $user): bool { return $user->can('cargos.view-any'); }
    public function view(User $user, Cargo $cargo): bool { return $user->can('cargos.view'); }
    public function create(User $user): bool { return $user->can('cargos.create'); }
    public function update(User $user, Cargo $cargo): bool { return $user->can('cargos.update'); }
    public function delete(User $user, Cargo $cargo): bool { return $user->can('cargos.delete'); }
    public function restore(User $user, Cargo $cargo): bool { return $user->can('cargos.restore'); }
}
