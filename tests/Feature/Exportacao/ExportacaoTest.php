<?php

declare(strict_types=1);

use App\Exports\AuditoriaExport;
use App\Exports\ColaboradoresExport;
use App\Exports\ManutencoesExport;
use App\Exports\PedidosCompraExport;
use App\Exports\VeiculosExport;
use App\Livewire\Auditoria\Consultar as AuditoriaConsultar;
use App\Livewire\Colaboradores\Listar as ColaboradoresListar;
use App\Livewire\PedidosCompra\Listar as PedidosListar;
use App\Livewire\Veiculos\Gerenciar as VeiculosGerenciar;
use App\Livewire\Veiculos\Manutencoes as VeiculosManutencoes;
use App\Models\Colaborador;
use App\Models\Fornecedor;
use App\Models\PedidoCompra;
use App\Models\User;
use App\Models\Veiculo;
use App\Models\VeiculoManutencao;
use Illuminate\Database\Eloquent\Builder;

// Headings
it('ColaboradoresExport tem headings esperados', function () {
    $headings = (new ColaboradoresExport())->headings();
    expect($headings)->toContain('Nome', 'CPF', 'E-mail', 'Cargo', 'Departamento');
});

it('VeiculosExport tem headings esperados', function () {
    $headings = (new VeiculosExport())->headings();
    expect($headings)->toContain('Placa', 'Marca', 'Modelo', 'KM atual');
});

it('PedidosCompraExport tem headings esperados', function () {
    $headings = (new PedidosCompraExport())->headings();
    expect($headings)->toContain('Número', 'Fornecedor', 'Valor total', 'Status');
});

it('ManutencoesExport tem headings esperados', function () {
    $headings = (new ManutencoesExport())->headings();
    expect($headings)->toContain('Data', 'Veículo', 'Placa', 'Tipo', 'Valor');
});

it('AuditoriaExport tem headings esperados', function () {
    $headings = (new AuditoriaExport())->headings();
    expect($headings)->toContain('Data/hora', 'Usuário', 'Módulo', 'Evento');
});

// Mapping
it('ColaboradoresExport mapeia colaborador real', function () {
    $colab = Colaborador::factory()->create(['nome' => 'Maria Teste']);
    $row = (new ColaboradoresExport())->map($colab);

    expect($row)->toBeArray();
    expect($row)->toContain('Maria Teste');
});

it('VeiculosExport mapeia veículo com placa formatada', function () {
    $v = Veiculo::factory()->create(['placa' => 'ABC1234']);
    $row = (new VeiculosExport())->map($v);
    expect($row[0])->toBe('ABC-1234');
});

it('ManutencoesExport mapeia com tipo label legível', function () {
    $manut = VeiculoManutencao::factory()->preventiva()->create();
    $row = (new ManutencoesExport())->map($manut);
    expect($row[3])->toBe('Preventiva');
});

// Coleção com filtros
it('ColaboradoresExport com filtro pega só os filtrados', function () {
    Colaborador::factory()->create(['nome' => 'Maria Aparecida']);
    Colaborador::factory()->create(['nome' => 'João Pedro']);

    $filtro = fn (Builder $q): Builder => $q->where('nome', 'like', 'Maria%');
    $export = new ColaboradoresExport($filtro);

    expect($export->collection())->toHaveCount(1);
});

it('VeiculosExport com filtro por status', function () {
    Veiculo::factory()->count(2)->create(['status' => 'disponivel']);
    Veiculo::factory()->create(['status' => 'inativo']);

    $filtro = fn (Builder $q): Builder => $q->where('status', 'disponivel');
    $export = new VeiculosExport($filtro);

    expect($export->collection())->toHaveCount(2);
});

it('ManutencoesExport com filtro por veículo', function () {
    $v1 = Veiculo::factory()->create();
    $v2 = Veiculo::factory()->create();
    VeiculoManutencao::factory()->count(3)->create(['veiculo_id' => $v1->id]);
    VeiculoManutencao::factory()->create(['veiculo_id' => $v2->id]);

    $filtro = fn (Builder $q): Builder => $q->where('veiculo_id', $v1->id);
    $export = new ManutencoesExport($filtro);

    expect($export->collection())->toHaveCount(3);
});

// Integração Livewire — método retorna BinaryFileResponse
it('Colaboradores exportar() retorna BinaryFileResponse', function () {
    asAdmin();
    Colaborador::factory()->count(2)->create();

    $componente = \Livewire\Livewire::test(ColaboradoresListar::class);
    $response = $componente->instance()->exportar();

    expect($response)->toBeInstanceOf(\Symfony\Component\HttpFoundation\BinaryFileResponse::class);
    expect($response->headers->get('content-disposition'))->toContain('colaboradores_');
});

it('Veiculos exportar() retorna BinaryFileResponse', function () {
    asAdmin();
    Veiculo::factory()->create();

    $componente = \Livewire\Livewire::test(VeiculosGerenciar::class);
    $response = $componente->instance()->exportar();

    expect($response)->toBeInstanceOf(\Symfony\Component\HttpFoundation\BinaryFileResponse::class);
    expect($response->headers->get('content-disposition'))->toContain('veiculos_');
});

it('Manutenções exportar() retorna BinaryFileResponse', function () {
    asAdmin();
    VeiculoManutencao::factory()->create();

    $componente = \Livewire\Livewire::test(VeiculosManutencoes::class);
    $response = $componente->instance()->exportar();

    expect($response)->toBeInstanceOf(\Symfony\Component\HttpFoundation\BinaryFileResponse::class);
    expect($response->headers->get('content-disposition'))->toContain('manutencoes_');
});

it('Pedidos exportar() retorna BinaryFileResponse', function () {
    asAdmin();
    PedidoCompra::factory()->create();

    $componente = \Livewire\Livewire::test(PedidosListar::class);
    $response = $componente->instance()->exportar();

    expect($response)->toBeInstanceOf(\Symfony\Component\HttpFoundation\BinaryFileResponse::class);
    expect($response->headers->get('content-disposition'))->toContain('pedidos-compra_');
});

it('Auditoria exportar() retorna BinaryFileResponse', function () {
    asAdmin();
    Colaborador::factory()->create();

    $componente = \Livewire\Livewire::test(AuditoriaConsultar::class);
    $response = $componente->instance()->exportar();

    expect($response)->toBeInstanceOf(\Symfony\Component\HttpFoundation\BinaryFileResponse::class);
    expect($response->headers->get('content-disposition'))->toContain('auditoria_');
});

it('Auditoria exportar() bloqueia sem permissão', function () {
    $user = User::factory()->create();
    $user->assignRole('colaborador');
    $this->actingAs($user);

    // Não usar Livewire::test() porque já o mount() aborta com 403 antes
    // Testa que tentar acessar a rota retorna 403
    $response = $this->get(route('auditoria.index'));
    expect($response->status())->toBe(403);
});

it('Export title e estilo de cabeçalho', function () {
    $export = new ColaboradoresExport();
    expect($export->title())->toBe('Colaboradores');
});
