<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * Policy do model User.
 *
 * Camada de autorização que combina:
 *   - Permissões granulares (spatie/permission)
 *   - Regras de negócio (não pode deletar a si mesmo, admin protegido, etc)
 *
 * As permissões 'users.*' são definidas no RolesAndPermissionsSeeder.
 */
class UserPolicy
{
    /**
     * Super admin: tem todas as permissões automaticamente.
     * Atalho seguro para evitar travas absurdas.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return null; // continua com as regras específicas
    }

    public function viewAny(User $user): bool
    {
        return $user->can('users.view-any');
    }

    public function view(User $user, User $model): bool
    {
        // Próprio usuário sempre pode ver a si mesmo
        if ($user->id === $model->id) {
            return true;
        }

        return $user->can('users.view');
    }

    public function create(User $user): bool
    {
        return $user->can('users.create');
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('users.update');
    }

    public function delete(User $user, User $model): bool
    {
        // Ninguém pode excluir a si mesmo
        if ($user->id === $model->id) {
            return false;
        }

        // Apenas admin pode deletar outros admins
        if ($model->hasRole('admin') && !$user->hasRole('admin')) {
            return false;
        }

        return $user->can('users.delete');
    }

    public function restore(User $user, User $model): bool
    {
        return $user->can('users.restore');
    }
}
