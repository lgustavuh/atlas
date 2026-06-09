<?php

declare(strict_types=1);

namespace App\Livewire\Republicas;

use App\Models\Cidade;
use App\Models\Estado;
use App\Models\Republica;
use App\Services\RepublicaService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Repúblicas')]
class Gerenciar extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'st')]
    public string $filterStatus = '';

    public bool $showModal = false;
    public bool $editando = false;
    public ?int $editingId = null;

    public string $nome = '';
    public string $endereco = '';
    public ?int $estadoFiltro = null;
    public ?int $cidade_id = null;
    public int $capacidade_total = 4;
    public ?float $aluguel_mensal = null;
    public string $responsavel_externo_nome = '';
    public string $responsavel_externo_telefone = '';
    public bool $ativa = true;
    public string $observacoes = '';

    public bool $showDeleteModal = false;
    public ?int $deletingId = null;
    public string $deletingName = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Republica::class);
    }

    public function updating(string $name): void
    {
        if (in_array($name, ['search', 'filterStatus'])) {
            $this->resetPage();
        }
    }

    public function updatedEstadoFiltro(): void
    {
        $this->cidade_id = null;
    }

    public function openCreate(): void
    {
        $this->authorize('create', Republica::class);
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $r = Republica::with('cidade')->findOrFail($id);
        $this->authorize('update', $r);

        $this->editingId = $r->id;
        $this->nome = $r->nome;
        $this->endereco = $r->endereco;
        $this->cidade_id = $r->cidade_id;
        $this->estadoFiltro = $r->cidade?->estado_id;
        $this->capacidade_total = $r->capacidade_total;
        $this->aluguel_mensal = $r->aluguel_mensal !== null ? (float) $r->aluguel_mensal : null;
        $this->responsavel_externo_nome = (string) $r->responsavel_externo_nome;
        $this->responsavel_externo_telefone = (string) $r->responsavel_externo_telefone;
        $this->ativa = $r->ativa;
        $this->observacoes = (string) $r->observacoes;

        $this->editando = true;
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function save(RepublicaService $service): void
    {
        $data = $this->validate([
            'nome' => ['required', 'string', 'min:3', 'max:150'],
            'endereco' => ['required', 'string', 'min:5', 'max:300'],
            'cidade_id' => ['nullable', 'integer', 'exists:cidades,id'],
            'capacidade_total' => ['required', 'integer', 'min:1', 'max:100'],
            'aluguel_mensal' => ['nullable', 'numeric', 'min:0'],
            'responsavel_externo_nome' => ['nullable', 'string', 'max:150'],
            'responsavel_externo_telefone' => ['nullable', 'string', 'max:20'],
            'ativa' => ['boolean'],
            'observacoes' => ['nullable', 'string', 'max:2000'],
        ], messages: [
            'nome.min' => 'O nome deve ter pelo menos 3 caracteres.',
            'endereco.min' => 'O endereço deve ter pelo menos 5 caracteres.',
        ], attributes: [
            'cidade_id' => 'cidade',
            'capacidade_total' => 'capacidade total',
        ]);

        try {
            if ($this->editando) {
                $republica = Republica::findOrFail($this->editingId);
                $this->authorize('update', $republica);
                $service->atualizar($republica, $data);
                $this->dispatch('toast', type: 'success', message: "República '{$republica->nome}' atualizada.");
            } else {
                $republica = $service->criar($data);
                $this->dispatch('toast', type: 'success', message: "República '{$republica->nome}' cadastrada.");
            }
            $this->closeModal();
        } catch (\Throwable $e) {
            if (app()->environment('testing', 'local')) {
                throw $e;
            }
            \Log::error('Erro ao salvar república', ['erro' => $e->getMessage()]);
            $this->dispatch('toast', type: 'error', message: 'Erro ao salvar.');
        }
    }

    public function toggleAtiva(int $id): void
    {
        $r = Republica::findOrFail($id);
        $this->authorize('update', $r);
        $r->update(['ativa' => !$r->ativa]);
        $this->dispatch('toast', type: 'success',
            message: $r->ativa ? 'República ativada.' : 'República desativada.');
    }

    public function confirmDelete(int $id): void
    {
        $r = Republica::findOrFail($id);
        $this->authorize('delete', $r);
        $this->deletingId = $id;
        $this->deletingName = $r->nome;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $r = Republica::findOrFail($this->deletingId);
        $this->authorize('delete', $r);
        $r->delete();
        $this->dispatch('toast', type: 'success', message: 'República excluída.');
        $this->showDeleteModal = false;
    }

    private function resetForm(): void
    {
        $this->reset([
            'nome', 'endereco', 'cidade_id', 'estadoFiltro',
            'aluguel_mensal', 'responsavel_externo_nome', 'responsavel_externo_telefone',
            'observacoes', 'editingId', 'editando',
        ]);
        $this->capacidade_total = 4;
        $this->ativa = true;
        $this->resetErrorBag();
    }

    public function render()
    {
        $query = Republica::query()
            ->with('cidade:id,nome,estado_id')
            ->withCount(['ocupacoesAtuais'])
            ->when($this->search !== '', fn (Builder $q) => $q->buscar($this->search))
            ->when($this->filterStatus === 'ativas', fn (Builder $q) => $q->where('ativa', true))
            ->when($this->filterStatus === 'inativas', fn (Builder $q) => $q->where('ativa', false))
            ->when($this->filterStatus === 'lotadas', function (Builder $q): void {
                // Lotada = capacidade <= ocupações atuais. Como ocupações atuais é via withCount,
                // filtramos depois via collection. Mas para paginação, usamos subquery.
                $q->whereRaw('capacidade_total <= (
                    SELECT COUNT(*) FROM republica_ocupacoes
                    WHERE republica_id = republicas.id
                    AND (data_saida IS NULL OR data_saida >= CURRENT_DATE)
                )');
            })
            ->orderByDesc('ativa')
            ->orderBy('nome');

        return view('livewire.republicas.gerenciar', [
            'republicas' => $query->paginate(15),
            'estados' => Estado::orderBy('nome')->get(['id', 'nome', 'uf']),
            'cidadesDisponiveis' => $this->estadoFiltro
                ? Cidade::where('estado_id', $this->estadoFiltro)->orderBy('nome')->get(['id', 'nome'])
                : collect(),
            'stats' => [
                'total_ativas' => Republica::ativas()->count(),
                'capacidade_total' => (int) Republica::ativas()->sum('capacidade_total'),
                'ocupantes' => \App\Models\RepublicaOcupacao::atuais()->count(),
            ],
        ]);
    }
}
