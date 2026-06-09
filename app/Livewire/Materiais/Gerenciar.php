<?php

declare(strict_types=1);

namespace App\Livewire\Materiais;

use App\Models\GrupoMaterial;
use App\Models\Material;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Gestão de Materiais (almoxarifado).
 */
#[Layout('layouts.app')]
#[Title('Materiais')]
class Gerenciar extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'grupo')]
    public string $filterGrupo = '';

    #[Url(as: 'estoque')]
    public string $filterEstoque = '';

    // Modal
    public bool $showModal = false;
    public bool $editando = false;
    public ?int $editingId = null;

    // Form
    public string $codigo = '';
    public string $nome = '';
    public string $descricao = '';
    public ?int $grupo_id = null;
    public string $unidade_medida = 'UN';
    public ?float $estoque_atual = 0;
    public ?float $estoque_minimo = 0;
    public ?float $estoque_maximo = null;
    public ?float $preco_referencia = null;
    public string $localizacao_estoque = '';

    // Exclusão
    public bool $showDeleteModal = false;
    public ?int $deletingId = null;
    public string $deletingName = '';

    /** @var list<string> */
    public array $unidades = ['UN', 'PC', 'CX', 'KG', 'G', 'TON', 'L', 'ML', 'M', 'CM', 'M2', 'M3', 'PAR', 'DZ', 'MIL', 'SC', 'RL', 'FD'];

    public function mount(): void
    {
        $this->authorize('viewAny', Material::class);
    }

    public function updating(string $name): void
    {
        if (in_array($name, ['search', 'filterGrupo', 'filterEstoque'])) {
            $this->resetPage();
        }
    }

    public function openCreate(): void
    {
        $this->authorize('create', Material::class);
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $m = Material::findOrFail($id);
        $this->authorize('update', $m);

        $this->editingId = $m->id;
        $this->codigo = (string) $m->codigo;
        $this->nome = $m->nome;
        $this->descricao = (string) $m->descricao;
        $this->grupo_id = $m->grupo_id;
        $this->unidade_medida = $m->unidade_medida;
        $this->estoque_atual = (float) $m->estoque_atual;
        $this->estoque_minimo = (float) $m->estoque_minimo;
        $this->estoque_maximo = $m->estoque_maximo !== null ? (float) $m->estoque_maximo : null;
        $this->preco_referencia = $m->preco_referencia !== null ? (float) $m->preco_referencia : null;
        $this->localizacao_estoque = (string) $m->localizacao_estoque;
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
            'codigo' => [
                'nullable', 'string', 'max:30',
                Rule::unique('materiais', 'codigo')
                    ->ignore($this->editingId)
                    ->whereNull('deleted_at'),
            ],
            'nome' => ['required', 'string', 'min:2', 'max:200'],
            'descricao' => ['nullable', 'string', 'max:2000'],
            'grupo_id' => ['nullable', 'integer', 'exists:grupos_materiais,id'],
            'unidade_medida' => ['required', 'string', Rule::in($this->unidades)],
            'estoque_atual' => ['required', 'numeric', 'min:0'],
            'estoque_minimo' => ['required', 'numeric', 'min:0'],
            'estoque_maximo' => ['nullable', 'numeric', 'min:0', 'gte:estoque_minimo'],
            'preco_referencia' => ['nullable', 'numeric', 'min:0'],
            'localizacao_estoque' => ['nullable', 'string', 'max:100'],
        ], messages: [
            'codigo.unique' => 'Já existe um material com este código.',
            'estoque_maximo.gte' => 'O estoque máximo deve ser maior ou igual ao mínimo.',
        ]);

        $payload = [
            'codigo' => $data['codigo'] ?: null,
            'nome' => $data['nome'],
            'descricao' => $data['descricao'] ?: null,
            'grupo_id' => $data['grupo_id'] ?: null,
            'unidade_medida' => $data['unidade_medida'],
            'estoque_atual' => $data['estoque_atual'],
            'estoque_minimo' => $data['estoque_minimo'],
            'estoque_maximo' => $data['estoque_maximo'],
            'preco_referencia' => $data['preco_referencia'],
            'localizacao_estoque' => $data['localizacao_estoque'] ?: null,
            'updated_by' => Auth::id(),
        ];

        if ($this->editando) {
            $m = Material::findOrFail($this->editingId);
            $this->authorize('update', $m);
            $m->update($payload);
            $this->dispatch('toast', type: 'success', message: "Material {$m->nome} atualizado.");
        } else {
            $payload['created_by'] = Auth::id();
            $m = Material::create($payload);
            $this->dispatch('toast', type: 'success', message: "Material {$m->nome} cadastrado.");
        }

        $this->closeModal();
    }

    public function confirmDelete(int $id): void
    {
        $m = Material::withCount('itensPedido')->findOrFail($id);
        $this->authorize('delete', $m);

        if ($m->itens_pedido_count > 0) {
            $this->dispatch('toast', type: 'error',
                message: "Não é possível excluir: material usado em {$m->itens_pedido_count} itens de pedidos.");
            return;
        }

        $this->deletingId = $id;
        $this->deletingName = $m->nome;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $m = Material::findOrFail($this->deletingId);
        $this->authorize('delete', $m);

        if ($m->itensPedido()->count() > 0) {
            $this->dispatch('toast', type: 'error', message: 'Material em uso, não pode ser excluído.');
            return;
        }

        $m->update(['updated_by' => Auth::id()]);
        $m->delete();
        $this->dispatch('toast', type: 'success', message: "Material {$m->nome} excluído.");
        $this->showDeleteModal = false;
    }

    private function resetForm(): void
    {
        $this->reset([
            'codigo', 'nome', 'descricao', 'grupo_id', 'estoque_maximo',
            'preco_referencia', 'localizacao_estoque', 'editingId', 'editando',
        ]);
        $this->unidade_medida = 'UN';
        $this->estoque_atual = 0;
        $this->estoque_minimo = 0;
        $this->resetErrorBag();
    }

    public function render()
    {
        $query = Material::query()
            ->with('grupo:id,nome')
            ->withCount('itensPedido')
            ->when($this->search !== '', fn (Builder $q) => $q->buscar($this->search))
            ->when($this->filterGrupo !== '', fn (Builder $q) => $q->where('grupo_id', $this->filterGrupo))
            ->when($this->filterEstoque === 'baixo', fn (Builder $q) => $q->abaixoDoMinimo())
            ->orderBy('nome');

        return view('livewire.materiais.gerenciar', [
            'materiais' => $query->paginate(20),
            'grupos' => GrupoMaterial::orderBy('nome')->get(['id', 'nome']),
            'totalAbaixoMinimo' => Material::abaixoDoMinimo()->count(),
        ]);
    }
}
