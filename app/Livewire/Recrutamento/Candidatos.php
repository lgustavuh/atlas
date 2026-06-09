<?php

declare(strict_types=1);

namespace App\Livewire\Recrutamento;

use App\Models\Candidato;
use App\Models\Vaga;
use App\Rules\Cpf;
use App\Services\CandidatoService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Candidatos')]
class Candidatos extends Component
{
    use WithFileUploads;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'vaga')]
    public ?int $filterVagaId = null;

    #[Url(as: 'status')]
    public string $filterStatus = '';

    public bool $showModal = false;
    public bool $editando = false;
    public ?int $editingId = null;

    public ?int $vaga_id = null;
    public string $nome = '';
    public string $cpf = '';
    public string $email = '';
    public string $telefone = '';
    public string $experiencia = '';
    public ?int $pontuacao = null;
    public string $observacoes = '';
    public string $status = Candidato::STATUS_INSCRITO;
    public $curriculo = null;

    public bool $showDeleteModal = false;
    public ?int $deletingId = null;
    public string $deletingName = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Candidato::class);
    }

    public function updating(string $name): void
    {
        if (in_array($name, ['search', 'filterVagaId', 'filterStatus'])) {
            $this->resetPage();
        }
    }

    public function openCreate(): void
    {
        $this->authorize('create', Candidato::class);
        $this->resetForm();
        if ($this->filterVagaId) {
            $this->vaga_id = $this->filterVagaId;
        }
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $c = Candidato::findOrFail($id);
        $this->authorize('update', $c);

        $this->editingId = $c->id;
        $this->vaga_id = $c->vaga_id;
        $this->nome = $c->nome;
        $this->cpf = Cpf::formatar($c->cpf ?? '');
        $this->email = $c->email;
        $this->telefone = (string) $c->telefone;
        $this->experiencia = (string) $c->experiencia;
        $this->pontuacao = $c->pontuacao;
        $this->observacoes = (string) $c->observacoes;
        $this->status = $c->status;

        $this->editando = true;
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function save(CandidatoService $service): void
    {
        $this->cpf = $this->cpf ? Cpf::limpar($this->cpf) : '';

        $data = $this->validate([
            'vaga_id' => ['required', 'integer', 'exists:vagas,id'],
            'nome' => ['required', 'string', 'min:3', 'max:150'],
            'cpf' => ['nullable', new Cpf()],
            'email' => ['required', 'email', 'max:200'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'experiencia' => ['nullable', 'string', 'max:5000'],
            'pontuacao' => ['nullable', 'integer', 'min:0', 'max:100'],
            'observacoes' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(array_keys(Candidato::statusesComLabel()))],
            'curriculo' => [
                'nullable',
                'file',
                'mimes:pdf,doc,docx',
                'mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'max:5120',
            ],
        ], messages: [
            'curriculo.mimes' => 'O currículo deve ser PDF, DOC ou DOCX.',
            'curriculo.max' => 'O currículo deve ter no máximo 5 MB.',
            'pontuacao.max' => 'A pontuação vai de 0 a 100.',
        ], attributes: [
            'vaga_id' => 'vaga',
        ]);

        try {
            if ($this->editando) {
                $candidato = Candidato::findOrFail($this->editingId);
                $this->authorize('update', $candidato);
                $candidato = $service->atualizar($candidato, $data, $this->curriculo);
                $this->dispatch('toast', type: 'success', message: "Candidato {$candidato->nome} atualizado.");
            } else {
                $this->authorize('create', Candidato::class);
                $candidato = $service->criar($data, $this->curriculo);
                $this->dispatch('toast', type: 'success', message: "Candidato {$candidato->nome} cadastrado.");
            }
            $this->closeModal();
        } catch (\Throwable $e) {
            \Log::error('Erro ao salvar candidato', ['erro' => $e->getMessage()]);
            if (app()->environment('testing', 'local')) {
                throw $e;
            }
            $this->dispatch('toast', type: 'error', message: 'Erro ao salvar candidato.');
        }
    }

    /**
     * Altera o status do candidato (avança no workflow).
     */
    public function alterarStatus(int $id, string $novoStatus, CandidatoService $service): void
    {
        $c = Candidato::findOrFail($id);
        $this->authorize('update', $c);

        try {
            $service->alterarStatus($c, $novoStatus);
            $this->dispatch('toast', type: 'success',
                message: "Candidato {$c->nome}: status atualizado para {$novoStatus}.");
        } catch (\Throwable $e) {
            if (app()->environment('testing', 'local')) {
                throw $e;
            }
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    public function confirmDelete(int $id): void
    {
        $c = Candidato::findOrFail($id);
        $this->authorize('delete', $c);
        $this->deletingId = $id;
        $this->deletingName = $c->nome;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $c = Candidato::findOrFail($this->deletingId);
        $this->authorize('delete', $c);
        $c->delete();
        $this->dispatch('toast', type: 'success', message: "Candidato {$c->nome} excluído.");
        $this->showDeleteModal = false;
    }

    private function resetForm(): void
    {
        $this->reset([
            'vaga_id', 'nome', 'cpf', 'email', 'telefone', 'experiencia',
            'pontuacao', 'observacoes', 'curriculo', 'editingId', 'editando',
        ]);
        $this->status = Candidato::STATUS_INSCRITO;
        $this->resetErrorBag();
    }

    public function render()
    {
        $query = Candidato::query()
            ->with('vaga:id,titulo')
            ->when($this->filterVagaId, fn (Builder $q) => $q->where('vaga_id', $this->filterVagaId))
            ->when($this->filterStatus !== '', fn (Builder $q) => $q->where('status', $this->filterStatus))
            ->when($this->search !== '', fn (Builder $q) => $q->buscar($this->search))
            ->orderByDesc('created_at');

        return view('livewire.recrutamento.candidatos', [
            'candidatos' => $query->paginate(15),
            'vagas' => Vaga::orderBy('titulo')->get(['id', 'titulo', 'status']),
            'statuses' => Candidato::statusesComLabel(),
            'vagaFiltrada' => $this->filterVagaId ? Vaga::find($this->filterVagaId) : null,
        ]);
    }
}
