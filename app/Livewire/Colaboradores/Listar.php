<?php

declare(strict_types=1);

namespace App\Livewire\Colaboradores;

use App\Exports\ColaboradoresExport;
use App\Livewire\Concerns\ExportaExcel;
use App\Models\Cargo;
use App\Models\Colaborador;
use App\Models\Departamento;
use App\Services\ColaboradorService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Listagem de colaboradores.
 *
 * Recursos:
 *   - Busca por nome/CPF/matrícula/email
 *   - Filtros por cargo, departamento, status
 *   - Paginação (preserva filtros na URL)
 *   - Modal de confirmação para desativar/reativar
 */
#[Layout('layouts.app')]
#[Title('Colaboradores')]
class Listar extends Component
{
    use ExportaExcel;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'cargo')]
    public string $filterCargo = '';

    #[Url(as: 'dep')]
    public string $filterDepartamento = '';

    #[Url(as: 'status')]
    public string $filterStatus = 'ativos';

    // Confirmação de exclusão/reativação
    public bool $showActionModal = false;
    public ?int $actionColaboradorId = null;
    public string $actionType = ''; // 'desativar' ou 'reativar'
    public string $actionColaboradorNome = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Colaborador::class);
    }

    public function updating(string $name): void
    {
        // Qualquer mudança de filtro reseta a página
        if (in_array($name, ['search', 'filterCargo', 'filterDepartamento', 'filterStatus'])) {
            $this->resetPage();
        }
    }

    public function confirmarDesativar(int $id): void
    {
        $colaborador = Colaborador::findOrFail($id);
        $this->authorize('delete', $colaborador);

        $this->actionColaboradorId = $id;
        $this->actionColaboradorNome = $colaborador->nome;
        $this->actionType = 'desativar';
        $this->showActionModal = true;
    }

    public function confirmarReativar(int $id): void
    {
        $colaborador = Colaborador::withTrashed()->findOrFail($id);
        $this->authorize('restore', $colaborador);

        $this->actionColaboradorId = $id;
        $this->actionColaboradorNome = $colaborador->nome;
        $this->actionType = 'reativar';
        $this->showActionModal = true;
    }

    public function executar(ColaboradorService $service): void
    {
        if ($this->actionType === 'desativar') {
            $colaborador = Colaborador::findOrFail($this->actionColaboradorId);
            $this->authorize('delete', $colaborador);
            $service->desativar($colaborador);
            $this->dispatch('toast', type: 'success', message: "Colaborador {$colaborador->nome} desativado.");
        } elseif ($this->actionType === 'reativar') {
            $colaborador = Colaborador::withTrashed()->findOrFail($this->actionColaboradorId);
            $this->authorize('restore', $colaborador);
            $service->reativar($colaborador);
            $this->dispatch('toast', type: 'success', message: "Colaborador {$colaborador->nome} reativado.");
        }

        $this->showActionModal = false;
        $this->actionColaboradorId = null;
    }

    public function limparFiltros(): void
    {
        $this->reset(['search', 'filterCargo', 'filterDepartamento']);
        $this->filterStatus = 'ativos';
        $this->resetPage();
    }

    /**
     * Constrói a closure de filtros para reuso (paginação e exportação).
     *
     * @return \Closure(Builder): Builder
     */
    protected function aplicarFiltros(): \Closure
    {
        return function (Builder $q): Builder {
            return $q
                ->when($this->filterStatus === 'inativos', fn (Builder $q) => $q->onlyTrashed())
                ->when($this->filterStatus === 'todos', fn (Builder $q) => $q->withTrashed())
                ->when($this->search !== '', fn (Builder $q) => $q->buscar($this->search))
                ->when($this->filterCargo !== '', fn (Builder $q) => $q->where('cargo_id', $this->filterCargo))
                ->when($this->filterDepartamento !== '', fn (Builder $q) => $q->where('departamento_id', $this->filterDepartamento));
        };
    }

    /**
     * Exporta a listagem atual (com filtros aplicados) para Excel.
     */
    public function exportar()
    {
        $this->authorize('viewAny', Colaborador::class);
        return $this->fazerDownload(
            new ColaboradoresExport($this->aplicarFiltros()),
            'colaboradores',
        );
    }

    public function render()
    {
        $podeVerSalario = Auth::user()->can('colaboradores.view-salary');

        $query = Colaborador::query()
            ->with(['cargo:id,nome', 'departamento:id,nome'])
            ->orderBy('nome');

        $query = ($this->aplicarFiltros())($query);

        return view('livewire.colaboradores.listar', [
            'colaboradores' => $query->paginate(20),
            'cargos' => Cargo::orderBy('nome')->get(['id', 'nome']),
            'departamentos' => Departamento::orderBy('nome')->get(['id', 'nome']),
            'podeVerSalario' => $podeVerSalario,
        ]);
    }
}
