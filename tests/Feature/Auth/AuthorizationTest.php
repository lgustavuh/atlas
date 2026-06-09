<?php

declare(strict_types=1);

use App\Livewire\Users\UserList;
use App\Models\Cargo;
use App\Models\Colaborador;
use App\Models\Departamento;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

it('bloqueia acesso à listagem de usuários sem permissão', function () {
    asUser('visualizador');

    Livewire::test(UserList::class)
        ->assertForbidden();
});

it('permite admin acessar a listagem de usuários', function () {
    asAdmin();

    Livewire::test(UserList::class)
        ->assertOk();
});

it('admin não pode excluir a própria conta', function () {
    $admin = asAdmin();

    Livewire::test(UserList::class)
        ->call('confirmDelete', $admin->id)
        ->assertSet('showDeleteModal', false);

    expect($admin->fresh())->not->toBeNull();
});

it('admin pode criar novo usuário', function () {
    asAdmin();

    $colab = Colaborador::factory()->create([
        'cargo_id' => Cargo::factory()->create()->id,
        'departamento_id' => Departamento::factory()->create()->id,
    ]);
    $roleColab = Role::where('name', 'colaborador')->first();

    Livewire::test(UserList::class)
        ->call('openCreate')
        ->set('colaborador_id', $colab->id)
        ->set('name', 'Novo Usuário')
        ->set('email', 'novo@etc.local')
        ->set('password', 'SenhaForte@12345')
        ->set('active', true)
        ->set('selectedRoles', [$roleColab->id])
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('users', [
        'email' => 'novo@etc.local',
        'name' => 'Novo Usuário',
        'colaborador_id' => $colab->id,
    ]);
});

it('não permite criar usuário sem colaborador', function () {
    asAdmin();
    $roleColab = Role::where('name', 'colaborador')->first();

    Livewire::test(UserList::class)
        ->call('openCreate')
        ->set('name', 'Sem Colaborador')
        ->set('email', 'sem@etc.local')
        ->set('password', 'SenhaForte@12345')
        ->set('selectedRoles', [$roleColab->id])
        ->call('save')
        ->assertHasErrors(['colaborador_id']);
});

it('não permite 2 usuários para o mesmo colaborador', function () {
    asAdmin();
    $colab = Colaborador::factory()->create([
        'cargo_id' => Cargo::factory()->create()->id,
        'departamento_id' => Departamento::factory()->create()->id,
    ]);
    User::factory()->create(['colaborador_id' => $colab->id]);

    $roleColab = Role::where('name', 'colaborador')->first();
    Livewire::test(UserList::class)
        ->call('openCreate')
        ->set('colaborador_id', $colab->id)
        ->set('name', 'Duplicado')
        ->set('email', 'dup@etc.local')
        ->set('password', 'SenhaForte@12345')
        ->set('selectedRoles', [$roleColab->id])
        ->call('save')
        ->assertHasErrors(['colaborador_id']);
});

it('mapeia departamento RH para perfil gestor_rh ao selecionar colaborador', function () {
    asAdmin();
    $dep = Departamento::factory()->create(['nome' => 'Recursos Humanos']);
    $colab = Colaborador::factory()->create([
        'cargo_id' => Cargo::factory()->create()->id,
        'departamento_id' => $dep->id,
    ]);

    $roleGestorRh = Role::where('name', 'gestor_rh')->first();

    $component = Livewire::test(UserList::class)
        ->call('openCreate')
        ->set('colaborador_id', $colab->id);

    expect($component->get('selectedRoles'))->toContain($roleGestorRh->id);
});

it('senha de usuário criado é armazenada com hash', function () {
    asAdmin();
    $colab = Colaborador::factory()->create([
        'cargo_id' => Cargo::factory()->create()->id,
        'departamento_id' => Departamento::factory()->create()->id,
    ]);
    $roleColab = Role::where('name', 'colaborador')->first();

    Livewire::test(UserList::class)
        ->call('openCreate')
        ->set('colaborador_id', $colab->id)
        ->set('name', 'Teste Hash')
        ->set('email', 'hash@etc.local')
        ->set('password', 'SenhaForte@12345')
        ->set('selectedRoles', [$roleColab->id])
        ->call('save');

    $user = User::where('email', 'hash@etc.local')->first();

    // Senha NUNCA deve estar em texto puro
    expect($user->password)->not->toBe('SenhaForte@12345');
    // Mas deve validar corretamente
    expect(\Hash::check('SenhaForte@12345', $user->password))->toBeTrue();
    // Deve usar Argon2id (não bcrypt)
    expect($user->password)->toStartWith('$argon2id$');
});

it('admin desbloqueia usuário', function () {
    asAdmin();
    $locked = User::factory()->locked()->create();

    Livewire::test(UserList::class)
        ->call('unlock', $locked->id);

    expect($locked->fresh()->is_locked)->toBeFalse();
});
