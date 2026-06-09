<?php

declare(strict_types=1);

namespace App\Livewire\Cargos;

use App\Models\Cargo;
use App\Models\Departamento;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * CRUD de Cargos - inline com modal.
 *
 * Diferente de Colaboradores (que tem página própria), Cargos é simples
 * o suficiente para CRUD inline. Padrão para módulos pequenos.
 */
#[Layout('layouts.app')]
#[Title('Cargos')]
class Gerenciar extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'dep')]
    public string $filterDepartamento = '';

    // Modal
    public bool $showModal = false;
    public bool $editando = false;
    public ?int $editingId = null;

    // Form
    public string $nome = '';
    public string $cbo = '';
    public string $descricao = '';
    public string $atribuicoes = '';
    public string $requisitos = '';
    public ?float $salario_minimo = null;
    public ?float $salario_maximo = null;
    public ?int $departamento_id = null;

    // Confirmação exclusão
    public bool $showDeleteModal = false;
    public ?int $deletingId = null;
    public string $deletingName = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Cargo::class);
    }

    public function updating(string $name): void
    {
        if (in_array($name, ['search', 'filterDepartamento'])) {
            $this->resetPage();
        }
    }

    public function openCreate(): void
    {
        $this->authorize('create', Cargo::class);
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $cargo = Cargo::findOrFail($id);
        $this->authorize('update', $cargo);

        $this->editingId = $cargo->id;
        $this->nome = $cargo->nome;
        $this->cbo = (string) $cargo->cbo;
        $this->descricao = (string) $cargo->descricao;
        $this->atribuicoes = (string) $cargo->atribuicoes;
        $this->requisitos = (string) $cargo->requisitos;
        $this->salario_minimo = $cargo->salario_minimo ? (float) $cargo->salario_minimo : null;
        $this->salario_maximo = $cargo->salario_maximo ? (float) $cargo->salario_maximo : null;
        $this->departamento_id = $cargo->departamento_id;
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
            'nome' => [
                'required', 'string', 'min:2', 'max:150',
                Rule::unique('cargos', 'nome')
                    ->ignore($this->editingId)
                    ->whereNull('deleted_at'),
            ],
            'cbo' => ['nullable', 'string', 'max:10'],
            'descricao' => ['nullable', 'string', 'max:1000'],
            'atribuicoes' => ['nullable', 'string', 'max:2000'],
            'requisitos' => ['nullable', 'string', 'max:2000'],
            'salario_minimo' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'salario_maximo' => ['nullable', 'numeric', 'min:0', 'max:999999.99', 'gte:salario_minimo'],
            'departamento_id' => ['nullable', 'integer', 'exists:departamentos,id'],
        ], messages: [
            'nome.unique' => 'Já existe um cargo com este nome.',
            'salario_maximo.gte' => 'O salário máximo deve ser maior ou igual ao mínimo.',
        ]);

        if ($this->editando) {
            $cargo = Cargo::findOrFail($this->editingId);
            $this->authorize('update', $cargo);

            $cargo->update($this->prepararDados($data));
            $this->dispatch('toast', type: 'success', message: "Cargo {$cargo->nome} atualizado.");
        } else {
            $this->authorize('create', Cargo::class);

            $cargo = Cargo::create($this->prepararDados($data) + [
                'created_by' => Auth::id(),
            ]);
            $this->dispatch('toast', type: 'success', message: "Cargo {$cargo->nome} criado.");
        }

        $this->closeModal();
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function prepararDados(array $data): array
    {
        return [
            'nome' => $data['nome'],
            'cbo' => $data['cbo'] ?: null,
            'descricao' => $data['descricao'] ?: null,
            'atribuicoes' => $data['atribuicoes'] ?: null,
            'requisitos' => $data['requisitos'] ?: null,
            'salario_minimo' => $data['salario_minimo'],
            'salario_maximo' => $data['salario_maximo'],
            'departamento_id' => $data['departamento_id'] ?: null,
            'updated_by' => Auth::id(),
        ];
    }

    public function confirmDelete(int $id): void
    {
        $cargo = Cargo::withCount('colaboradores')->findOrFail($id);
        $this->authorize('delete', $cargo);

        if ($cargo->colaboradores_count > 0) {
            $this->dispatch('toast',
                type: 'error',
                message: "Não é possível excluir: {$cargo->colaboradores_count} colaboradores usam este cargo."
            );
            return;
        }

        $this->deletingId = $id;
        $this->deletingName = $cargo->nome;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $cargo = Cargo::findOrFail($this->deletingId);
        $this->authorize('delete', $cargo);

        if ($cargo->colaboradores()->count() > 0) {
            $this->dispatch('toast', type: 'error', message: 'Cargo em uso, não pode ser excluído.');
            return;
        }

        $cargo->update(['updated_by' => Auth::id()]);
        $cargo->delete();
        $this->dispatch('toast', type: 'success', message: "Cargo {$cargo->nome} excluído.");
        $this->showDeleteModal = false;
    }

    private function resetForm(): void
    {
        $this->reset([
            'nome', 'cbo', 'descricao', 'atribuicoes', 'requisitos',
            'salario_minimo', 'salario_maximo', 'departamento_id',
            'editingId', 'editando',
        ]);
        $this->resetErrorBag();
    }

    public function render()
    {
        $query = Cargo::query()
            ->with('departamento:id,nome')
            ->withCount('colaboradores')
            ->when($this->search !== '', fn (Builder $q) => $q->buscar($this->search))
            ->when($this->filterDepartamento !== '', fn (Builder $q) => $q->where('departamento_id', $this->filterDepartamento))
            ->orderBy('nome');

        return view('livewire.cargos.gerenciar', [
            'cargos' => $query->paginate(20),
            'departamentos' => Departamento::orderBy('nome')->get(['id', 'nome']),
        ]);
    }
}
