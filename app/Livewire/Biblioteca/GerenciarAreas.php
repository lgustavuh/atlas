<?php

declare(strict_types=1);

namespace App\Livewire\Biblioteca;

use App\Models\BibliotecaArea;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Áreas da Biblioteca')]
class GerenciarAreas extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public bool $showModal = false;
    public bool $editando = false;
    public ?int $editingId = null;

    public string $nome = '';
    public string $descricao = '';

    public bool $showDeleteModal = false;
    public ?int $deletingId = null;
    public string $deletingName = '';

    public function mount(): void
    {
        $this->authorize('viewAny', BibliotecaArea::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->authorize('create', BibliotecaArea::class);
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $a = BibliotecaArea::findOrFail($id);
        $this->authorize('update', $a);

        $this->editingId = $a->id;
        $this->nome = $a->nome;
        $this->descricao = (string) $a->descricao;
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
            'descricao' => ['nullable', 'string', 'max:1000'],
        ]);

        $payload = [
            'nome' => $data['nome'],
            'descricao' => trim($data['descricao'] ?? '') === '' ? null : $data['descricao'],
        ];

        if ($this->editando) {
            $a = BibliotecaArea::findOrFail($this->editingId);
            $this->authorize('update', $a);
            $a->update($payload);
            $this->dispatch('toast', type: 'success', message: "Área '{$a->nome}' atualizada.");
        } else {
            $a = BibliotecaArea::create($payload);
            $this->dispatch('toast', type: 'success', message: "Área '{$a->nome}' criada.");
        }
        $this->closeModal();
    }

    public function confirmDelete(int $id): void
    {
        $a = BibliotecaArea::withCount('documentos')->findOrFail($id);
        $this->authorize('delete', $a);

        if ($a->documentos_count > 0) {
            $this->dispatch('toast', type: 'error',
                message: "Não é possível excluir: {$a->documentos_count} documento(s) usam esta área.");
            return;
        }

        $this->deletingId = $id;
        $this->deletingName = $a->nome;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $a = BibliotecaArea::findOrFail($this->deletingId);
        $this->authorize('delete', $a);

        if ($a->documentos()->count() > 0) {
            $this->dispatch('toast', type: 'error', message: 'Área em uso, não pode ser excluída.');
            return;
        }

        $a->delete();
        $this->dispatch('toast', type: 'success', message: "Área '{$a->nome}' excluída.");
        $this->showDeleteModal = false;
    }

    private function resetForm(): void
    {
        $this->reset(['nome', 'descricao', 'editingId', 'editando']);
        $this->resetErrorBag();
    }

    public function render()
    {
        $query = BibliotecaArea::query()
            ->withCount('documentos')
            ->when($this->search !== '', fn (Builder $q) => $q->buscar($this->search))
            ->orderBy('nome');

        return view('livewire.biblioteca.gerenciar-areas', [
            'areas' => $query->paginate(20),
        ]);
    }
}
