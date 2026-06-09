<?php

declare(strict_types=1);

namespace App\Livewire\PedidosCompra;

use App\Models\Colaborador;
use App\Models\Fornecedor;
use App\Models\Material;
use App\Models\PedidoCompra;
use App\Services\PedidoCompraService;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Formulário de Pedido de Compra (criar/editar) com itens dinâmicos.
 *
 * O usuário adiciona linhas de material, cada uma com quantidade e preço.
 * Subtotais e total final recalculam ao vivo.
 */
#[Layout('layouts.app')]
class Formulario extends Component
{
    public ?int $pedidoId = null;
    public bool $editando = false;

    // Cabeçalho
    public ?int $fornecedor_id = null;
    public ?int $solicitante_id = null;
    public ?string $data_pedido = null;
    public ?string $data_entrega_prevista = null;
    public string $forma_pagamento = '';
    public int $parcelas = 1;
    public float $valor_desconto = 0;
    public float $valor_frete = 0;
    public string $justificativa = '';
    public string $observacoes = '';

    /**
     * Itens do pedido.
     * @var list<array{material_id: int|null, quantidade: float, preco_unitario: float, observacoes: string}>
     */
    public array $itens = [];

    public function mount(?int $id = null): void
    {
        if ($id) {
            $pedido = PedidoCompra::with('itens')->findOrFail($id);
            $this->authorize('update', $pedido);
            $this->carregar($pedido);
        } else {
            $this->authorize('create', PedidoCompra::class);
            $this->data_pedido = now()->toDateString();
            $this->adicionarItem(); // começa com uma linha
        }
    }

    public function title(): string
    {
        return $this->editando ? 'Editar Pedido de Compra' : 'Novo Pedido de Compra';
    }

    private function carregar(PedidoCompra $pedido): void
    {
        $this->editando = true;
        $this->pedidoId = $pedido->id;
        $this->fornecedor_id = $pedido->fornecedor_id;
        $this->solicitante_id = $pedido->solicitante_id;
        $this->data_pedido = $pedido->data_pedido?->toDateString();
        $this->data_entrega_prevista = $pedido->data_entrega_prevista?->toDateString();
        $this->forma_pagamento = (string) $pedido->forma_pagamento;
        $this->parcelas = $pedido->parcelas ?? 1;
        $this->valor_desconto = (float) $pedido->valor_desconto;
        $this->valor_frete = (float) $pedido->valor_frete;
        $this->justificativa = (string) $pedido->justificativa;
        $this->observacoes = (string) $pedido->observacoes;

        $this->itens = $pedido->itens->map(fn ($item) => [
            'material_id' => $item->material_id,
            'quantidade' => (float) $item->quantidade,
            'preco_unitario' => (float) $item->preco_unitario,
            'observacoes' => (string) $item->observacoes,
        ])->toArray();

        if (empty($this->itens)) {
            $this->adicionarItem();
        }
    }

    public function adicionarItem(): void
    {
        $this->itens[] = [
            'material_id' => null,
            'quantidade' => 1,
            'preco_unitario' => 0,
            'observacoes' => '',
        ];
    }

    public function removerItem(int $index): void
    {
        unset($this->itens[$index]);
        $this->itens = array_values($this->itens);

        // Sempre mantém ao menos uma linha
        if (empty($this->itens)) {
            $this->adicionarItem();
        }
    }

    /**
     * Quando seleciona um material, sugere o preço de referência.
     */
    public function updatedItens($value, $key): void
    {
        // $key vem como "0.material_id"
        if (str_ends_with($key, '.material_id')) {
            $index = (int) explode('.', $key)[0];
            $materialId = $this->itens[$index]['material_id'] ?? null;

            if ($materialId) {
                $material = Material::find($materialId);
                if ($material && $material->preco_referencia && (float) $this->itens[$index]['preco_unitario'] === 0.0) {
                    $this->itens[$index]['preco_unitario'] = (float) $material->preco_referencia;
                }
            }
        }
    }

    /**
     * Subtotal de uma linha.
     */
    public function subtotalItem(int $index): float
    {
        $item = $this->itens[$index] ?? null;
        if (!$item) {
            return 0;
        }
        return round((float) $item['quantidade'] * (float) $item['preco_unitario'], 2);
    }

    public function getTotalItensProperty(): float
    {
        $total = 0;
        foreach ($this->itens as $i => $item) {
            $total += $this->subtotalItem($i);
        }
        return round($total, 2);
    }

    public function getValorFinalProperty(): float
    {
        return max(0, $this->total_itens - (float) $this->valor_desconto + (float) $this->valor_frete);
    }

    public function salvar(PedidoCompraService $service): void
    {
        $data = $this->validate([
            'fornecedor_id' => ['required', 'integer', 'exists:fornecedores,id'],
            'solicitante_id' => ['required', 'integer', 'exists:colaboradores,id'],
            'data_pedido' => ['required', 'date'],
            'data_entrega_prevista' => ['nullable', 'date', 'after_or_equal:data_pedido'],
            'forma_pagamento' => ['nullable', 'in:a_vista,boleto,transferencia,cartao,pix,cheque,parcelado,outro'],
            'parcelas' => ['required', 'integer', 'min:1', 'max:60'],
            'valor_desconto' => ['required', 'numeric', 'min:0'],
            'valor_frete' => ['required', 'numeric', 'min:0'],
            'justificativa' => ['nullable', 'string', 'max:2000'],
            'observacoes' => ['nullable', 'string', 'max:2000'],
            'itens' => ['required', 'array', 'min:1'],
            'itens.*.material_id' => ['required', 'integer', 'exists:materiais,id'],
            'itens.*.quantidade' => ['required', 'numeric', 'gt:0'],
            'itens.*.preco_unitario' => ['required', 'numeric', 'min:0'],
            'itens.*.observacoes' => ['nullable', 'string', 'max:500'],
        ], messages: [
            'itens.required' => 'Adicione pelo menos um item ao pedido.',
            'itens.min' => 'Adicione pelo menos um item ao pedido.',
            'itens.*.material_id.required' => 'Selecione o material em todas as linhas.',
            'itens.*.quantidade.gt' => 'A quantidade deve ser maior que zero.',
            'data_entrega_prevista.after_or_equal' => 'A entrega prevista deve ser após a data do pedido.',
        ], attributes: [
            'fornecedor_id' => 'fornecedor',
            'solicitante_id' => 'solicitante',
        ]);

        // Verifica materiais duplicados nos itens
        $materiaisIds = array_column($this->itens, 'material_id');
        if (count($materiaisIds) !== count(array_unique($materiaisIds))) {
            $this->addError('itens', 'Há materiais repetidos. Agrupe as quantidades em uma única linha.');
            return;
        }

        $cabecalho = [
            'fornecedor_id' => $data['fornecedor_id'],
            'solicitante_id' => $data['solicitante_id'],
            'data_pedido' => $data['data_pedido'],
            'data_entrega_prevista' => $data['data_entrega_prevista'] ?: null,
            'forma_pagamento' => $data['forma_pagamento'] ?: null,
            'parcelas' => $data['parcelas'],
            'valor_desconto' => $data['valor_desconto'],
            'valor_frete' => $data['valor_frete'],
            'justificativa' => $data['justificativa'] ?: null,
            'observacoes' => $data['observacoes'] ?: null,
        ];

        $itens = array_map(fn ($item) => [
            'material_id' => (int) $item['material_id'],
            'quantidade' => (float) $item['quantidade'],
            'preco_unitario' => (float) $item['preco_unitario'],
            'observacoes' => $item['observacoes'] ?: null,
        ], $this->itens);

        try {
            if ($this->editando) {
                $pedido = PedidoCompra::findOrFail($this->pedidoId);
                $this->authorize('update', $pedido);
                $pedido = $service->atualizar($pedido, $cabecalho, $itens);
                session()->flash('success', "Pedido {$pedido->numero} atualizado.");
            } else {
                $this->authorize('create', PedidoCompra::class);
                $pedido = $service->criar($cabecalho, $itens);
                session()->flash('success', "Pedido {$pedido->numero} criado como rascunho.");
            }

            $this->redirect(route('pedidos-compra.show', $pedido->id), navigate: true);
        } catch (\Throwable $e) {
            \Log::error('Erro ao salvar pedido de compra', ['erro' => $e->getMessage()]);
            if (app()->environment('testing', 'local')) {
                throw $e;
            }
            $this->dispatch('toast', type: 'error', message: 'Erro ao salvar o pedido. Tente novamente.');
        }
    }

    public function render()
    {
        return view('livewire.pedidos-compra.formulario', [
            'fornecedores' => Fornecedor::orderBy('razao_social')->get(['id', 'razao_social', 'nome_fantasia']),
            'colaboradores' => Colaborador::orderBy('nome')->get(['id', 'nome']),
            'materiais' => Material::orderBy('nome')->get(['id', 'nome', 'codigo', 'unidade_medida', 'preco_referencia']),
        ]);
    }
}
