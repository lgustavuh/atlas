<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\BibliotecaArea;
use App\Models\User;

class BibliotecaAreaPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

    // Áreas usam as mesmas permissões da biblioteca
    public function viewAny(User $user): bool { return $user->can('biblioteca.view-any'); }
    public function view(User $user, BibliotecaArea $a): bool { return $user->can('biblioteca.view'); }
    public function create(User $user): bool { return $user->can('biblioteca.create'); }
    public function update(User $user, BibliotecaArea $a): bool { return $user->can('biblioteca.update'); }
    public function delete(User $user, BibliotecaArea $a): bool { return $user->can('biblioteca.delete'); }
}
