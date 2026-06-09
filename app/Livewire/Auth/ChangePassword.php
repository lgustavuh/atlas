<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Models\User;
use App\Rules\SenhaForte;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Permite ao usuário logado trocar a própria senha.
 *
 * Exige confirmação da senha atual (defesa contra sequestro de sessão).
 */
#[Layout('layouts.app')]
#[Title('Trocar senha')]
class ChangePassword extends Component
{
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function update(): void
    {
        $this->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', 'different:current_password', new SenhaForte()],
        ], attributes: [
            'current_password' => 'senha atual',
            'password' => 'nova senha',
        ]);

        /** @var User $user */
        $user = Auth::user();

        // Confirma senha atual
        if (!Hash::check($this->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Senha atual incorreta.',
            ]);
        }

        $user->update(['password' => $this->password]);

        // Política configurável: forçar logout em outros dispositivos
        if (config('atlas.security.force_logout_on_password_change')) {
            Auth::logoutOtherDevices($this->password);
        }

        $this->reset(['current_password', 'password', 'password_confirmation']);
        session()->flash('success', 'Senha alterada com sucesso.');
    }

    public function render()
    {
        return view('livewire.auth.change-password');
    }
}
