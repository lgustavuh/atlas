<?php

declare(strict_types=1);

namespace App\Livewire\Materiais;

use App\Models\GrupoMaterial;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Gestão de Grupos de Materiais (categorias do almoxarifado).
 *
 * Suporta hierarquia (grupo pai), com detecção de ciclo.
 */
#[Layout('layouts.app')]
#[Title('Grupos de Materiais')]
class GerenciarGrupos extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public bool $showModal = false;
    public bool $editando = false;
    public ?int $editingId = null;

    public string $nome = '';
    public string $codigo = '';
    public string $descricao = '';
    public ?int $grupo_pai_id = null;

    public bool $showDeleteModal = false;
    public ?int $deletingId = null;
    public string $deletingName = '';

    public function mount(): void
    {
        $this->authorize('viewAny', GrupoMaterial::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->authorize('create', GrupoMaterial::class);
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $g = GrupoMaterial::findOrFail($id);
        $this->authorize('update', $g);

        $this->editingId = $g->id;
        $this->nome = $g->nome;
        $this->codigo = (string) $g->codigo;
        $this->descricao = (string) $g->descricao;
        $this->grupo_pai_id = $g->grupo_pai_id;
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
            'nome' => ['required', 'string', 'min:2', 'max:100'],
            'codigo' => ['nullable', 'string', 'max:20'],
            'descricao' => ['nullable', 'string', 'max:1000'],
            'grupo_pai_id' => ['nullable', 'integer', 'exists:grupos_materiais,id'],
        ]);

        // Anti-ciclo
        if ($this->editando && $data['grupo_pai_id'] !== null) {
            $grupo = GrupoMaterial::findOrFail($this->editingId);
            if ($grupo->criariaCiclo($data['grupo_pai_id'])) {
                $this->addError('grupo_pai_id', 'Esta seleção criaria um ciclo na hierarquia.');
                return;
            }
        }

        $payload = [
            'nome' => $data['nome'],
            'codigo' => $data['codigo'] ?: null,
            'descricao' => $data['descricao'] ?: null,
            'grupo_pai_id' => $data['grupo_pai_id'],
            'updated_by' => Auth::id(),
        ];

        if ($this->editando) {
            $g = GrupoMaterial::findOrFail($this->editingId);
            $this->authorize('update', $g);
            $g->update($payload);
            $this->dispatch('toast', type: 'success', message: "Grupo {$g->nome} atualizado.");
        } else {
            $payload['created_by'] = Auth::id();
            $g = GrupoMaterial::create($payload);
            $this->dispatch('toast', type: 'success', message: "Grupo {$g->nome} criado.");
        }

        $this->closeModal();
    }

    public function confirmDelete(int $id): void
    {
        $g = GrupoMaterial::withCount(['materiais', 'subGrupos'])->findOrFail($id);
        $this->authorize('delete', $g);

        $bloqueios = [];
        if ($g->materiais_count > 0) {
            $bloqueios[] = "{$g->materiais_count} materiais";
        }
        if ($g->sub_grupos_count > 0) {
            $bloqueios[] = "{$g->sub_grupos_count} subgrupos";
        }

        if (!empty($bloqueios)) {
            $this->dispatch('toast', type: 'error',
                message: 'Não é possível excluir: o grupo tem ' . implode(' e ', $bloqueios) . '.');
            return;
        }

        $this->deletingId = $id;
        $this->deletingName = $g->nome;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $g = GrupoMaterial::findOrFail($this->deletingId);
        $this->authorize('delete', $g);
        $g->update(['updated_by' => Auth::id()]);
        $g->delete();
        $this->dispatch('toast', type: 'success', message: "Grupo {$g->nome} excluído.");
        $this->showDeleteModal = false;
    }

    private function resetForm(): void
    {
        $this->reset(['nome', 'codigo', 'descricao', 'grupo_pai_id', 'editingId', 'editando']);
        $this->resetErrorBag();
    }

    public function render()
    {
        $query = GrupoMaterial::query()
            ->with('grupoPai:id,nome')
            ->withCount(['materiais', 'subGrupos'])
            ->when($this->search !== '', fn (Builder $q) => $q->buscar($this->search))
            ->orderBy('nome');

        return view('livewire.materiais.gerenciar-grupos', [
            'grupos' => $query->paginate(20),
            'gruposDisponiveis' => GrupoMaterial::query()
                ->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))
                ->orderBy('nome')
                ->get(['id', 'nome']),
        ]);
    }
}
