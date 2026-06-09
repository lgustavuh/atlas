<?php

declare(strict_types=1);

namespace App\Livewire\Profile;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Tela "Meu Perfil" — usuário edita os próprios dados.
 *
 * Limitações:
 *   - Não permite alterar email aqui (precisa fluxo de verificação separado, futuro)
 *   - Não permite alterar perfil/permissões (admin faz isso pela tela de usuários)
 *   - Senha tem fluxo próprio em /change-password
 */
#[Layout('layouts.app')]
#[Title('Meu Perfil')]
class Edit extends Component
{
    public string $name = '';

    public function mount(): void
    {
        /** @var User $user */
        $user = Auth::user();
        $this->name = $user->name;
    }

    public function update(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'min:3', 'max:150'],
        ]);

        /** @var User $user */
        $user = Auth::user();
        $user->update([
            'name' => $data['name'],
            'updated_by' => $user->id,
        ]);

        $this->dispatch('toast', type: 'success', message: 'Perfil atualizado com sucesso.');
    }

    public function render()
    {
        return view('livewire.profile.edit');
    }
}
