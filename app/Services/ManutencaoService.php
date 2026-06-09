<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Veiculo;
use App\Models\VeiculoManutencao;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Lógica de negócio das manutenções de veículos.
 *
 * Centraliza:
 *   - Atualização do km_atual do veículo quando uma manutenção é registrada
 *   - Anexo de comprovante (NF, recibo) usando DocumentoUploadService
 */
class ManutencaoService
{
    public function __construct(
        private readonly DocumentoUploadService $documentos,
    ) {}

    /**
     * @param array<string, mixed> $dados
     */
    public function criar(array $dados, ?UploadedFile $comprovante = null): VeiculoManutencao
    {
        return DB::transaction(function () use ($dados, $comprovante): VeiculoManutencao {
            $dados = $this->normalizar($dados);
            $dados['created_by'] = Auth::id();
            $dados['updated_by'] = Auth::id();

            if ($comprovante) {
                $info = $this->documentos->armazenar($comprovante, 'manutencoes');
                $dados['comprovante_path'] = $info['arquivo_path'];
                $dados['comprovante_hash'] = $info['arquivo_hash'];
                $dados['comprovante_nome_original'] = $info['arquivo_nome_original'];
                $dados['comprovante_mime'] = $info['arquivo_mime'];
            }

            $manutencao = VeiculoManutencao::create($dados);

            // Atualiza km_atual do veículo se a manutenção registra um km maior que o atual
            $this->sincronizarKmDoVeiculo($manutencao);

            return $manutencao->fresh(['veiculo', 'fornecedor']);
        });
    }

    /**
     * @param array<string, mixed> $dados
     */
    public function atualizar(VeiculoManutencao $manutencao, array $dados, ?UploadedFile $comprovante = null): VeiculoManutencao
    {
        return DB::transaction(function () use ($manutencao, $dados, $comprovante): VeiculoManutencao {
            $dados = $this->normalizar($dados);
            $dados['updated_by'] = Auth::id();

            if ($comprovante) {
                // Apaga comprovante antigo
                if ($manutencao->comprovante_path) {
                    $this->documentos->remover($manutencao->comprovante_path);
                }
                $info = $this->documentos->armazenar($comprovante, 'manutencoes');
                $dados['comprovante_path'] = $info['arquivo_path'];
                $dados['comprovante_hash'] = $info['arquivo_hash'];
                $dados['comprovante_nome_original'] = $info['arquivo_nome_original'];
                $dados['comprovante_mime'] = $info['arquivo_mime'];
            }

            $manutencao->update($dados);
            $this->sincronizarKmDoVeiculo($manutencao->fresh());

            return $manutencao->fresh(['veiculo', 'fornecedor']);
        });
    }

    public function excluir(VeiculoManutencao $manutencao): void
    {
        DB::transaction(function () use ($manutencao): void {
            if ($manutencao->comprovante_path) {
                $this->documentos->remover($manutencao->comprovante_path);
            }
            $manutencao->update(['updated_by' => Auth::id()]);
            $manutencao->delete();
        });
    }

    /**
     * Se a manutenção informou km_no_momento maior que o km_atual do veículo,
     * atualiza o veículo. Não mexe se for menor (manutenção retroativa).
     */
    private function sincronizarKmDoVeiculo(VeiculoManutencao $manutencao): void
    {
        if (!$manutencao->km_no_momento) {
            return;
        }

        $veiculo = $manutencao->veiculo;
        if (!$veiculo || $manutencao->km_no_momento <= $veiculo->km_atual) {
            return;
        }

        $veiculo->update([
            'km_atual' => $manutencao->km_no_momento,
            'updated_by' => Auth::id(),
        ]);
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
        // 'comprovante' veio das rules de validação como UploadedFile; não vai pro banco
        unset($dados['comprovante']);
        return $dados;
    }
}
