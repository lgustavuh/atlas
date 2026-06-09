<?php

declare(strict_types=1);

namespace App\Livewire\Veiculos;

use App\Exports\ManutencoesExport;
use App\Livewire\Concerns\ExportaExcel;
use App\Models\Fornecedor;
use App\Models\Veiculo;
use App\Models\VeiculoManutencao;
use App\Services\ManutencaoService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Gestão de manutenções de veículos.
 *
 * Pode ser filtrada por veículo via query string (?veiculo=ID).
 */
#[Layout('layouts.app')]
#[Title('Manutenções de Veículos')]
class Manutencoes extends Component
{
    use ExportaExcel;
    use WithFileUploads;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'veiculo')]
    public ?int $filterVeiculoId = null;

    #[Url(as: 'tipo')]
    public string $filterTipo = '';

    // Modal
    public bool $showModal = false;
    public bool $editando = false;
    public ?int $editingId = null;

    // Form
    public ?int $veiculo_id = null;
    public string $tipo = '';
    public ?string $data_manutencao = null;
    public ?int $km_no_momento = null;
    public string $descricao = '';
    public string $servicos_realizados = '';
    public ?int $fornecedor_id = null;
    public ?float $valor = null;
    public string $nota_fiscal = '';
    public ?string $proxima_manutencao_data = null;
    public ?int $proxima_manutencao_km = null;
    public $comprovante = null;
    public bool $removerComprovante = false;

    // Exclusão
    public bool $showDeleteModal = false;
    public ?int $deletingId = null;

    public function mount(): void
    {
        $this->authorize('viewAny', VeiculoManutencao::class);
    }

    public function updating(string $name): void
    {
        if (in_array($name, ['search', 'filterVeiculoId', 'filterTipo'])) {
            $this->resetPage();
        }
    }

    public function openCreate(): void
    {
        $this->authorize('create', VeiculoManutencao::class);
        $this->resetForm();
        // Se já está filtrando por um veículo, pré-seleciona
        if ($this->filterVeiculoId) {
            $this->veiculo_id = $this->filterVeiculoId;
            $veiculo = Veiculo::find($this->filterVeiculoId);
            $this->km_no_momento = $veiculo?->km_atual;
        }
        $this->data_manutencao = now()->toDateString();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $m = VeiculoManutencao::findOrFail($id);
        $this->authorize('update', $m);

        $this->editingId = $m->id;
        $this->veiculo_id = $m->veiculo_id;
        $this->tipo = $m->tipo;
        $this->data_manutencao = $m->data_manutencao?->toDateString();
        $this->km_no_momento = $m->km_no_momento;
        $this->descricao = $m->descricao;
        $this->servicos_realizados = (string) $m->servicos_realizados;
        $this->fornecedor_id = $m->fornecedor_id;
        $this->valor = $m->valor !== null ? (float) $m->valor : null;
        $this->nota_fiscal = (string) $m->nota_fiscal;
        $this->proxima_manutencao_data = $m->proxima_manutencao_data?->toDateString();
        $this->proxima_manutencao_km = $m->proxima_manutencao_km;
        $this->editando = true;
        $this->showModal = true;
    }

    /**
     * Quando seleciona um veículo no modal, sugere o km atual.
     */
    public function updatedVeiculoId($value): void
    {
        if ($value && !$this->editando) {
            $veiculo = Veiculo::find($value);
            if ($veiculo && !$this->km_no_momento) {
                $this->km_no_momento = $veiculo->km_atual;
            }
        }
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function save(ManutencaoService $service): void
    {
        $data = $this->validate([
            'veiculo_id' => ['required', 'integer', 'exists:veiculos,id'],
            'tipo' => ['required', Rule::in(array_keys(VeiculoManutencao::tiposComLabel()))],
            'data_manutencao' => ['required', 'date'],
            'km_no_momento' => ['nullable', 'integer', 'min:0'],
            'descricao' => ['required', 'string', 'min:5', 'max:2000'],
            'servicos_realizados' => ['nullable', 'string', 'max:2000'],
            'fornecedor_id' => ['nullable', 'integer', 'exists:fornecedores,id'],
            'valor' => ['nullable', 'numeric', 'min:0'],
            'nota_fiscal' => ['nullable', 'string', 'max:30'],
            'proxima_manutencao_data' => ['nullable', 'date', 'after:data_manutencao'],
            'proxima_manutencao_km' => ['nullable', 'integer', 'gt:km_no_momento'],
            'comprovante' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'mimetypes:application/pdf,image/jpeg,image/png', 'max:5120'],
        ], messages: [
            'descricao.min' => 'A descrição precisa ter pelo menos 5 caracteres.',
            'proxima_manutencao_data.after' => 'A próxima manutenção deve ser após a data atual.',
            'proxima_manutencao_km.gt' => 'O KM da próxima manutenção deve ser maior que o KM atual.',
            'comprovante.max' => 'O comprovante deve ter no máximo 5 MB.',
        ], attributes: [
            'veiculo_id' => 'veículo',
            'fornecedor_id' => 'fornecedor (oficina)',
            'km_no_momento' => 'KM no momento',
        ]);

        try {
            if ($this->editando) {
                $manutencao = VeiculoManutencao::findOrFail($this->editingId);
                $this->authorize('update', $manutencao);
                $manutencao = $service->atualizar($manutencao, $data, $this->comprovante);
                $this->dispatch('toast', type: 'success', message: 'Manutenção atualizada.');
            } else {
                $this->authorize('create', VeiculoManutencao::class);
                $manutencao = $service->criar($data, $this->comprovante);
                $this->dispatch('toast', type: 'success', message: 'Manutenção registrada.');
            }
            $this->closeModal();
        } catch (\Throwable $e) {
            \Log::error('Erro ao salvar manutenção', ['erro' => $e->getMessage()]);
            if (app()->environment('testing', 'local')) {
                throw $e;
            }
            $this->dispatch('toast', type: 'error', message: 'Erro ao salvar manutenção.');
        }
    }

    public function confirmDelete(int $id): void
    {
        $m = VeiculoManutencao::findOrFail($id);
        $this->authorize('delete', $m);
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(ManutencaoService $service): void
    {
        $m = VeiculoManutencao::findOrFail($this->deletingId);
        $this->authorize('delete', $m);
        $service->excluir($m);
        $this->dispatch('toast', type: 'success', message: 'Manutenção excluída.');
        $this->showDeleteModal = false;
    }

    private function resetForm(): void
    {
        $this->reset([
            'veiculo_id', 'tipo', 'data_manutencao', 'km_no_momento',
            'descricao', 'servicos_realizados', 'fornecedor_id', 'valor',
            'nota_fiscal', 'proxima_manutencao_data', 'proxima_manutencao_km',
            'comprovante', 'removerComprovante', 'editingId', 'editando',
        ]);
        $this->resetErrorBag();
    }

    /**
     * @return \Closure(Builder): Builder
     */
    protected function aplicarFiltros(): \Closure
    {
        return function (Builder $q): Builder {
            return $q
                ->when($this->filterVeiculoId, fn (Builder $q) => $q->where('veiculo_id', $this->filterVeiculoId))
                ->when($this->filterTipo !== '', fn (Builder $q) => $q->where('tipo', $this->filterTipo))
                ->when($this->search !== '', function (Builder $q): void {
                    $like = '%' . strtolower($this->search) . '%';
                    $q->whereRaw('LOWER(descricao) LIKE ?', [$like]);
                });
        };
    }

    public function exportar()
    {
        $this->authorize('viewAny', VeiculoManutencao::class);
        return $this->fazerDownload(new ManutencoesExport($this->aplicarFiltros()), 'manutencoes');
    }

    public function render()
    {
        $query = VeiculoManutencao::query()
            ->with(['veiculo:id,placa,marca,modelo', 'fornecedor:id,razao_social,nome_fantasia'])
            ->orderByDesc('data_manutencao');

        $query = ($this->aplicarFiltros())($query);

        return view('livewire.veiculos.manutencoes', [
            'manutencoes' => $query->paginate(15),
            'veiculos' => Veiculo::orderBy('placa')->get(['id', 'placa', 'marca', 'modelo', 'km_atual']),
            'fornecedores' => Fornecedor::orderBy('razao_social')->get(['id', 'razao_social', 'nome_fantasia']),
            'tipos' => VeiculoManutencao::tiposComLabel(),
            'veiculoFiltrado' => $this->filterVeiculoId ? Veiculo::find($this->filterVeiculoId) : null,
        ]);
    }
}
