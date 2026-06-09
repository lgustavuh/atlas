<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Componente Livewire para login.
 *
 * Recursos de segurança:
 *   - Rate limiting por IP+email (configurável em config/etc.php)
 *   - Bloqueio progressivo da conta após N falhas
 *   - Detecção de lockout (sem revelar se o email existe)
 *   - Regeneração de session ID após login (anti session fixation)
 *   - Verificação de active e deleted_at antes de autenticar
 *   - Registro de IP e timestamp do último login
 *
 * O que NUNCA fazemos aqui:
 *   - Revelar se um email existe ou não (sempre mesma mensagem genérica)
 *   - Logar a senha (nem em texto, nem em hash)
 *   - Confiar em qualquer dado vindo do cliente sem validar
 */
#[Layout('layouts.guest')]
#[Title('Entrar')]
class Login extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $this->validate();

        // Chave do rate limiter combina IP + email para não bloquear
        // outros usuários da mesma rede injustamente
        $throttleKey = Str::transliterate(
            Str::lower($this->email) . '|' . request()->ip()
        );

        $maxTentativas = (int) config('atlas.security.login_max_attempts', 5);
        $decayMinutos = (int) config('atlas.security.login_decay_minutes', 1);

        // Bloqueio temporário por excesso de tentativas neste IP
        if (RateLimiter::tooManyAttempts($throttleKey, $maxTentativas)) {
            event(new Lockout(request()));
            $segundos = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => "Muitas tentativas. Tente novamente em {$segundos} segundos.",
            ]);
        }

        // Tenta autenticar (Laravel cuida do hash check internamente)
        if (!Auth::attempt($this->credentials(), $this->remember)) {
            RateLimiter::hit($throttleKey, $decayMinutos * 60);

            // Se o email existe, registra a tentativa falha na própria conta
            // (separadamente do rate limit, que é por IP)
            $user = User::where('email', $this->email)->first();
            $user?->registrarTentativaFalha();

            throw ValidationException::withMessages([
                'email' => 'Email ou senha incorretos.',
            ]);
        }

        /** @var User $user */
        $user = Auth::user();

        // Verificações pós-autenticação que o Laravel não faz por padrão
        if (!$user->podeAutenticar()) {
            Auth::logout();

            $mensagem = match (true) {
                !$user->active => 'Sua conta está desativada. Contate o administrador.',
                $user->is_locked => "Conta bloqueada até {$user->locked_until->format('H:i')}. Tente novamente mais tarde.",
                default => 'Esta conta não pode ser acessada.',
            };

            throw ValidationException::withMessages(['email' => $mensagem]);
        }

        // Sucesso!
        RateLimiter::clear($throttleKey);
        $user->registrarLoginSucesso(request()->ip());

        // Anti session fixation: gera novo ID de sessão
        session()->regenerate();

        $this->redirectIntended(route('dashboard'), navigate: true);
    }

    /**
     * @return array{email: string, password: string, active: true}
     */
    private function credentials(): array
    {
        return [
            'email' => $this->email,
            'password' => $this->password,
            // Inclui filtro na query: usuários inativos nem chegam à validação de senha
            'active' => true,
        ];
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
