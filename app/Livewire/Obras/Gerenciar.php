<?php

declare(strict_types=1);

namespace App\Livewire\Obras;

use App\Models\Cidade;
use App\Models\Colaborador;
use App\Models\Estado;
use App\Models\Obra;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Gestão de Obras.
 */
#[Layout('layouts.app')]
#[Title('Obras')]
class Gerenciar extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $filterStatus = '';

    // Modal
    public bool $showModal = false;
    public bool $editando = false;
    public ?int $editingId = null;

    // Form
    public string $codigo = '';
    public string $nome = '';
    public string $descricao = '';
    public string $endereco = '';
    public ?int $cidade_id = null;
    public ?int $estadoFiltro = null;
    public ?int $responsavel_id = null;
    public ?string $data_inicio = null;
    public ?string $data_termino_previsto = null;
    public ?string $data_termino_real = null;
    public ?float $orcamento = null;
    public string $status = Obra::STATUS_PLANEJAMENTO;

    // Exclusão
    public bool $showDeleteModal = false;
    public ?int $deletingId = null;
    public string $deletingName = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Obra::class);
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
        $this->authorize('create', Obra::class);
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $o = Obra::with('cidade')->findOrFail($id);
        $this->authorize('update', $o);

        $this->editingId = $o->id;
        $this->codigo = (string) $o->codigo;
        $this->nome = $o->nome;
        $this->descricao = (string) $o->descricao;
        $this->endereco = (string) $o->endereco;
        $this->cidade_id = $o->cidade_id;
        $this->estadoFiltro = $o->cidade?->estado_id;
        $this->responsavel_id = $o->responsavel_id;
        $this->data_inicio = $o->data_inicio?->toDateString();
        $this->data_termino_previsto = $o->data_termino_previsto?->toDateString();
        $this->data_termino_real = $o->data_termino_real?->toDateString();
        $this->orcamento = $o->orcamento !== null ? (float) $o->orcamento : null;
        $this->status = $o->status;

        $this->editando = true;
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function save(): void
    {
        $statuses = array_keys(Obra::statusesComLabel());

        $data = $this->validate([
            'codigo' => [
                'nullable', 'string', 'max:30',
                Rule::unique('obras', 'codigo')
                    ->ignore($this->editingId)
                    ->whereNull('deleted_at'),
            ],
            'nome' => ['required', 'string', 'min:3', 'max:200'],
            'descricao' => ['nullable', 'string', 'max:5000'],
            'endereco' => ['nullable', 'string', 'max:300'],
            'cidade_id' => ['nullable', 'integer', 'exists:cidades,id'],
            'responsavel_id' => ['nullable', 'integer', 'exists:colaboradores,id'],
            'data_inicio' => ['nullable', 'date'],
            'data_termino_previsto' => ['nullable', 'date', 'after_or_equal:data_inicio'],
            'data_termino_real' => ['nullable', 'date', 'after_or_equal:data_inicio'],
            'orcamento' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in($statuses)],
        ], messages: [
            'codigo.unique' => 'Já existe uma obra com este código.',
            'data_termino_previsto.after_or_equal' => 'O término previsto deve ser após o início.',
            'data_termino_real.after_or_equal' => 'O término real deve ser após o início.',
            'nome.min' => 'O nome deve ter pelo menos 3 caracteres.',
        ], attributes: [
            'cidade_id' => 'cidade',
            'responsavel_id' => 'responsável',
        ]);

        // String vazia → null (lição B6)
        $payload = [];
        foreach ($data as $k => $v) {
            $payload[$k] = is_string($v) && trim($v) === '' ? null : $v;
        }
        $payload['updated_by'] = Auth::id();

        if ($this->editando) {
            $obra = Obra::findOrFail($this->editingId);
            $this->authorize('update', $obra);
            $obra->update($payload);
            $this->dispatch('toast', type: 'success', message: "Obra '{$obra->nome}' atualizada.");
        } else {
            $payload['created_by'] = Auth::id();
            $obra = Obra::create($payload);
            $this->dispatch('toast', type: 'success', message: "Obra '{$obra->nome}' cadastrada.");
        }

        $this->closeModal();
    }

    /**
     * Marcar obra como concluída (preenche data_termino_real).
     */
    public function concluir(int $id): void
    {
        $obra = Obra::findOrFail($id);
        $this->authorize('update', $obra);

        if ($obra->status === Obra::STATUS_CONCLUIDA) {
            $this->dispatch('toast', type: 'error', message: 'Obra já está concluída.');
            return;
        }

        $obra->update([
            'status' => Obra::STATUS_CONCLUIDA,
            'data_termino_real' => $obra->data_termino_real ?? now()->toDateString(),
            'updated_by' => Auth::id(),
        ]);
        $this->dispatch('toast', type: 'success', message: "Obra '{$obra->nome}' marcada como concluída.");
    }

    public function confirmDelete(int $id): void
    {
        $o = Obra::findOrFail($id);
        $this->authorize('delete', $o);
        $this->deletingId = $id;
        $this->deletingName = $o->nome;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $o = Obra::findOrFail($this->deletingId);
        $this->authorize('delete', $o);
        $o->update(['updated_by' => Auth::id()]);
        $o->delete();
        $this->dispatch('toast', type: 'success', message: "Obra '{$o->nome}' excluída.");
        $this->showDeleteModal = false;
    }

    private function resetForm(): void
    {
        $this->reset([
            'codigo', 'nome', 'descricao', 'endereco', 'cidade_id', 'estadoFiltro',
            'responsavel_id', 'data_inicio', 'data_termino_previsto', 'data_termino_real',
            'orcamento', 'editingId', 'editando',
        ]);
        $this->status = Obra::STATUS_PLANEJAMENTO;
        $this->resetErrorBag();
    }

    public function render()
    {
        $query = Obra::query()
            ->with(['cidade:id,nome,estado_id', 'responsavel:id,nome'])
            ->when($this->search !== '', fn (Builder $q) => $q->buscar($this->search))
            ->when($this->filterStatus !== '', fn (Builder $q) => $q->where('status', $this->filterStatus))
            ->orderByRaw("CASE
                WHEN status = 'em_andamento' THEN 1
                WHEN status = 'planejamento' THEN 2
                WHEN status = 'pausada' THEN 3
                WHEN status = 'concluida' THEN 4
                ELSE 5 END")
            ->orderByDesc('data_inicio');

        return view('livewire.obras.gerenciar', [
            'obras' => $query->paginate(15),
            'estados' => Estado::orderBy('nome')->get(['id', 'nome', 'uf']),
            'cidadesDisponiveis' => $this->estadoFiltro
                ? Cidade::where('estado_id', $this->estadoFiltro)->orderBy('nome')->get(['id', 'nome'])
                : collect(),
            'colaboradores' => Colaborador::orderBy('nome')->get(['id', 'nome']),
            'statuses' => Obra::statusesComLabel(),
            'stats' => [
                'em_andamento' => Obra::comStatus(Obra::STATUS_EM_ANDAMENTO)->count(),
                'planejamento' => Obra::comStatus(Obra::STATUS_PLANEJAMENTO)->count(),
                'atrasadas' => Obra::ativas()
                    ->whereNotNull('data_termino_previsto')
                    ->whereDate('data_termino_previsto', '<', now())
                    ->count(),
            ],
        ]);
    }
}
