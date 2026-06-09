<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Departamento;
use App\Models\User;

class DepartamentoPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

    public function viewAny(User $user): bool { return $user->can('departamentos.view-any'); }
    public function view(User $user, Departamento $dep): bool { return $user->can('departamentos.view'); }
    public function create(User $user): bool { return $user->can('departamentos.create'); }
    public function update(User $user, Departamento $dep): bool { return $user->can('departamentos.update'); }
    public function delete(User $user, Departamento $dep): bool { return $user->can('departamentos.delete'); }
    public function restore(User $user, Departamento $dep): bool { return $user->can('departamentos.restore'); }
}
