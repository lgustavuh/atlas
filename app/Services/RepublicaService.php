<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Republica;
use App\Models\RepublicaOcupacao;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Regras de negócio de Repúblicas e Ocupações.
 */
class RepublicaService
{
    /**
     * Aloca um colaborador a uma república.
     *
     * Regras:
     *  - Não pode estourar capacidade
     *  - Mesmo colaborador não pode estar ativo em duas repúblicas ao mesmo tempo
     */
    public function alocar(Republica $republica, int $colaboradorId, string $dataEntrada, ?string $observacoes = null): RepublicaOcupacao
    {
        return DB::transaction(function () use ($republica, $colaboradorId, $dataEntrada, $observacoes): RepublicaOcupacao {
            // Verifica capacidade
            $atuais = $republica->ocupacoesAtuais()->count();
            if ($atuais >= (int) $republica->capacidade_total) {
                throw ValidationException::withMessages([
                    'colaborador_id' => "República lotada ({$atuais}/{$republica->capacidade_total}).",
                ]);
            }

            // Verifica se o colaborador já está em outra república
            $jaAlocado = RepublicaOcupacao::query()
                ->where('colaborador_id', $colaboradorId)
                ->atuais()
                ->exists();
            if ($jaAlocado) {
                throw ValidationException::withMessages([
                    'colaborador_id' => 'Este colaborador já está alocado em outra república. Faça a saída primeiro.',
                ]);
            }

            return RepublicaOcupacao::create([
                'republica_id' => $republica->id,
                'colaborador_id' => $colaboradorId,
                'data_entrada' => $dataEntrada,
                'observacoes' => $observacoes,
            ]);
        });
    }

    /**
     * Registra a saída de um colaborador (encerra ocupação).
     */
    public function darSaida(RepublicaOcupacao $ocupacao, string $dataSaida): RepublicaOcupacao
    {
        if ($ocupacao->data_saida !== null) {
            throw ValidationException::withMessages([
                'data_saida' => 'Esta ocupação já foi encerrada.',
            ]);
        }

        if ($ocupacao->data_entrada->gt(\Carbon\Carbon::parse($dataSaida))) {
            throw ValidationException::withMessages([
                'data_saida' => 'A data de saída deve ser após a data de entrada.',
            ]);
        }

        $ocupacao->update(['data_saida' => $dataSaida]);
        return $ocupacao->fresh();
    }

    /**
     * Cria a república.
     *
     * @param array<string, mixed> $dados
     */
    public function criar(array $dados): Republica
    {
        $dados = $this->normalizar($dados);
        $dados['created_by'] = Auth::id();
        $dados['updated_by'] = Auth::id();
        return Republica::create($dados);
    }

    /**
     * @param array<string, mixed> $dados
     */
    public function atualizar(Republica $republica, array $dados): Republica
    {
        $dados = $this->normalizar($dados);
        $dados['updated_by'] = Auth::id();

        // Se reduziu capacidade abaixo do nº de ocupantes atuais, bloqueia
        if (isset($dados['capacidade_total'])) {
            $atuais = $republica->ocupacoesAtuais()->count();
            if ((int) $dados['capacidade_total'] < $atuais) {
                throw ValidationException::withMessages([
                    'capacidade_total' => "Capacidade não pode ser menor que ocupantes atuais ({$atuais}).",
                ]);
            }
        }

        $republica->update($dados);
        return $republica->fresh();
    }

    /**
     * @param array<string, mixed> $dados
     * @return array<string, mixed>
     */
    private function normalizar(array $dados): array
    {
        foreach ($dados as $key => $valor) {
            if (is_string($valor) && trim($valor) === '') {
                $dados[$key] = null;
            }
        }
        return $dados;
    }
}
