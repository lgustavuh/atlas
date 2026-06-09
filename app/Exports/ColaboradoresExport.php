<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Colaborador;
use App\Rules\Cpf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Export de Colaboradores.
 *
 * Aceita callback de filtros para reaproveitar os mesmos filtros aplicados na tela.
 */
class ColaboradoresExport extends BaseExport
{
    /**
     * @param \Closure(Builder): Builder $aplicarFiltros
     */
    public function __construct(
        protected ?\Closure $aplicarFiltros = null,
    ) {
        $this->aplicarFiltros ??= fn (Builder $q): Builder => $q;
    }

    public function title(): string
    {
        return 'Colaboradores';
    }

    public function collection(): Collection
    {
        $query = Colaborador::query()
            ->with(['cargo:id,nome', 'departamento:id,nome'])
            ->orderBy('nome');

        $filtrada = ($this->aplicarFiltros)($query);

        return $filtrada->get();
    }

    public function headings(): array
    {
        return [
            'Matrícula',
            'Nome',
            'CPF',
            'PIS',
            'E-mail',
            'Celular',
            'Cargo',
            'Departamento',
            'Data admissão',
            'Data demissão',
            'Situação',
            'Salário',
        ];
    }

    /**
     * @param Colaborador $colab
     */
    public function map($colab): array
    {
        return [
            $colab->matricula ?? '',
            $colab->nome,
            Cpf::formatar($colab->cpf ?? ''),
            $colab->pis ?? '',
            $colab->email_pessoal ?? '',
            $colab->telefone_celular ?? '',
            $colab->cargo?->nome ?? '',
            $colab->departamento?->nome ?? '',
            $this->formatarData($colab->data_admissao),
            $this->formatarData($colab->data_demissao),
            $colab->data_demissao ? 'Desligado' : 'Ativo',
            $this->formatarMoeda($colab->salario),
        ];
    }
}
