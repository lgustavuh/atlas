<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Advertencia;
use App\Models\User;

class AdvertenciaPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

    public function viewAny(User $user): bool { return $user->can('advertencias.view-any'); }

    public function view(User $user, Advertencia $adv): bool
    {
        // Colaborador pode ver suas próprias advertências
        if ($user->colaborador_id === $adv->colaborador_id) {
            return true;
        }
        return $user->can('advertencias.view');
    }

    public function create(User $user): bool { return $user->can('advertencias.create'); }
    public function update(User $user, Advertencia $adv): bool { return $user->can('advertencias.update'); }
    public function delete(User $user, Advertencia $adv): bool { return $user->can('advertencias.delete'); }
}
