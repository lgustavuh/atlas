<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AlertaAdm;
use App\Models\AlertaAdmDestinatario;
use App\Models\Colaborador;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Lógica de negócio dos Alertas Administrativos.
 */
class AlertaAdmService
{
    /**
     * Cria um alerta com seus destinatários.
     *
     * @param array<string, mixed> $dados
     * @param list<int> $colaboradorIds
     */
    public function criar(array $dados, array $colaboradorIds): AlertaAdm
    {
        return DB::transaction(function () use ($dados, $colaboradorIds): AlertaAdm {
            $dados['created_by'] = Auth::id();
            $dados['updated_by'] = Auth::id();

            $alerta = AlertaAdm::create($dados);
            $this->sincronizarDestinatarios($alerta, $colaboradorIds);
            return $alerta->fresh('colaboradores');
        });
    }

    /**
     * Atualiza alerta + destinatários.
     *
     * @param array<string, mixed> $dados
     * @param list<int> $colaboradorIds
     */
    public function atualizar(AlertaAdm $alerta, array $dados, array $colaboradorIds): AlertaAdm
    {
        return DB::transaction(function () use ($alerta, $dados, $colaboradorIds): AlertaAdm {
            $dados['updated_by'] = Auth::id();
            $alerta->update($dados);
            $this->sincronizarDestinatarios($alerta, $colaboradorIds);
            return $alerta->fresh('colaboradores');
        });
    }

    /**
     * Adiciona TODOS os colaboradores ativos como destinatários.
     */
    public function enviarParaTodos(AlertaAdm $alerta): int
    {
        $ids = Colaborador::query()->ativos()->pluck('id')->all();
        return DB::transaction(function () use ($alerta, $ids): int {
            $this->sincronizarDestinatarios($alerta, $ids);
            return count($ids);
        });
    }

    /**
     * Marca um alerta como visualizado por um colaborador.
     * Idempotente: chamar várias vezes não muda o timestamp original.
     */
    public function marcarVisualizado(int $alertaId, int $colaboradorId): bool
    {
        $registro = AlertaAdmDestinatario::where('alerta_adm_id', $alertaId)
            ->where('colaborador_id', $colaboradorId)
            ->first();

        if (!$registro || $registro->visualizado_em !== null) {
            return false;
        }

        $registro->update(['visualizado_em' => now()]);
        return true;
    }

    /**
     * @param list<int> $colaboradorIds
     */
    private function sincronizarDestinatarios(AlertaAdm $alerta, array $colaboradorIds): void
    {
        $idsValidos = array_values(array_unique(array_map('intval', $colaboradorIds)));

        // Quais já existem (preservamos para não perder visualizado_em)
        $existentes = $alerta->destinatarios()->pluck('colaborador_id')->all();

        // Remove os que saíram
        $remover = array_diff($existentes, $idsValidos);
        if (!empty($remover)) {
            $alerta->destinatarios()->whereIn('colaborador_id', $remover)->delete();
        }

        // Adiciona os novos (mantém os existentes intactos)
        $adicionar = array_diff($idsValidos, $existentes);
        foreach ($adicionar as $colaboradorId) {
            $alerta->destinatarios()->create([
                'colaborador_id' => $colaboradorId,
                'visualizado_em' => null,
            ]);
        }
    }
}
