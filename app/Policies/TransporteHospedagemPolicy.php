<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TransporteHospedagem;
use App\Models\User;

class TransporteHospedagemPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

    public function viewAny(User $user): bool { return $user->can('transporte-hospedagem.view-any'); }
    public function view(User $user, TransporteHospedagem $t): bool { return $user->can('transporte-hospedagem.view'); }
    public function create(User $user): bool { return $user->can('transporte-hospedagem.create'); }
    public function update(User $user, TransporteHospedagem $t): bool { return $user->can('transporte-hospedagem.update'); }
    public function delete(User $user, TransporteHospedagem $t): bool { return $user->can('transporte-hospedagem.delete'); }
}
