<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\BibliotecaDocumento;
use App\Models\User;

class BibliotecaDocumentoPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

    public function viewAny(User $user): bool { return $user->can('biblioteca.view-any'); }
    public function view(User $user, BibliotecaDocumento $d): bool { return $user->can('biblioteca.view'); }
    public function create(User $user): bool { return $user->can('biblioteca.create'); }
    public function update(User $user, BibliotecaDocumento $d): bool { return $user->can('biblioteca.update'); }
    public function delete(User $user, BibliotecaDocumento $d): bool { return $user->can('biblioteca.delete'); }
    public function download(User $user, BibliotecaDocumento $d): bool { return $user->can('biblioteca.download'); }
}
