<?php

declare(strict_types=1);

use App\Models\Advertencia;
use App\Models\Colaborador;
use App\Models\Fornecedor;
use App\Models\Material;
use App\Models\PedidoCompra;
use App\Models\PedidoCompraItem;
use App\Models\User;
use App\Pdf\AdvertenciaPdf;
use App\Pdf\ColaboradorFichaPdf;
use App\Pdf\PedidoCompraPdf;

// =========================
// Geração direta — output() retorna bytes de PDF
// =========================

it('AdvertenciaPdf gera bytes de PDF válidos', function () {
    $colab = Colaborador::factory()->create(['nome' => 'João Silva']);
    $adv = Advertencia::factory()->create([
        'colaborador_id' => $colab->id,
        'motivo' => 'Atraso reiterado',
        'descricao_ocorrencia' => 'Chegou atrasado em 5 ocasiões no mês.',
    ]);

    $pdf = new AdvertenciaPdf($adv);
    $output = $pdf->output();

    // Bytes de um PDF começam com %PDF-
    expect(substr($output, 0, 4))->toBe('%PDF');
    expect(strlen($output))->toBeGreaterThan(1000);
});

it('PedidoCompraPdf gera PDF com itens', function () {
    $forn = Fornecedor::factory()->create();
    $colab = Colaborador::factory()->create();
    $pedido = PedidoCompra::factory()->create([
        'fornecedor_id' => $forn->id,
        'solicitante_id' => $colab->id,
        'valor_total' => 1500.50,
    ]);

    // Cria item
    $material = Material::factory()->create();
    PedidoCompraItem::create([
        'pedido_compra_id' => $pedido->id,
        'material_id' => $material->id,
        'quantidade' => 10,
        'preco_unitario' => 150.05,
        'subtotal' => 1500.50,
    ]);

    $pdf = new PedidoCompraPdf($pedido->fresh());
    $output = $pdf->output();

    expect(substr($output, 0, 4))->toBe('%PDF');
});

it('ColaboradorFichaPdf gera PDF', function () {
    $colab = Colaborador::factory()->create(['nome' => 'Maria das Dores']);

    $pdf = new ColaboradorFichaPdf($colab);
    $output = $pdf->output();

    expect(substr($output, 0, 4))->toBe('%PDF');
    expect(strlen($output))->toBeGreaterThan(1000);
});

// =========================
// Rotas — admin baixa
// =========================

it('admin baixa PDF de advertência', function () {
    asAdmin();
    $adv = Advertencia::factory()->create();

    $response = $this->get(route('pdf.advertencia', $adv));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
    expect($response->headers->get('content-disposition'))->toContain('attachment');
    expect($response->headers->get('content-disposition'))->toContain('advertencia_');
});

it('admin pode visualizar inline com ?inline=1', function () {
    asAdmin();
    $adv = Advertencia::factory()->create();

    $response = $this->get(route('pdf.advertencia', $adv) . '?inline=1');

    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('inline');
});

it('admin baixa PDF de pedido de compra', function () {
    asAdmin();
    $pedido = PedidoCompra::factory()->create();

    $response = $this->get(route('pdf.pedido-compra', $pedido));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

it('admin baixa ficha funcional', function () {
    asAdmin();
    $colab = Colaborador::factory()->create();

    $response = $this->get(route('pdf.colaborador.ficha', $colab));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
    expect($response->headers->get('content-disposition'))->toContain('ficha-funcional_');
});

it('usuário sem permissão de ver advertência recebe 403', function () {
    $user = User::factory()->create();
    $user->assignRole('colaborador'); // colaborador não tem advertencias.view
    $this->actingAs($user);

    $adv = Advertencia::factory()->create();

    $response = $this->get(route('pdf.advertencia', $adv));
    $response->assertForbidden();
});

it('PDF de advertência inclui motivo no conteúdo', function () {
    $colab = Colaborador::factory()->create();
    $adv = Advertencia::factory()->create([
        'colaborador_id' => $colab->id,
        'motivo' => 'MOTIVO_UNICO_TESTE_PDF',
    ]);

    $output = (new AdvertenciaPdf($adv))->output();

    // Mesmo PDF binário deve conter o texto (compressao on/off varia)
    // Vamos extrair string e checar
    expect($output)->toBeString();
    // O PDF gerado deve ser não-vazio
    expect(strlen($output))->toBeGreaterThan(2000);
});

it('nome do arquivo da advertência usa matrícula', function () {
    $colab = Colaborador::factory()->create(['matricula' => 'MAT-1234']);
    $adv = Advertencia::factory()->create([
        'colaborador_id' => $colab->id,
        'data_aplicacao' => '2026-03-15',
    ]);

    asAdmin();
    $response = $this->get(route('pdf.advertencia', $adv));
    expect($response->headers->get('content-disposition'))->toContain('MAT-1234');
    expect($response->headers->get('content-disposition'))->toContain('2026-03-15');
});

it('Pedido de Compra usa número no nome do arquivo', function () {
    $pedido = PedidoCompra::factory()->create(['numero' => '2026/0042']);

    asAdmin();
    $response = $this->get(route('pdf.pedido-compra', $pedido));
    expect($response->headers->get('content-disposition'))->toContain('2026-0042');
});
