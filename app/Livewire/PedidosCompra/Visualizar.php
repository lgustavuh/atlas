<?php

declare(strict_types=1);

namespace App\Livewire\PedidosCompra;

use App\Models\PedidoCompra;
use App\Services\PedidoCompraService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Visualização do Pedido de Compra + ações de workflow.
 *
 * Concentra as transições de estado: enviar para liberação, liberar, aprovar,
 * enviar ao fornecedor, registrar recebimento, cancelar.
 */
#[Layout('layouts.app')]
#[Title('Pedido de Compra')]
class Visualizar extends Component
{
    public PedidoCompra $pedido;

    // Modal de ação (liberar/aprovar/rejeitar/cancelar)
    public bool $showAcaoModal = false;
    public string $acao = '';
    public string $comentario = '';

    // Modal de recebimento
    public bool $showRecebimentoModal = false;
    /** @var array<int, float> */
    public array $quantidadesRecebidas = [];

    public function mount(int $id): void
    {
        $this->pedido = PedidoCompra::with([
            'fornecedor', 'solicitante', 'itens.material',
            'aprovacoes.user:id,name',
        ])->findOrFail($id);

        $this->authorize('view', $this->pedido);
    }

    private function recarregar(): void
    {
        $this->pedido = $this->pedido->fresh([
            'fornecedor', 'solicitante', 'itens.material', 'aprovacoes.user:id,name',
        ]);
    }

    // ============================================================
    // Enviar para liberação
    // ============================================================

    public function enviarParaLiberacao(PedidoCompraService $service): void
    {
        $this->authorize('update', $this->pedido);

        try {
            $service->enviarParaLiberacao($this->pedido);
            $this->recarregar();
            $this->dispatch('toast', type: 'success', message: 'Pedido enviado para liberação.');
        } catch (\DomainException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    // ============================================================
    // Modal genérico de ação
    // ============================================================

    public function abrirAcao(string $acao): void
    {
        $this->acao = $acao;
        $this->comentario = '';
        $this->showAcaoModal = true;
    }

    public function confirmarAcao(PedidoCompraService $service): void
    {
        try {
            match ($this->acao) {
                'liberar' => $this->processarLiberacao($service, true),
                'rejeitar_liberacao' => $this->processarLiberacao($service, false),
                'aprovar' => $this->processarAprovacao($service, true),
                'rejeitar_aprovacao' => $this->processarAprovacao($service, false),
                'cancelar' => $this->processarCancelamento($service),
                default => null,
            };

            $this->recarregar();
            $this->showAcaoModal = false;
        } catch (\DomainException $e) {
            // Mensagens de regra de negócio podem ir pro usuário
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        } catch (\Throwable $e) {
            // Erros internos: log com detalhes, mensagem genérica pro usuário
            \Log::error('Erro ao executar ação em pedido de compra', [
                'pedido_id' => $this->pedido->id ?? null,
                'acao' => $this->acao,
                'erro' => $e->getMessage(),
                'arquivo' => $e->getFile() . ':' . $e->getLine(),
            ]);
            if (app()->environment('testing', 'local')) {
                throw $e;
            }
            $this->dispatch('toast', type: 'error',
                message: 'Não foi possível concluir a ação. Tente novamente ou contate o administrador.');
        }
    }

    private function processarLiberacao(PedidoCompraService $service, bool $aprovado): void
    {
        $this->authorize('liberar', $this->pedido);

        if (!$aprovado && trim($this->comentario) === '') {
            $this->addError('comentario', 'Informe o motivo da rejeição.');
            throw new \DomainException('Motivo obrigatório.');
        }

        $service->liberar($this->pedido, $aprovado, $this->comentario ?: null);
        $this->dispatch('toast', type: $aprovado ? 'success' : 'warning',
            message: $aprovado ? 'Pedido liberado. Segue para aprovação.' : 'Pedido rejeitado.');
    }

    private function processarAprovacao(PedidoCompraService $service, bool $aprovado): void
    {
        $this->authorize('aprovar', $this->pedido);

        if (!$aprovado && trim($this->comentario) === '') {
            $this->addError('comentario', 'Informe o motivo da rejeição.');
            throw new \DomainException('Motivo obrigatório.');
        }

        $service->aprovar($this->pedido, $aprovado, $this->comentario ?: null);
        $this->dispatch('toast', type: $aprovado ? 'success' : 'warning',
            message: $aprovado ? 'Pedido aprovado!' : 'Pedido rejeitado.');
    }

    private function processarCancelamento(PedidoCompraService $service): void
    {
        $this->authorize('cancelar', $this->pedido);
        $service->cancelar($this->pedido, $this->comentario ?: null);
        $this->dispatch('toast', type: 'warning', message: 'Pedido cancelado.');
    }

    // ============================================================
    // Enviar ao fornecedor
    // ============================================================

    public function enviarAoFornecedor(PedidoCompraService $service): void
    {
        $this->authorize('aprovar', $this->pedido); // quem aprova pode enviar

        try {
            $service->enviarAoFornecedor($this->pedido);
            $this->recarregar();
            $this->dispatch('toast', type: 'success', message: 'Pedido marcado como enviado ao fornecedor.');
        } catch (\DomainException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    // ============================================================
    // Recebimento
    // ============================================================

    public function abrirRecebimento(): void
    {
        $this->authorize('receber', $this->pedido);

        // Pré-preenche com o que ainda falta receber
        $this->quantidadesRecebidas = [];
        foreach ($this->pedido->itens as $item) {
            $this->quantidadesRecebidas[$item->id] = (float) $item->quantidade_recebida;
        }
        $this->showRecebimentoModal = true;
    }

    public function confirmarRecebimento(PedidoCompraService $service): void
    {
        $this->authorize('receber', $this->pedido);

        $service->registrarRecebimento($this->pedido, $this->quantidadesRecebidas);
        $this->recarregar();
        $this->showRecebimentoModal = false;
        $this->dispatch('toast', type: 'success', message: 'Recebimento registrado.');
    }

    public function render()
    {
        return view('livewire.pedidos-compra.visualizar', [
            'podeEditar' => Auth::user()->can('update', $this->pedido),
            'podeLiberar' => Auth::user()->can('liberar', $this->pedido),
            'podeAprovar' => Auth::user()->can('aprovar', $this->pedido),
            'podeReceber' => Auth::user()->can('receber', $this->pedido),
            'podeCancelar' => Auth::user()->can('cancelar', $this->pedido),
        ]);
    }
}
