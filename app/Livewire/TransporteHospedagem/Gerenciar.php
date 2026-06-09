<?php

declare(strict_types=1);

namespace App\Livewire\TransporteHospedagem;

use App\Models\Colaborador;
use App\Models\Fornecedor;
use App\Models\Obra;
use App\Models\TransporteHospedagem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Transporte e Hospedagem')]
class Gerenciar extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'tipo')]
    public string $filterTipo = '';

    #[Url(as: 'colab')]
    public ?int $filterColaboradorId = null;

    #[Url(as: 'obra')]
    public ?int $filterObraId = null;

    public bool $showModal = false;
    public bool $editando = false;
    public ?int $editingId = null;

    // Form
    public string $tipo = TransporteHospedagem::TIPO_TRANSPORTE;
    public ?int $colaborador_id = null;
    public ?int $obra_id = null;
    public ?string $data_inicio = null;
    public ?string $data_fim = null;
    public string $origem = '';
    public string $destino = '';
    public string $meio_transporte = '';
    public string $hospedagem_local = '';
    public string $hospedagem_endereco = '';
    public ?int $hospedagem_cidade_id = null;
    public ?float $valor = null;
    public ?int $fornecedor_id = null;
    public string $observacoes = '';

    public bool $showDeleteModal = false;
    public ?int $deletingId = null;
    public string $deletingName = '';

    public function mount(): void
    {
        $this->authorize('viewAny', TransporteHospedagem::class);
    }

    public function updating(string $name): void
    {
        if (in_array($name, ['search', 'filterTipo', 'filterColaboradorId', 'filterObraId'])) {
            $this->resetPage();
        }
    }

    public function openCreate(): void
    {
        $this->authorize('create', TransporteHospedagem::class);
        $this->resetForm();
        $this->data_inicio = now()->toDateString();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $t = TransporteHospedagem::findOrFail($id);
        $this->authorize('update', $t);

        $this->editingId = $t->id;
        $this->tipo = $t->tipo;
        $this->colaborador_id = $t->colaborador_id;
        $this->obra_id = $t->obra_id;
        $this->data_inicio = $t->data_inicio?->toDateString();
        $this->data_fim = $t->data_fim?->toDateString();
        $this->origem = (string) $t->origem;
        $this->destino = (string) $t->destino;
        $this->meio_transporte = (string) $t->meio_transporte;
        $this->hospedagem_local = (string) $t->hospedagem_local;
        $this->hospedagem_endereco = (string) $t->hospedagem_endereco;
        $this->hospedagem_cidade_id = $t->hospedagem_cidade_id;
        $this->valor = $t->valor !== null ? (float) $t->valor : null;
        $this->fornecedor_id = $t->fornecedor_id;
        $this->observacoes = (string) $t->observacoes;

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
        $temTransporte = in_array($this->tipo, ['transporte', 'ambos'], true);
        $temHospedagem = in_array($this->tipo, ['hospedagem', 'ambos'], true);

        $rules = [
            'tipo' => ['required', Rule::in(array_keys(TransporteHospedagem::tiposComLabel()))],
            'colaborador_id' => ['required', 'integer', 'exists:colaboradores,id'],
            'obra_id' => ['nullable', 'integer', 'exists:obras,id'],
            'data_inicio' => ['required', 'date'],
            'data_fim' => ['nullable', 'date', 'after_or_equal:data_inicio'],
            'origem' => [$temTransporte ? 'required' : 'nullable', 'string', 'max:200'],
            'destino' => [$temTransporte ? 'required' : 'nullable', 'string', 'max:200'],
            'meio_transporte' => [$temTransporte ? 'required' : 'nullable',
                Rule::in(array_keys(TransporteHospedagem::meiosTransporteComLabel()))],
            'hospedagem_local' => [$temHospedagem ? 'required' : 'nullable', 'string', 'max:200'],
            'hospedagem_endereco' => ['nullable', 'string', 'max:300'],
            // Item 5: cidade da hospedagem (opcional, mas FK valida)
            'hospedagem_cidade_id' => ['nullable', 'integer', 'exists:cidades,id'],
            'valor' => ['nullable', 'numeric', 'min:0'],
            'fornecedor_id' => ['nullable', 'integer', 'exists:fornecedores,id'],
            'observacoes' => ['nullable', 'string', 'max:2000'],
        ];

        $data = $this->validate($rules, messages: [
            'origem.required' => 'A origem é obrigatória para transporte.',
            'destino.required' => 'O destino é obrigatório para transporte.',
            'meio_transporte.required' => 'O meio de transporte é obrigatório.',
            'hospedagem_local.required' => 'O local de hospedagem é obrigatório.',
            'data_fim.after_or_equal' => 'A data fim deve ser após a data início.',
        ], attributes: [
            'colaborador_id' => 'colaborador',
            'obra_id' => 'obra',
            'fornecedor_id' => 'fornecedor',
            'meio_transporte' => 'meio de transporte',
            'hospedagem_local' => 'local de hospedagem',
            'hospedagem_cidade_id' => 'cidade da hospedagem',
        ]);

        // Limpa campos não aplicáveis ao tipo (defesa contra dados zumbis ao mudar tipo no edit)
        if (!$temTransporte) {
            $data['origem'] = null;
            $data['destino'] = null;
            $data['meio_transporte'] = null;
        }
        if (!$temHospedagem) {
            $data['hospedagem_local'] = null;
            $data['hospedagem_endereco'] = null;
            $data['hospedagem_cidade_id'] = null;
        }

        // String vazia → null
        $payload = [];
        foreach ($data as $k => $v) {
            $payload[$k] = is_string($v) && trim($v) === '' ? null : $v;
        }
        $payload['updated_by'] = Auth::id();

        if ($this->editando) {
            $reg = TransporteHospedagem::findOrFail($this->editingId);
            $this->authorize('update', $reg);
            $reg->update($payload);
            $this->dispatch('toast', type: 'success', message: 'Registro atualizado.');
        } else {
            $payload['created_by'] = Auth::id();
            TransporteHospedagem::create($payload);
            $this->dispatch('toast', type: 'success', message: 'Registro cadastrado.');
        }

        $this->closeModal();
    }

    public function confirmDelete(int $id): void
    {
        $t = TransporteHospedagem::with('colaborador:id,nome')->findOrFail($id);
        $this->authorize('delete', $t);
        $this->deletingId = $id;
        $this->deletingName = "{$t->tipo_label} de {$t->colaborador?->nome}";
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $t = TransporteHospedagem::findOrFail($this->deletingId);
        $this->authorize('delete', $t);
        $t->update(['updated_by' => Auth::id()]);
        $t->delete();
        $this->dispatch('toast', type: 'success', message: 'Registro excluído.');
        $this->showDeleteModal = false;
    }

    private function resetForm(): void
    {
        $this->reset([
            'colaborador_id', 'obra_id', 'data_inicio', 'data_fim',
            'origem', 'destino', 'meio_transporte',
            'hospedagem_local', 'hospedagem_endereco', 'hospedagem_cidade_id',
            'valor', 'fornecedor_id', 'observacoes',
            'editingId', 'editando',
        ]);
        $this->tipo = TransporteHospedagem::TIPO_TRANSPORTE;
        $this->resetErrorBag();
    }

    public function render()
    {
        $query = TransporteHospedagem::query()
            ->with([
                'colaborador:id,nome',
                'obra:id,nome',
                'fornecedor:id,razao_social,nome_fantasia',
                'hospedagemCidade:id,nome,estado_id',
                'hospedagemCidade.estado:id,uf',
            ])
            ->when($this->search !== '', fn (Builder $q) => $q->buscar($this->search))
            ->when($this->filterTipo !== '', fn (Builder $q) => $q->where('tipo', $this->filterTipo))
            ->when($this->filterColaboradorId, fn (Builder $q) => $q->where('colaborador_id', $this->filterColaboradorId))
            ->when($this->filterObraId, fn (Builder $q) => $q->where('obra_id', $this->filterObraId))
            ->orderByDesc('data_inicio');

        return view('livewire.transporte-hospedagem.gerenciar', [
            'registros' => $query->paginate(15),
            'colaboradores' => Colaborador::ativos()->orderBy('nome')->get(['id', 'nome']),
            'obras' => Obra::orderBy('nome')->get(['id', 'nome']),
            'fornecedores' => Fornecedor::orderBy('razao_social')->get(['id', 'razao_social', 'nome_fantasia']),
            'cidades' => \App\Models\Cidade::query()
                ->with('estado:id,uf')
                ->orderBy('nome')
                ->get(['id', 'nome', 'estado_id']),
            'tipos' => TransporteHospedagem::tiposComLabel(),
            'meiosTransporte' => TransporteHospedagem::meiosTransporteComLabel(),
            'stats' => [
                'em_andamento' => TransporteHospedagem::emAndamento()->count(),
                'futuros' => TransporteHospedagem::whereDate('data_inicio', '>', now())->count(),
            ],
        ]);
    }
}
