<?php

declare(strict_types=1);

namespace App\Livewire\Classificacoes;

use App\Models\Classificacao;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Classificações')]
class Gerenciar extends Component
{
    #[Url(as: 'q')]
    public string $search = '';

    public bool $showModal = false;
    public bool $editando = false;
    public ?int $editingId = null;

    public string $nome = '';
    public string $cor_hex = '#3B82F6';
    public string $descricao = '';

    public bool $showDeleteModal = false;
    public ?int $deletingId = null;
    public string $deletingName = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Classificacao::class);
    }

    public function openCreate(): void
    {
        $this->authorize('create', Classificacao::class);
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $c = Classificacao::findOrFail($id);
        $this->authorize('update', $c);

        $this->editingId = $c->id;
        $this->nome = $c->nome;
        $this->cor_hex = $c->cor_hex ?: '#3B82F6';
        $this->descricao = (string) $c->descricao;
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
            'cor_hex' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'descricao' => ['nullable', 'string', 'max:500'],
        ], messages: [
            'cor_hex.regex' => 'Use formato #RRGGBB (ex: #3B82F6).',
        ]);

        if ($this->editando) {
            $c = Classificacao::findOrFail($this->editingId);
            $this->authorize('update', $c);
            $c->update([
                'nome' => $data['nome'],
                'cor_hex' => $data['cor_hex'],
                'descricao' => $data['descricao'] ?: null,
            ]);
            $this->dispatch('toast', type: 'success', message: "Classificação atualizada.");
        } else {
            $this->authorize('create', Classificacao::class);
            Classificacao::create([
                'nome' => $data['nome'],
                'cor_hex' => $data['cor_hex'],
                'descricao' => $data['descricao'] ?: null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
            $this->dispatch('toast', type: 'success', message: "Classificação criada.");
        }

        $this->closeModal();
    }

    public function confirmDelete(int $id): void
    {
        $c = Classificacao::withCount('colaboradores')->findOrFail($id);
        $this->authorize('delete', $c);

        if ($c->colaboradores_count > 0) {
            $this->dispatch('toast', type: 'error',
                message: "Não é possível excluir: {$c->colaboradores_count} colaboradores usam esta classificação.");
            return;
        }

        $this->deletingId = $id;
        $this->deletingName = $c->nome;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $c = Classificacao::findOrFail($this->deletingId);
        $this->authorize('delete', $c);
        $c->delete();
        $this->dispatch('toast', type: 'success', message: "Classificação excluída.");
        $this->showDeleteModal = false;
    }

    private function resetForm(): void
    {
        $this->reset(['nome', 'descricao', 'editingId', 'editando']);
        $this->cor_hex = '#3B82F6';
        $this->resetErrorBag();
    }

    public function render()
    {
        $classificacoes = Classificacao::query()
            ->withCount('colaboradores')
            ->when($this->search !== '', fn (Builder $q) => $q->whereRaw('LOWER(nome) LIKE ?', ['%' . strtolower($this->search) . '%']))
            ->orderBy('nome')
            ->get();

        return view('livewire.classificacoes.gerenciar', compact('classificacoes'));
    }
}
