<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Material;
use App\Models\User;

class MaterialPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

    public function viewAny(User $user): bool { return $user->can('materiais.view-any'); }
    public function view(User $user, Material $m): bool { return $user->can('materiais.view'); }
    public function create(User $user): bool { return $user->can('materiais.create'); }
    public function update(User $user, Material $m): bool { return $user->can('materiais.update'); }
    public function delete(User $user, Material $m): bool { return $user->can('materiais.delete'); }
    public function restore(User $user, Material $m): bool { return $user->can('materiais.restore'); }
}
