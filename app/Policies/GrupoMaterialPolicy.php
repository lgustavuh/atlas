<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\GrupoMaterial;
use App\Models\User;

class GrupoMaterialPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

    // Usa as mesmas permissões de materiais (grupos são parte do módulo)
    public function viewAny(User $user): bool { return $user->can('materiais.view-any'); }
    public function view(User $user, GrupoMaterial $g): bool { return $user->can('materiais.view'); }
    public function create(User $user): bool { return $user->can('materiais.create'); }
    public function update(User $user, GrupoMaterial $g): bool { return $user->can('materiais.update'); }
    public function delete(User $user, GrupoMaterial $g): bool { return $user->can('materiais.delete'); }
}
