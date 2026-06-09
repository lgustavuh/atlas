<?php

declare(strict_types=1);

use App\Models\Colaborador;
use App\Models\Fornecedor;
use App\Models\Material;
use App\Models\PedidoCompra;
use App\Services\PedidoCompraService;

beforeEach(function () {
    $this->service = app(PedidoCompraService::class);
});

function dadosCabecalho(): array
{
    return [
        'fornecedor_id' => Fornecedor::factory()->create()->id,
        'solicitante_id' => Colaborador::factory()->create()->id,
        'data_pedido' => now()->toDateString(),
        'valor_desconto' => 0,
        'valor_frete' => 0,
        'parcelas' => 1,
    ];
}

function itensExemplo(): array
{
    $m1 = Material::factory()->create();
    $m2 = Material::factory()->create();
    return [
        ['material_id' => $m1->id, 'quantidade' => 10, 'preco_unitario' => 5.00],
        ['material_id' => $m2->id, 'quantidade' => 3, 'preco_unitario' => 20.00],
    ];
}

it('cria pedido com número sequencial', function () {
    asAdmin();
    $pedido = $this->service->criar(dadosCabecalho(), itensExemplo());

    expect($pedido->numero)->toStartWith((string) now()->year);
    expect($pedido->status)->toBe(PedidoCompra::STATUS_RASCUNHO);
});

it('calcula totais corretamente', function () {
    asAdmin();
    // 10*5 + 3*20 = 50 + 60 = 110
    $pedido = $this->service->criar(dadosCabecalho(), itensExemplo());

    expect((float) $pedido->valor_total)->toBe(110.00);
    expect((float) $pedido->valor_final)->toBe(110.00);
});

it('aplica desconto e frete no valor final', function () {
    asAdmin();
    $dados = array_merge(dadosCabecalho(), ['valor_desconto' => 10, 'valor_frete' => 25]);
    // 110 - 10 + 25 = 125
    $pedido = $this->service->criar($dados, itensExemplo());

    expect((float) $pedido->valor_final)->toBe(125.00);
});

it('gera números sequenciais diferentes', function () {
    asAdmin();
    $p1 = $this->service->criar(dadosCabecalho(), itensExemplo());
    $p2 = $this->service->criar(dadosCabecalho(), itensExemplo());

    expect($p1->numero)->not->toBe($p2->numero);
});

it('cria itens vinculados ao pedido', function () {
    asAdmin();
    $pedido = $this->service->criar(dadosCabecalho(), itensExemplo());

    expect($pedido->itens()->count())->toBe(2);
});

it('workflow completo: rascunho até recebido', function () {
    asAdmin();
    $pedido = $this->service->criar(dadosCabecalho(), itensExemplo());

    // Enviar para liberação
    $this->service->enviarParaLiberacao($pedido);
    expect($pedido->fresh()->status)->toBe(PedidoCompra::STATUS_AGUARDANDO_LIBERACAO);

    // Liberar (1ª etapa)
    $this->service->liberar($pedido->fresh(), true, 'OK');
    expect($pedido->fresh()->status)->toBe(PedidoCompra::STATUS_AGUARDANDO_APROVACAO);

    // Aprovar (2ª etapa)
    $this->service->aprovar($pedido->fresh(), true, 'Aprovado');
    expect($pedido->fresh()->status)->toBe(PedidoCompra::STATUS_APROVADO);

    // Enviar ao fornecedor
    $this->service->enviarAoFornecedor($pedido->fresh());
    expect($pedido->fresh()->status)->toBe(PedidoCompra::STATUS_ENVIADO);

    // Receber tudo
    $pedido = $pedido->fresh('itens');
    $quantidades = [];
    foreach ($pedido->itens as $item) {
        $quantidades[$item->id] = (float) $item->quantidade;
    }
    $this->service->registrarRecebimento($pedido, $quantidades);
    expect($pedido->fresh()->status)->toBe(PedidoCompra::STATUS_RECEBIDO);
});

it('recebimento parcial mantém status parcial', function () {
    asAdmin();
    $pedido = $this->service->criar(dadosCabecalho(), itensExemplo());
    $this->service->enviarParaLiberacao($pedido);
    $this->service->liberar($pedido->fresh(), true);
    $this->service->aprovar($pedido->fresh(), true);
    $this->service->enviarAoFornecedor($pedido->fresh());

    $pedido = $pedido->fresh('itens');
    // Recebe só metade do primeiro item
    $primeiro = $pedido->itens->first();
    $this->service->registrarRecebimento($pedido, [
        $primeiro->id => (float) $primeiro->quantidade / 2,
    ]);

    expect($pedido->fresh()->status)->toBe(PedidoCompra::STATUS_PARCIAL);
});

it('liberação rejeitada muda status para rejeitado', function () {
    asAdmin();
    $pedido = $this->service->criar(dadosCabecalho(), itensExemplo());
    $this->service->enviarParaLiberacao($pedido);
    $this->service->liberar($pedido->fresh(), false, 'Fora do orçamento');

    expect($pedido->fresh()->status)->toBe(PedidoCompra::STATUS_REJEITADO);
});

it('registra histórico de aprovações', function () {
    asAdmin();
    $pedido = $this->service->criar(dadosCabecalho(), itensExemplo());
    $this->service->enviarParaLiberacao($pedido);
    $this->service->liberar($pedido->fresh(), true, 'Liberado');

    expect($pedido->fresh()->aprovacoes()->count())->toBe(1);
    expect($pedido->fresh()->aprovacoes()->first()->etapa)->toBe('liberacao');
});

it('não envia pedido vazio para liberação', function () {
    asAdmin();
    $dados = dadosCabecalho();
    $pedido = PedidoCompra::create(array_merge($dados, [
        'numero' => '2026/9999',
        'status' => PedidoCompra::STATUS_RASCUNHO,
    ]));

    expect(fn () => $this->service->enviarParaLiberacao($pedido))
        ->toThrow(\DomainException::class);
});

it('não permite editar pedido aprovado (Policy)', function () {
    asUser('almoxarife');
    $pedido = PedidoCompra::factory()->aprovado()->create();

    expect($pedido->podeEditar())->toBeFalse();
});

it('status label e cor retornam valores corretos', function () {
    $pedido = PedidoCompra::factory()->make(['status' => PedidoCompra::STATUS_RECEBIDO]);
    expect($pedido->status_label)->toBe('Recebido');
    expect($pedido->status_cor)->toBe('green');
});
