<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Fornecedor;
use App\Models\User;

class FornecedorPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

    public function viewAny(User $user): bool { return $user->can('fornecedores.view-any'); }
    public function view(User $user, Fornecedor $f): bool { return $user->can('fornecedores.view'); }
    public function create(User $user): bool { return $user->can('fornecedores.create'); }
    public function update(User $user, Fornecedor $f): bool { return $user->can('fornecedores.update'); }
    public function delete(User $user, Fornecedor $f): bool { return $user->can('fornecedores.delete'); }
    public function restore(User $user, Fornecedor $f): bool { return $user->can('fornecedores.restore'); }
}
