<?php

declare(strict_types=1);

namespace App\Livewire\Roles;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Gestão de perfis (roles) e suas permissões.
 *
 * Permite ao admin:
 *   - Ver os perfis existentes
 *   - Criar novos perfis
 *   - Editar nome/descrição
 *   - Atribuir/remover permissões granulares
 *
 * O perfil 'admin' é protegido e não pode ter permissões removidas.
 */
#[Layout('layouts.app')]
#[Title('Perfis e Permissões')]
class RoleList extends Component
{
    public bool $showModal = false;
    public bool $editing = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $display_name = '';
    public string $description = '';
    /** @var array<int> */
    public array $selectedPermissions = [];

    public function mount(): void
    {
        $this->authorize('viewAny', Role::class);
    }

    public function openCreate(): void
    {
        abort_unless(Auth::user()->can('roles.create'), 403);
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        abort_unless(Auth::user()->can('roles.update'), 403);

        $role = Role::with('permissions')->findOrFail($id);

        $this->editingId = $role->id;
        $this->name = $role->name;
        $this->display_name = $role->display_name ?? '';
        $this->description = $role->description ?? '';
        $this->selectedPermissions = $role->permissions->pluck('id')->toArray();
        $this->editing = true;
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function save(): void
    {
        $rules = [
            'name' => [
                'required', 'string', 'max:125',
                // Apenas letras minúsculas, números e underscore
                'regex:/^[a-z][a-z0-9_]*$/',
                $this->editing
                    ? 'unique:roles,name,' . $this->editingId
                    : 'unique:roles,name',
            ],
            'display_name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'selectedPermissions' => ['array'],
            'selectedPermissions.*' => ['integer', 'exists:permissions,id'],
        ];

        $data = $this->validate($rules, attributes: [
            'name' => 'identificador',
            'display_name' => 'nome',
        ]);

        if ($this->editing) {
            $role = Role::findOrFail($this->editingId);

            // Protege o role 'admin': não permite renomear nem remover todas as permissões
            if ($role->name === 'admin') {
                if ($data['name'] !== 'admin') {
                    session()->flash('error', 'O perfil "admin" não pode ser renomeado.');
                    return;
                }
                if (count($data['selectedPermissions']) === 0) {
                    session()->flash('error', 'O perfil "admin" deve ter pelo menos uma permissão.');
                    return;
                }
            }

            $role->update([
                'name' => $data['name'],
                'display_name' => $data['display_name'],
                'description' => $data['description'] ?? null,
            ]);

            $permissions = Permission::whereIn('id', $data['selectedPermissions'])->get();
            $role->syncPermissions($permissions);

            session()->flash('success', "Perfil {$role->display_name} atualizado.");
        } else {
            $role = Role::create([
                'name' => $data['name'],
                'guard_name' => 'web',
                'display_name' => $data['display_name'],
                'description' => $data['description'] ?? null,
            ]);

            $permissions = Permission::whereIn('id', $data['selectedPermissions'])->get();
            $role->syncPermissions($permissions);

            session()->flash('success', "Perfil {$role->display_name} criado.");
        }

        // Limpa cache de permissões
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->closeModal();
    }

    public function delete(int $id): void
    {
        abort_unless(Auth::user()->can('roles.delete'), 403);

        $role = Role::findOrFail($id);

        if ($role->name === 'admin') {
            session()->flash('error', 'O perfil "admin" não pode ser excluído.');
            return;
        }

        if ($role->users()->count() > 0) {
            session()->flash('error', 'Este perfil está sendo usado por usuários. Remova-os antes.');
            return;
        }

        $nome = $role->display_name ?? $role->name;
        $role->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        session()->flash('success', "Perfil {$nome} excluído.");
    }

    private function resetForm(): void
    {
        $this->reset(['name', 'display_name', 'description', 'editingId', 'selectedPermissions']);
        $this->editing = false;
        $this->resetErrorBag();
    }

    public function render()
    {
        // Agrupa permissões por módulo para a UI ficar organizada
        $permissionsByModule = Permission::orderBy('name')->get()
            ->groupBy(fn ($p) => explode('.', $p->name)[0]);

        return view('livewire.roles.role-list', [
            'roles' => Role::withCount(['permissions', 'users'])
                ->orderBy('display_name')
                ->get(),
            'permissionsByModule' => $permissionsByModule,
        ]);
    }
}
