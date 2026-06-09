<?php

declare(strict_types=1);

namespace App\Livewire\Geografia;

use App\Models\Cidade;
use App\Models\Estado;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Consulta read-only de geografia brasileira.
 *
 * Os estados são imutáveis (vêm prontos do IBGE).
 * As cidades também (importadas via comando).
 * Esta tela só permite visualização e busca.
 */
#[Layout('layouts.app')]
#[Title('Geografia')]
class Consultar extends Component
{
    use WithPagination;

    #[Url(as: 'uf')]
    public string $filterEstado = '';

    #[Url(as: 'q')]
    public string $search = '';

    public function mount(): void
    {
        // Sem permission check — todos usuários autenticados podem consultar
    }

    public function updating(string $name): void
    {
        if (in_array($name, ['search', 'filterEstado'])) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $cidadesQuery = Cidade::query()
            ->with('estado:id,nome,uf')
            ->when($this->filterEstado !== '', fn (Builder $q) => $q->where('estado_id', $this->filterEstado))
            ->when($this->search !== '', function (Builder $q): void {
                $like = '%' . strtolower($this->search) . '%';
                $q->whereRaw('LOWER(nome) LIKE ?', [$like]);
            })
            ->orderBy('nome');

        return view('livewire.geografia.consultar', [
            'cidades' => $cidadesQuery->paginate(30),
            'estados' => Estado::orderBy('nome')->get(['id', 'nome', 'uf']),
            'totalCidades' => Cidade::count(),
        ]);
    }
}
