<?php

declare(strict_types=1);

use App\Livewire\Veiculos\Gerenciar;
use App\Livewire\Veiculos\Manutencoes;
use App\Models\Fornecedor;
use App\Models\Veiculo;
use App\Models\VeiculoManutencao;
use Livewire\Livewire;

it('admin pode criar veículo válido', function () {
    asAdmin();

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('placa', 'ABC-1234')
        ->set('marca', 'Volkswagen')
        ->set('modelo', 'Gol')
        ->set('km_atual', 50000)
        ->set('status', 'disponivel')
        ->call('save')
        ->assertHasNoErrors();

    expect(Veiculo::where('marca', 'Volkswagen')->where('modelo', 'Gol')->exists())->toBeTrue();
});

it('armazena placa apenas com letras/dígitos maiúsculos', function () {
    asAdmin();

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('placa', 'abc-1234')
        ->set('marca', 'Fiat')
        ->set('modelo', 'Strada')
        ->set('km_atual', 0)
        ->set('status', 'disponivel')
        ->call('save');

    expect(Veiculo::where('modelo', 'Strada')->value('placa'))->toBe('ABC1234');
});

it('rejeita placa inválida', function () {
    asAdmin();

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('placa', 'XX-99')
        ->set('marca', 'X')
        ->set('modelo', 'Y')
        ->set('km_atual', 0)
        ->set('status', 'disponivel')
        ->call('save')
        ->assertHasErrors('placa');
});

it('rejeita placa duplicada', function () {
    asAdmin();
    Veiculo::factory()->create(['placa' => 'ABC1D23']);

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('placa', 'ABC1D23')
        ->set('marca', 'Outro')
        ->set('modelo', 'Modelo')
        ->set('km_atual', 0)
        ->set('status', 'disponivel')
        ->call('save')
        ->assertHasErrors('placa');
});

it('rejeita chassi com menos de 17 caracteres', function () {
    asAdmin();

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('placa', 'XYZ-1234')
        ->set('marca', 'Test')
        ->set('modelo', 'Test')
        ->set('chassi', 'ABC123') // muito curto
        ->set('km_atual', 0)
        ->set('status', 'disponivel')
        ->call('save')
        ->assertHasErrors('chassi');
});

it('detecta licenciamento vencido', function () {
    $vencido = Veiculo::factory()->licenciamentoVencido()->create();
    $valido = Veiculo::factory()->create(['licenciamento_vencimento' => now()->addMonths(6)]);

    expect($vencido->licenciamento_vencido)->toBeTrue();
    expect($valido->licenciamento_vencido)->toBeFalse();
});

it('formata placa antiga com hífen no accessor', function () {
    $v = Veiculo::factory()->make(['placa' => 'ABC1234']);
    expect($v->placa_formatada)->toBe('ABC-1234');
});

it('mantém placa Mercosul sem hífen no accessor', function () {
    $v = Veiculo::factory()->make(['placa' => 'ABC1D23']);
    expect($v->placa_formatada)->toBe('ABC1D23');
});

it('busca veículo por marca', function () {
    asAdmin();
    Veiculo::factory()->create(['marca' => 'Volkswagen', 'modelo' => 'Gol']);
    Veiculo::factory()->create(['marca' => 'Fiat', 'modelo' => 'Uno']);

    Livewire::test(Gerenciar::class)
        ->set('search', 'Volkswagen')
        ->assertViewHas('veiculos', fn ($p) => $p->total() === 1);
});

// =========================
// Manutenções
// =========================

it('registra manutenção e atualiza KM do veículo quando maior', function () {
    asAdmin();
    $veiculo = Veiculo::factory()->create(['km_atual' => 50000]);

    Livewire::test(Manutencoes::class)
        ->call('openCreate')
        ->set('veiculo_id', $veiculo->id)
        ->set('tipo', 'preventiva')
        ->set('data_manutencao', now()->toDateString())
        ->set('km_no_momento', 55000) // maior que o atual
        ->set('descricao', 'Revisão preventiva dos 55 mil km')
        ->call('save')
        ->assertHasNoErrors();

    expect($veiculo->fresh()->km_atual)->toBe(55000);
    expect(VeiculoManutencao::count())->toBe(1);
});

it('não diminui o KM do veículo quando manutenção registra valor menor', function () {
    asAdmin();
    $veiculo = Veiculo::factory()->create(['km_atual' => 80000]);

    Livewire::test(Manutencoes::class)
        ->call('openCreate')
        ->set('veiculo_id', $veiculo->id)
        ->set('tipo', 'corretiva')
        ->set('data_manutencao', now()->toDateString())
        ->set('km_no_momento', 60000) // retroativa
        ->set('descricao', 'Manutenção retroativa registrada agora')
        ->call('save')
        ->assertHasNoErrors();

    expect($veiculo->fresh()->km_atual)->toBe(80000); // manteve
});

it('rejeita próxima manutenção em data anterior à atual', function () {
    asAdmin();
    $veiculo = Veiculo::factory()->create();

    Livewire::test(Manutencoes::class)
        ->call('openCreate')
        ->set('veiculo_id', $veiculo->id)
        ->set('tipo', 'revisao')
        ->set('data_manutencao', '2025-06-01')
        ->set('descricao', 'Teste de datas')
        ->set('proxima_manutencao_data', '2025-05-01') // antes
        ->call('save')
        ->assertHasErrors('proxima_manutencao_data');
});

it('rejeita próxima manutenção em KM menor que o atual', function () {
    asAdmin();
    $veiculo = Veiculo::factory()->create();

    Livewire::test(Manutencoes::class)
        ->call('openCreate')
        ->set('veiculo_id', $veiculo->id)
        ->set('tipo', 'revisao')
        ->set('data_manutencao', now()->toDateString())
        ->set('km_no_momento', 100000)
        ->set('descricao', 'Teste KM próxima')
        ->set('proxima_manutencao_km', 50000) // menor
        ->call('save')
        ->assertHasErrors('proxima_manutencao_km');
});

it('exige descrição mínima de 5 caracteres', function () {
    asAdmin();
    $veiculo = Veiculo::factory()->create();

    Livewire::test(Manutencoes::class)
        ->call('openCreate')
        ->set('veiculo_id', $veiculo->id)
        ->set('tipo', 'outro')
        ->set('data_manutencao', now()->toDateString())
        ->set('descricao', 'oi') // 2 caracteres
        ->call('save')
        ->assertHasErrors('descricao');
});

it('vincula manutenção ao fornecedor', function () {
    asAdmin();
    $veiculo = Veiculo::factory()->create();
    $oficina = Fornecedor::factory()->create(['razao_social' => 'Oficina do Zé LTDA']);

    Livewire::test(Manutencoes::class)
        ->call('openCreate')
        ->set('veiculo_id', $veiculo->id)
        ->set('tipo', 'corretiva')
        ->set('data_manutencao', now()->toDateString())
        ->set('descricao', 'Troca de embreagem')
        ->set('fornecedor_id', $oficina->id)
        ->set('valor', 1200.00)
        ->call('save')
        ->assertHasNoErrors();

    $m = VeiculoManutencao::first();
    expect($m->fornecedor->razao_social)->toBe('Oficina do Zé LTDA');
    expect((float) $m->valor)->toBe(1200.00);
});

it('lista manutenções filtradas por veículo', function () {
    asAdmin();
    $v1 = Veiculo::factory()->create();
    $v2 = Veiculo::factory()->create();
    VeiculoManutencao::factory()->count(3)->create(['veiculo_id' => $v1->id]);
    VeiculoManutencao::factory()->count(2)->create(['veiculo_id' => $v2->id]);

    Livewire::test(Manutencoes::class)
        ->set('filterVeiculoId', $v1->id)
        ->assertViewHas('manutencoes', fn ($p) => $p->total() === 3);
});
