<?php

declare(strict_types=1);

use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\ResetPassword;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;

it('envia link de recuperação para email existente', function () {
    Notification::fake();

    $user = User::factory()->create(['email' => 'usuario@etc.local']);

    Livewire::test(ForgotPassword::class)
        ->set('email', 'usuario@etc.local')
        ->call('send')
        ->assertHasNoErrors();

    Notification::assertSentTo($user, ResetPasswordNotification::class);
});

it('exibe mesma mensagem para email inexistente (não vaza enumeração)', function () {
    Notification::fake();

    Livewire::test(ForgotPassword::class)
        ->set('email', 'inexistente@etc.local')
        ->call('send')
        ->assertHasNoErrors()
        ->assertSet('status', fn ($status) => str_contains($status, 'Se este email'));

    Notification::assertNothingSent();
});

it('reseta a senha com token válido', function () {
    $user = User::factory()->create(['email' => 'usuario@etc.local']);
    $token = Password::createToken($user);

    Livewire::test(ResetPassword::class, ['token' => $token])
        ->set('email', 'usuario@etc.local')
        ->set('password', 'NovaSenha@Forte123')
        ->set('password_confirmation', 'NovaSenha@Forte123')
        ->call('redefinirSenha')
        ->assertHasNoErrors()
        ->assertRedirect('/');

    expect(\Hash::check('NovaSenha@Forte123', $user->fresh()->password))->toBeTrue();
});

it('rejeita senha fraca no reset', function () {
    $user = User::factory()->create(['email' => 'usuario@etc.local']);
    $token = Password::createToken($user);

    Livewire::test(ResetPassword::class, ['token' => $token])
        ->set('email', 'usuario@etc.local')
        ->set('password', '12345678')  // Senha fraca
        ->set('password_confirmation', '12345678')
        ->call('redefinirSenha')
        ->assertHasErrors('password');
});

it('limpa lockout ao resetar a senha', function () {
    $user = User::factory()->locked()->create(['email' => 'bloqueado@etc.local']);
    $token = Password::createToken($user);

    Livewire::test(ResetPassword::class, ['token' => $token])
        ->set('email', 'bloqueado@etc.local')
        ->set('password', 'NovaSenha@Forte123')
        ->set('password_confirmation', 'NovaSenha@Forte123')
        ->call('redefinirSenha');

    $user->refresh();
    expect($user->is_locked)->toBeFalse();
    expect($user->failed_login_attempts)->toBe(0);
});
