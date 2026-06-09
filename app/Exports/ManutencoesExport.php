<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\VeiculoManutencao;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ManutencoesExport extends BaseExport
{
    public function __construct(
        protected ?\Closure $aplicarFiltros = null,
    ) {
        $this->aplicarFiltros ??= fn (Builder $q): Builder => $q;
    }

    public function title(): string
    {
        return 'Manutenções';
    }

    public function collection(): Collection
    {
        $query = VeiculoManutencao::query()
            ->with(['veiculo:id,placa,marca,modelo', 'fornecedor:id,razao_social,nome_fantasia'])
            ->orderByDesc('data_manutencao');

        return (($this->aplicarFiltros)($query))->get();
    }

    public function headings(): array
    {
        return [
            'Data',
            'Veículo',
            'Placa',
            'Tipo',
            'KM no momento',
            'Descrição',
            'Oficina',
            'Nota fiscal',
            'Valor',
            'Próxima data',
            'Próxima KM',
        ];
    }

    /**
     * @param VeiculoManutencao $m
     */
    public function map($m): array
    {
        return [
            $this->formatarData($m->data_manutencao),
            ($m->veiculo?->marca ?? '') . ' ' . ($m->veiculo?->modelo ?? ''),
            $m->veiculo?->placa ?? '',
            $m->tipo_label,
            $m->km_no_momento ?? '',
            $m->descricao ?? '',
            $m->fornecedor?->nome_fantasia ?: $m->fornecedor?->razao_social ?? '',
            $m->nota_fiscal ?? '',
            $this->formatarMoeda($m->valor),
            $this->formatarData($m->proxima_manutencao_data),
            $m->proxima_manutencao_km ?? '',
        ];
    }
}
