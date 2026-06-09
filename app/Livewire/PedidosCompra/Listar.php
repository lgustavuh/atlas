<?php

declare(strict_types=1);

namespace App\Livewire\PedidosCompra;

use App\Exports\PedidosCompraExport;
use App\Livewire\Concerns\ExportaExcel;
use App\Models\PedidoCompra;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Pedidos de Compra')]
class Listar extends Component
{
    use ExportaExcel;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $filterStatus = '';

    public function mount(): void
    {
        $this->authorize('viewAny', PedidoCompra::class);
    }

    public function updating(string $name): void
    {
        if (in_array($name, ['search', 'filterStatus'])) {
            $this->resetPage();
        }
    }

    /**
     * @return \Closure(Builder): Builder
     */
    protected function aplicarFiltros(): \Closure
    {
        return function (Builder $q): Builder {
            return $q
                ->when($this->search !== '', fn (Builder $q) => $q->buscar($this->search))
                ->when($this->filterStatus !== '', fn (Builder $q) => $q->where('status', $this->filterStatus));
        };
    }

    public function exportar()
    {
        $this->authorize('viewAny', PedidoCompra::class);
        return $this->fazerDownload(new PedidosCompraExport($this->aplicarFiltros()), 'pedidos-compra');
    }

    public function render()
    {
        $query = PedidoCompra::query()
            ->with(['fornecedor:id,razao_social,nome_fantasia', 'solicitante:id,nome'])
            ->withCount('itens')
            ->orderByDesc('data_pedido')
            ->orderByDesc('id');

        $query = ($this->aplicarFiltros())($query);

        return view('livewire.pedidos-compra.listar', [
            'pedidos' => $query->paginate(15),
            'stats' => [
                'pendentes' => PedidoCompra::pendentesAprovacao()->count(),
                'rascunhos' => PedidoCompra::comStatus(PedidoCompra::STATUS_RASCUNHO)->count(),
            ],
        ]);
    }
}
