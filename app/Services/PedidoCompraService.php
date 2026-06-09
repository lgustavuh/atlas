<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PedidoCompra;
use App\Models\PedidoCompraAprovacao;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Lógica de negócio dos Pedidos de Compra.
 *
 * Centraliza:
 *   - Geração do número do pedido (ano/sequencial)
 *   - Cálculo de totais (subtotais, desconto, frete, total final)
 *   - Transições da máquina de estados, com registro de auditoria
 */
class PedidoCompraService
{
    /**
     * Cria um novo pedido com seus itens, em transação.
     *
     * @param array<string, mixed> $dados
     * @param list<array{material_id: int, quantidade: float, preco_unitario: float, observacoes?: string|null}> $itens
     */
    public function criar(array $dados, array $itens): PedidoCompra
    {
        return DB::transaction(function () use ($dados, $itens): PedidoCompra {
            $dados['numero'] = $this->gerarNumero();
            $dados['status'] = PedidoCompra::STATUS_RASCUNHO;
            $dados['created_by'] = Auth::id();
            $dados['updated_by'] = Auth::id();

            // Cria primeiro sem totais
            $pedido = PedidoCompra::create($dados);

            $this->sincronizarItens($pedido, $itens);
            $this->recalcularTotais($pedido);

            return $pedido->fresh(['itens.material', 'fornecedor', 'solicitante']);
        });
    }

    /**
     * Atualiza um pedido existente (apenas se editável).
     *
     * @param array<string, mixed> $dados
     * @param list<array{material_id: int, quantidade: float, preco_unitario: float, observacoes?: string|null}> $itens
     */
    public function atualizar(PedidoCompra $pedido, array $dados, array $itens): PedidoCompra
    {
        return DB::transaction(function () use ($pedido, $dados, $itens): PedidoCompra {
            $dados['updated_by'] = Auth::id();
            $pedido->update($dados);

            // Remove itens antigos e recria (estratégia simples e segura)
            $pedido->itens()->delete();
            $this->sincronizarItens($pedido, $itens);
            $this->recalcularTotais($pedido);

            return $pedido->fresh(['itens.material', 'fornecedor', 'solicitante']);
        });
    }

    /**
     * Envia o pedido para o fluxo de liberação (rascunho → aguardando_liberacao).
     */
    public function enviarParaLiberacao(PedidoCompra $pedido): void
    {
        if (!in_array($pedido->status, [PedidoCompra::STATUS_RASCUNHO, PedidoCompra::STATUS_REJEITADO])) {
            throw new \DomainException('Apenas rascunhos podem ser enviados para liberação.');
        }
        if ($pedido->itens()->count() === 0) {
            throw new \DomainException('O pedido precisa ter pelo menos um item.');
        }

        $pedido->update([
            'status' => PedidoCompra::STATUS_AGUARDANDO_LIBERACAO,
            'updated_by' => Auth::id(),
        ]);
    }

    /**
     * 1ª etapa: liberar (ou rejeitar) o pedido.
     */
    public function liberar(PedidoCompra $pedido, bool $aprovado, ?string $comentario = null): void
    {
        DB::transaction(function () use ($pedido, $aprovado, $comentario): void {
            $this->registrarAprovacao($pedido, 'liberacao', $aprovado, $comentario);

            $pedido->update([
                'status' => $aprovado
                    ? PedidoCompra::STATUS_AGUARDANDO_APROVACAO
                    : PedidoCompra::STATUS_REJEITADO,
                'updated_by' => Auth::id(),
            ]);
        });
    }

    /**
     * 2ª etapa: aprovação final (ou rejeição).
     */
    public function aprovar(PedidoCompra $pedido, bool $aprovado, ?string $comentario = null): void
    {
        DB::transaction(function () use ($pedido, $aprovado, $comentario): void {
            $this->registrarAprovacao($pedido, 'aprovacao', $aprovado, $comentario);

            $pedido->update([
                'status' => $aprovado
                    ? PedidoCompra::STATUS_APROVADO
                    : PedidoCompra::STATUS_REJEITADO,
                'updated_by' => Auth::id(),
            ]);
        });
    }

    /**
     * Marca como enviado ao fornecedor.
     */
    public function enviarAoFornecedor(PedidoCompra $pedido): void
    {
        if ($pedido->status !== PedidoCompra::STATUS_APROVADO) {
            throw new \DomainException('Apenas pedidos aprovados podem ser enviados ao fornecedor.');
        }

        $pedido->update([
            'status' => PedidoCompra::STATUS_ENVIADO,
            'updated_by' => Auth::id(),
        ]);
    }

    /**
     * Registra recebimento (total ou parcial) com base nas quantidades recebidas por item.
     *
     * @param array<int, float> $quantidadesRecebidas  [item_id => quantidade]
     */
    public function registrarRecebimento(PedidoCompra $pedido, array $quantidadesRecebidas): void
    {
        DB::transaction(function () use ($pedido, $quantidadesRecebidas): void {
            $todosCompletos = true;

            foreach ($pedido->itens as $item) {
                if (isset($quantidadesRecebidas[$item->id])) {
                    $recebida = max(0, (float) $quantidadesRecebidas[$item->id]);
                    // Não pode receber mais que o pedido
                    $recebida = min($recebida, (float) $item->quantidade);
                    $item->update(['quantidade_recebida' => $recebida]);
                }

                if ((float) $item->fresh()->quantidade_recebida < (float) $item->quantidade) {
                    $todosCompletos = false;
                }
            }

            $pedido->update([
                'status' => $todosCompletos
                    ? PedidoCompra::STATUS_RECEBIDO
                    : PedidoCompra::STATUS_PARCIAL,
                'data_entrega_realizada' => $todosCompletos ? now() : $pedido->data_entrega_realizada,
                'updated_by' => Auth::id(),
            ]);
        });
    }

    /**
     * Cancela o pedido.
     */
    public function cancelar(PedidoCompra $pedido, ?string $motivo = null): void
    {
        $pedido->update([
            'status' => PedidoCompra::STATUS_CANCELADO,
            'observacoes' => trim(($pedido->observacoes ?? '') . "\n[Cancelado] " . ($motivo ?? '')),
            'updated_by' => Auth::id(),
        ]);
    }

    // ============================================================
    // Helpers privados
    // ============================================================

    /**
     * Gera número sequencial no formato YYYY/NNNN.
     */
    private function gerarNumero(): string
    {
        $ano = now()->year;
        $prefixo = "{$ano}/";

        // Pega o último número do ano (considerando soft-deleted também para não repetir)
        $ultimo = PedidoCompra::withTrashed()
            ->where('numero', 'LIKE', "{$prefixo}%")
            ->orderByDesc('numero')
            ->value('numero');

        $sequencial = 1;
        if ($ultimo) {
            $partes = explode('/', $ultimo);
            $sequencial = (int) ($partes[1] ?? 0) + 1;
        }

        return $prefixo . str_pad((string) $sequencial, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @param list<array{material_id: int, quantidade: float, preco_unitario: float, observacoes?: string|null}> $itens
     */
    private function sincronizarItens(PedidoCompra $pedido, array $itens): void
    {
        foreach ($itens as $item) {
            $quantidade = (float) $item['quantidade'];
            $precoUnitario = (float) $item['preco_unitario'];
            $subtotal = round($quantidade * $precoUnitario, 2);

            $pedido->itens()->create([
                'material_id' => $item['material_id'],
                'quantidade' => $quantidade,
                'preco_unitario' => $precoUnitario,
                'subtotal' => $subtotal,
                'quantidade_recebida' => 0,
                'observacoes' => $item['observacoes'] ?? null,
            ]);
        }
    }

    /**
     * Recalcula valor_total (soma subtotais) e valor_final (com desconto e frete).
     */
    public function recalcularTotais(PedidoCompra $pedido): void
    {
        $total = (float) $pedido->itens()->sum('subtotal');
        $desconto = (float) $pedido->valor_desconto;
        $frete = (float) $pedido->valor_frete;
        $final = max(0, $total - $desconto + $frete);

        $pedido->update([
            'valor_total' => $total,
            'valor_final' => $final,
        ]);
    }

    private function registrarAprovacao(
        PedidoCompra $pedido,
        string $etapa,
        bool $aprovado,
        ?string $comentario
    ): void {
        PedidoCompraAprovacao::create([
            'pedido_compra_id' => $pedido->id,
            'etapa' => $etapa,
            'decisao' => $aprovado ? 'aprovado' : 'rejeitado',
            'user_id' => Auth::id(),
            'comentario' => $comentario,
        ]);
    }
}
