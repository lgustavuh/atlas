<?php

declare(strict_types=1);

namespace App\Livewire\Colaboradores;

use App\Models\Colaborador;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Visualização detalhada do colaborador.
 *
 * Read-only — para editar, o usuário vai para a tela de formulário.
 * Mostra dados sensíveis (salário, bancário) condicionalmente.
 */
#[Layout('layouts.app')]
#[Title('Detalhes do Colaborador')]
class Visualizar extends Component
{
    public Colaborador $colaborador;

    public function mount(int $id): void
    {
        $this->colaborador = Colaborador::withTrashed()
            ->with([
                'cargo',
                'departamento',
                'naturalidadeCidade.estado',
                'enderecoResidencial.cidade.estado',
                'dependentes',
            ])
            ->findOrFail($id);

        $this->authorize('view', $this->colaborador);
    }

    public function render()
    {
        return view('livewire.colaboradores.visualizar', [
            'podeVerSalario' => Auth::user()->can('colaboradores.view-salary'),
            'podeEditar' => Auth::user()->can('update', $this->colaborador),
        ]);
    }
}
