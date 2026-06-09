<?php

declare(strict_types=1);

namespace App\Livewire\Recrutamento;

use App\Models\Cargo;
use App\Models\Departamento;
use App\Models\Vaga;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Vagas de Emprego')]
class Vagas extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $filterStatus = '';

    public bool $showModal = false;
    public bool $editando = false;
    public ?int $editingId = null;

    public string $titulo = '';
    public ?int $cargo_id = null;
    public ?int $departamento_id = null;
    public string $descricao = '';
    public string $requisitos = '';
    public string $beneficios = '';
    public ?float $salario_de = null;
    public ?float $salario_ate = null;
    public bool $salario_publicar = false;
    public int $quantidade_vagas = 1;
    public ?string $data_abertura = null;
    public ?string $data_fechamento = null;
    public string $status = Vaga::STATUS_RASCUNHO;

    public bool $showDeleteModal = false;
    public ?int $deletingId = null;
    public string $deletingName = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Vaga::class);
    }

    public function updating(string $name): void
    {
        if (in_array($name, ['search', 'filterStatus'])) {
            $this->resetPage();
        }
    }

    public function openCreate(): void
    {
        $this->authorize('create', Vaga::class);
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $v = Vaga::findOrFail($id);
        $this->authorize('update', $v);

        $this->editingId = $v->id;
        $this->titulo = $v->titulo;
        $this->cargo_id = $v->cargo_id;
        $this->departamento_id = $v->departamento_id;
        $this->descricao = $v->descricao;
        $this->requisitos = (string) $v->requisitos;
        $this->beneficios = (string) $v->beneficios;
        $this->salario_de = $v->salario_de !== null ? (float) $v->salario_de : null;
        $this->salario_ate = $v->salario_ate !== null ? (float) $v->salario_ate : null;
        $this->salario_publicar = $v->salario_publicar;
        $this->quantidade_vagas = $v->quantidade_vagas ?? 1;
        $this->data_abertura = $v->data_abertura?->toDateString();
        $this->data_fechamento = $v->data_fechamento?->toDateString();
        $this->status = $v->status;

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
        $data = $this->validate([
            'titulo' => ['required', 'string', 'min:3', 'max:200'],
            'cargo_id' => ['nullable', 'integer', 'exists:cargos,id'],
            'departamento_id' => ['nullable', 'integer', 'exists:departamentos,id'],
            'descricao' => ['required', 'string', 'min:10', 'max:5000'],
            'requisitos' => ['nullable', 'string', 'max:3000'],
            'beneficios' => ['nullable', 'string', 'max:2000'],
            'salario_de' => ['nullable', 'numeric', 'min:0'],
            'salario_ate' => ['nullable', 'numeric', 'min:0', 'gte:salario_de'],
            'salario_publicar' => ['boolean'],
            'quantidade_vagas' => ['required', 'integer', 'min:1', 'max:999'],
            'data_abertura' => ['nullable', 'date'],
            'data_fechamento' => ['nullable', 'date', 'after_or_equal:data_abertura'],
            'status' => ['required', Rule::in(array_keys(Vaga::statusesComLabel()))],
        ], messages: [
            'descricao.min' => 'A descrição deve ter pelo menos 10 caracteres.',
            'salario_ate.gte' => 'O salário máximo deve ser maior ou igual ao mínimo.',
            'data_fechamento.after_or_equal' => 'A data de fechamento deve ser após a abertura.',
        ], attributes: [
            'cargo_id' => 'cargo',
            'departamento_id' => 'departamento',
        ]);

        $payload = [];
        foreach ($data as $k => $v) {
            $payload[$k] = is_string($v) && trim($v) === '' ? null : $v;
        }
        $payload['updated_by'] = Auth::id();

        if ($this->editando) {
            $vaga = Vaga::findOrFail($this->editingId);
            $this->authorize('update', $vaga);
            $vaga->update($payload);
            $this->dispatch('toast', type: 'success', message: "Vaga '{$vaga->titulo}' atualizada.");
        } else {
            $payload['created_by'] = Auth::id();
            $vaga = Vaga::create($payload);
            $this->dispatch('toast', type: 'success', message: "Vaga '{$vaga->titulo}' criada.");
        }

        $this->closeModal();
    }

    public function publicar(int $id): void
    {
        $v = Vaga::findOrFail($id);
        $this->authorize('update', $v);

        if ($v->status !== Vaga::STATUS_RASCUNHO) {
            $this->dispatch('toast', type: 'error', message: 'Vaga já foi publicada.');
            return;
        }

        $v->update(['status' => Vaga::STATUS_ABERTA, 'updated_by' => Auth::id()]);
        $this->dispatch('toast', type: 'success', message: "Vaga '{$v->titulo}' publicada.");
    }

    public function confirmDelete(int $id): void
    {
        $v = Vaga::findOrFail($id);
        $this->authorize('delete', $v);
        $this->deletingId = $id;
        $this->deletingName = $v->titulo;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $v = Vaga::findOrFail($this->deletingId);
        $this->authorize('delete', $v);
        $v->update(['updated_by' => Auth::id()]);
        $v->delete();
        $this->dispatch('toast', type: 'success', message: "Vaga '{$v->titulo}' excluída.");
        $this->showDeleteModal = false;
    }

    private function resetForm(): void
    {
        $this->reset([
            'titulo', 'cargo_id', 'departamento_id', 'descricao', 'requisitos',
            'beneficios', 'salario_de', 'salario_ate', 'data_abertura', 'data_fechamento',
            'editingId', 'editando',
        ]);
        $this->salario_publicar = false;
        $this->quantidade_vagas = 1;
        $this->status = Vaga::STATUS_RASCUNHO;
        $this->resetErrorBag();
    }

    public function render()
    {
        $query = Vaga::query()
            ->with(['cargo:id,nome', 'departamento:id,nome'])
            ->withCount('candidatos')
            ->when($this->search !== '', fn (Builder $q) => $q->buscar($this->search))
            ->when($this->filterStatus !== '', fn (Builder $q) => $q->where('status', $this->filterStatus))
            ->orderByRaw("CASE status
                WHEN 'aberta' THEN 1
                WHEN 'em_selecao' THEN 2
                WHEN 'rascunho' THEN 3
                WHEN 'preenchida' THEN 4
                ELSE 5 END")
            ->orderByDesc('created_at');

        return view('livewire.recrutamento.vagas', [
            'vagas' => $query->paginate(15),
            'cargos' => Cargo::orderBy('nome')->get(['id', 'nome']),
            'departamentos' => Departamento::orderBy('nome')->get(['id', 'nome']),
            'statuses' => Vaga::statusesComLabel(),
            'stats' => [
                'abertas' => Vaga::comStatus(Vaga::STATUS_ABERTA)->count(),
                'em_selecao' => Vaga::comStatus(Vaga::STATUS_EM_SELECAO)->count(),
                'candidatos_inscritos' => \App\Models\Candidato::comStatus(\App\Models\Candidato::STATUS_INSCRITO)->count(),
            ],
        ]);
    }
}
