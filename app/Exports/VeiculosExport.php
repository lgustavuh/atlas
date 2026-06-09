<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Veiculo;
use App\Rules\Placa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class VeiculosExport extends BaseExport
{
    public function __construct(
        protected ?\Closure $aplicarFiltros = null,
    ) {
        $this->aplicarFiltros ??= fn (Builder $q): Builder => $q;
    }

    public function title(): string
    {
        return 'Veículos';
    }

    public function collection(): Collection
    {
        $query = Veiculo::query()
            ->with('responsavel:id,nome')
            ->orderBy('marca')->orderBy('modelo');

        return (($this->aplicarFiltros)($query))->get();
    }

    public function headings(): array
    {
        return [
            'Placa',
            'Marca',
            'Modelo',
            'Ano fab/mod',
            'Cor',
            'Categoria',
            'Combustível',
            'KM atual',
            'Responsável',
            'Status',
            'Licenciamento',
            'Seguro',
            'Aluguel',
        ];
    }

    /**
     * @param Veiculo $v
     */
    public function map($v): array
    {
        return [
            Placa::formatar($v->placa ?? ''),
            $v->marca,
            $v->modelo,
            trim(($v->ano_fabricacao ?: '') . '/' . ($v->ano_modelo ?: ''), '/'),
            $v->cor ?? '',
            $v->categoria ?? '',
            $v->combustivel ?? '',
            $v->km_atual ?? 0,
            $v->responsavel?->nome ?? '',
            $v->status_label,
            $this->formatarData($v->licenciamento_vencimento),
            $this->formatarData($v->seguro_vencimento),
            $this->formatarMoeda($v->valor_aquisicao),
        ];
    }
}
