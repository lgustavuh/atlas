<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Solicitação de recuperação de senha.
 *
 * Importante: a resposta é SEMPRE a mesma — "se o email existe, enviamos um link".
 * Nunca revelamos se um email está ou não cadastrado, para não permitir
 * enumeração de usuários.
 *
 * Protege contra:
 *   - Enumeração de usuários (mensagem genérica)
 *   - DoS / email spam (rate limit: 3 tentativas por 15min, por email + IP)
 */
#[Layout('layouts.guest')]
#[Title('Recuperar senha')]
class ForgotPassword extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    public ?string $status = null;

    public function send(): void
    {
        $this->validate();

        $key = $this->throttleKey();

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $segundos = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'email' => "Muitas tentativas. Tente novamente em {$segundos} segundo(s).",
            ]);
        }

        RateLimiter::hit($key, 15 * 60); // janela de 15 minutos

        // Password::sendResetLink só envia se o email existir;
        // mas exibimos a mesma mensagem em ambos os casos.
        Password::sendResetLink(['email' => $this->email]);

        $this->status = 'Se este email estiver cadastrado, enviamos um link de recuperação. Verifique sua caixa de entrada.';
        $this->email = '';
    }

    /**
     * Chave de rate limit combina email + IP — impede tanto spam de um único endereço
     * quanto tentativa massiva de um único atacante.
     */
    private function throttleKey(): string
    {
        return Str::transliterate('forgot:' . mb_strtolower($this->email) . '|' . request()->ip());
    }

    public function render()
    {
        return view('livewire.auth.forgot-password');
    }
}
