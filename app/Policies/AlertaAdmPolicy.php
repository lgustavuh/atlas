<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AlertaAdm;
use App\Models\User;

class AlertaAdmPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

    public function viewAny(User $user): bool { return $user->can('alertas-adm.view-any'); }
    public function view(User $user, AlertaAdm $a): bool { return $user->can('alertas-adm.view'); }
    public function create(User $user): bool { return $user->can('alertas-adm.create'); }
    public function update(User $user, AlertaAdm $a): bool { return $user->can('alertas-adm.update'); }
    public function delete(User $user, AlertaAdm $a): bool { return $user->can('alertas-adm.delete'); }
}
