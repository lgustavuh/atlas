<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Candidato;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Lógica de negócio de candidatos.
 */
class CandidatoService
{
    public function __construct(
        private readonly DocumentoUploadService $documentos,
    ) {}

    /**
     * @param array<string, mixed> $dados
     */
    public function criar(array $dados, ?UploadedFile $curriculo = null): Candidato
    {
        return DB::transaction(function () use ($dados, $curriculo): Candidato {
            $dados = $this->normalizar($dados);

            if ($curriculo) {
                $info = $this->documentos->armazenar($curriculo, 'curriculos');
                $dados['curriculo_path'] = $info['arquivo_path'];
            }

            return Candidato::create($dados);
        });
    }

    /**
     * @param array<string, mixed> $dados
     */
    public function atualizar(Candidato $candidato, array $dados, ?UploadedFile $curriculo = null): Candidato
    {
        return DB::transaction(function () use ($candidato, $dados, $curriculo): Candidato {
            $dados = $this->normalizar($dados);

            if ($curriculo) {
                if ($candidato->curriculo_path) {
                    $this->documentos->remover($candidato->curriculo_path);
                }
                $info = $this->documentos->armazenar($curriculo, 'curriculos');
                $dados['curriculo_path'] = $info['arquivo_path'];
            }

            $candidato->update($dados);
            return $candidato->fresh();
        });
    }

    /**
     * Altera o status validando se a transição é permitida pelo workflow.
     */
    public function alterarStatus(Candidato $candidato, string $novoStatus): Candidato
    {
        if ($candidato->status === $novoStatus) {
            return $candidato;
        }

        $transicoesPermitidas = $candidato->transicoesPossiveis();
        if (!in_array($novoStatus, $transicoesPermitidas, true)) {
            throw ValidationException::withMessages([
                'status' => "Transição não permitida: {$candidato->status_label} → {$novoStatus}.",
            ]);
        }

        $candidato->update(['status' => $novoStatus]);
        return $candidato->fresh();
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
        // 'curriculo' veio das rules como UploadedFile; não vai pro banco
        unset($dados['curriculo']);
        return $dados;
    }
}
