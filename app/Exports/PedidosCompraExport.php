<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\PedidoCompra;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PedidosCompraExport extends BaseExport
{
    public function __construct(
        protected ?\Closure $aplicarFiltros = null,
    ) {
        $this->aplicarFiltros ??= fn (Builder $q): Builder => $q;
    }

    public function title(): string
    {
        return 'Pedidos de Compra';
    }

    public function collection(): Collection
    {
        $query = PedidoCompra::query()
            ->with(['fornecedor:id,razao_social,nome_fantasia', 'solicitante:id,nome'])
            ->orderByDesc('created_at');

        return (($this->aplicarFiltros)($query))->get();
    }

    public function headings(): array
    {
        return [
            'Número',
            'Fornecedor',
            'Solicitante',
            'Justificativa',
            'Data emissão',
            'Status',
            'Valor total',
        ];
    }

    /**
     * @param PedidoCompra $p
     */
    public function map($p): array
    {
        return [
            $p->numero ?? '',
            $p->fornecedor?->nome_fantasia ?: $p->fornecedor?->razao_social ?? '',
            $p->solicitante?->nome ?? '',
            $p->justificativa ?? '',
            $this->formatarData($p->created_at),
            $p->status,
            $this->formatarMoeda($p->valor_total),
        ];
    }
}
