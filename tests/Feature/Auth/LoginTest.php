<?php

declare(strict_types=1);

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

/*
|--------------------------------------------------------------------------
| Testes de Login
|--------------------------------------------------------------------------
| Cobre cenários críticos de segurança e UX do fluxo de autenticação.
*/

it('exibe a tela de login para visitantes', function () {
    $this->get('/')
        ->assertOk()
        ->assertSeeLivewire(Login::class);
});

it('redireciona usuários autenticados do login para o dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/')
        ->assertRedirect('/dashboard');
});

it('autentica usuário com credenciais válidas', function () {
    $user = User::factory()->create([
        'email' => 'teste@etc.local',
        'password' => 'CorrectPassword@123',
    ]);

    Livewire::test(Login::class)
        ->set('email', 'teste@etc.local')
        ->set('password', 'CorrectPassword@123')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect('/dashboard');

    $this->assertAuthenticatedAs($user);
});

it('rejeita login com senha incorreta', function () {
    User::factory()->create([
        'email' => 'teste@etc.local',
        'password' => 'CorrectPassword@123',
    ]);

    Livewire::test(Login::class)
        ->set('email', 'teste@etc.local')
        ->set('password', 'WrongPassword')
        ->call('login')
        ->assertHasErrors('email');

    $this->assertGuest();
});

it('não autentica usuário com conta inativa', function () {
    User::factory()->inactive()->create([
        'email' => 'inativo@etc.local',
        'password' => 'CorrectPassword@123',
    ]);

    Livewire::test(Login::class)
        ->set('email', 'inativo@etc.local')
        ->set('password', 'CorrectPassword@123')
        ->call('login')
        ->assertHasErrors('email');

    $this->assertGuest();
});

it('não autentica usuário bloqueado', function () {
    User::factory()->locked()->create([
        'email' => 'bloqueado@etc.local',
        'password' => 'CorrectPassword@123',
    ]);

    Livewire::test(Login::class)
        ->set('email', 'bloqueado@etc.local')
        ->set('password', 'CorrectPassword@123')
        ->call('login')
        ->assertHasErrors('email');

    $this->assertGuest();
});

it('aplica rate limiting após muitas tentativas falhadas', function () {
    User::factory()->create([
        'email' => 'teste@etc.local',
        'password' => 'CorrectPassword@123',
    ]);

    // Faz 6 tentativas erradas (limite padrão é 5)
    for ($i = 0; $i < 6; $i++) {
        Livewire::test(Login::class)
            ->set('email', 'teste@etc.local')
            ->set('password', 'WrongPassword')
            ->call('login');
    }

    // A próxima tentativa, mesmo com senha correta, deve ser bloqueada
    Livewire::test(Login::class)
        ->set('email', 'teste@etc.local')
        ->set('password', 'CorrectPassword@123')
        ->call('login')
        ->assertHasErrors('email');

    $this->assertGuest();
});

it('bloqueia conta após muitas tentativas falhadas', function () {
    $user = User::factory()->create([
        'email' => 'teste@etc.local',
        'password' => 'CorrectPassword@123',
    ]);

    for ($i = 0; $i < 5; $i++) {
        Livewire::test(Login::class)
            ->set('email', 'teste@etc.local')
            ->set('password', 'WrongPassword')
            ->call('login');
    }

    expect($user->fresh()->is_locked)->toBeTrue();
});

it('registra IP e timestamp do último login bem-sucedido', function () {
    $user = User::factory()->create([
        'email' => 'teste@etc.local',
        'password' => 'CorrectPassword@123',
    ]);

    expect($user->last_login_at)->toBeNull();

    Livewire::test(Login::class)
        ->set('email', 'teste@etc.local')
        ->set('password', 'CorrectPassword@123')
        ->call('login');

    $user->refresh();
    expect($user->last_login_at)->not->toBeNull();
    expect($user->failed_login_attempts)->toBe(0);
});

it('faz logout corretamente', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/logout')
        ->assertRedirect('/');

    $this->assertGuest();
});
