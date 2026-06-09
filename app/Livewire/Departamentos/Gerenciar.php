<?php

declare(strict_types=1);

namespace App\Livewire\Departamentos;

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
 * Gestão de departamentos com estrutura hierárquica.
 *
 * Cuidado especial: previne ciclos na hierarquia (departamento não pode
 * ser pai de si mesmo ou de um antecessor).
 */
#[Layout('layouts.app')]
#[Title('Departamentos')]
class Gerenciar extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public bool $showModal = false;
    public bool $editando = false;
    public ?int $editingId = null;

    public string $nome = '';
    public string $sigla = '';
    public string $descricao = '';
    public ?int $departamento_pai_id = null;

    public bool $showDeleteModal = false;
    public ?int $deletingId = null;
    public string $deletingName = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Departamento::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->authorize('create', Departamento::class);
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $dep = Departamento::findOrFail($id);
        $this->authorize('update', $dep);

        $this->editingId = $dep->id;
        $this->nome = $dep->nome;
        $this->sigla = (string) $dep->sigla;
        $this->descricao = (string) $dep->descricao;
        $this->departamento_pai_id = $dep->departamento_pai_id;
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
            'nome' => ['required', 'string', 'min:2', 'max:150'],
            'sigla' => ['nullable', 'string', 'max:20'],
            'descricao' => ['nullable', 'string', 'max:1000'],
            'departamento_pai_id' => [
                'nullable', 'integer',
                Rule::exists('departamentos', 'id')->whereNull('deleted_at'),
            ],
        ]);

        // Validação anti-ciclo
        if ($this->editando && $data['departamento_pai_id'] !== null) {
            $depAtual = Departamento::findOrFail($this->editingId);
            if ($depAtual->criariaCiclo($data['departamento_pai_id'])) {
                $this->addError('departamento_pai_id', 'Esta seleção criaria um ciclo na hierarquia.');
                return;
            }
        }

        if ($this->editando) {
            $dep = Departamento::findOrFail($this->editingId);
            $this->authorize('update', $dep);
            $dep->update([
                'nome' => $data['nome'],
                'sigla' => $data['sigla'] ?: null,
                'descricao' => $data['descricao'] ?: null,
                'departamento_pai_id' => $data['departamento_pai_id'],
                'updated_by' => Auth::id(),
            ]);
            $this->dispatch('toast', type: 'success', message: "Departamento {$dep->nome} atualizado.");
        } else {
            $this->authorize('create', Departamento::class);
            $dep = Departamento::create([
                'nome' => $data['nome'],
                'sigla' => $data['sigla'] ?: null,
                'descricao' => $data['descricao'] ?: null,
                'departamento_pai_id' => $data['departamento_pai_id'],
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
            $this->dispatch('toast', type: 'success', message: "Departamento {$dep->nome} criado.");
        }

        $this->closeModal();
    }

    public function confirmDelete(int $id): void
    {
        $dep = Departamento::withCount(['colaboradores', 'subDepartamentos'])->findOrFail($id);
        $this->authorize('delete', $dep);

        $bloqueios = [];
        if ($dep->colaboradores_count > 0) {
            $bloqueios[] = "{$dep->colaboradores_count} colaboradores";
        }
        if ($dep->sub_departamentos_count > 0) {
            $bloqueios[] = "{$dep->sub_departamentos_count} subdepartamentos";
        }

        if (!empty($bloqueios)) {
            $this->dispatch('toast',
                type: 'error',
                message: 'Não é possível excluir: o departamento tem ' . implode(' e ', $bloqueios) . '.'
            );
            return;
        }

        $this->deletingId = $id;
        $this->deletingName = $dep->nome;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $dep = Departamento::findOrFail($this->deletingId);
        $this->authorize('delete', $dep);
        $dep->update(['updated_by' => Auth::id()]);
        $dep->delete();
        $this->dispatch('toast', type: 'success', message: "Departamento {$dep->nome} excluído.");
        $this->showDeleteModal = false;
    }

    private function resetForm(): void
    {
        $this->reset(['nome', 'sigla', 'descricao', 'departamento_pai_id', 'editingId', 'editando']);
        $this->resetErrorBag();
    }

    public function render()
    {
        $query = Departamento::query()
            ->with('departamentoPai:id,nome')
            ->withCount(['colaboradores', 'subDepartamentos'])
            ->when($this->search !== '', fn (Builder $q) => $q->buscar($this->search))
            ->orderBy('nome');

        // Para o select de "departamento pai", excluímos o próprio departamento
        // (mas só conseguimos filtrar descendentes em PHP, depois de carregar)
        $departamentosDisponiveis = Departamento::query()
            ->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))
            ->orderBy('nome')
            ->get(['id', 'nome', 'departamento_pai_id']);

        return view('livewire.departamentos.gerenciar', [
            'departamentos' => $query->paginate(20),
            'departamentosDisponiveis' => $departamentosDisponiveis,
        ]);
    }
}
