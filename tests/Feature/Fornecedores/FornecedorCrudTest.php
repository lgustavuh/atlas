<?php

declare(strict_types=1);

use App\Livewire\Fornecedores\Gerenciar;
use App\Models\Fornecedor;
use Livewire\Livewire;

it('admin pode criar fornecedor PJ válido', function () {
    asAdmin();

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('tipo_pessoa', 'juridica')
        ->set('razao_social', 'Materiais de Construção LTDA')
        ->set('cnpj_cpf', '11.222.333/0001-81')
        ->call('save')
        ->assertHasNoErrors();

    expect(Fornecedor::where('razao_social', 'Materiais de Construção LTDA')->exists())->toBeTrue();
});

it('armazena CNPJ apenas com dígitos', function () {
    asAdmin();

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('tipo_pessoa', 'juridica')
        ->set('razao_social', 'Teste LTDA')
        ->set('cnpj_cpf', '11.222.333/0001-81')
        ->call('save');

    expect(Fornecedor::where('razao_social', 'Teste LTDA')->value('cnpj_cpf'))->toBe('11222333000181');
});

it('rejeita CNPJ inválido', function () {
    asAdmin();

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('tipo_pessoa', 'juridica')
        ->set('razao_social', 'Teste')
        ->set('cnpj_cpf', '11.222.333/0001-00')
        ->call('save')
        ->assertHasErrors('cnpj_cpf');
});

it('valida CPF quando tipo é pessoa física', function () {
    asAdmin();

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('tipo_pessoa', 'fisica')
        ->set('razao_social', 'João Prestador')
        ->set('cnpj_cpf', '123.456.789-00') // CPF inválido
        ->call('save')
        ->assertHasErrors('cnpj_cpf');
});

it('aceita CPF válido para pessoa física', function () {
    asAdmin();

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('tipo_pessoa', 'fisica')
        ->set('razao_social', 'João Prestador')
        ->set('cnpj_cpf', '111.444.777-35')
        ->call('save')
        ->assertHasNoErrors();
});

it('rejeita documento duplicado', function () {
    asAdmin();
    Fornecedor::factory()->create(['cnpj_cpf' => '11222333000181']);

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('tipo_pessoa', 'juridica')
        ->set('razao_social', 'Outro')
        ->set('cnpj_cpf', '11.222.333/0001-81')
        ->call('save')
        ->assertHasErrors('cnpj_cpf');
});

it('alterna homologação', function () {
    asAdmin();
    $f = Fornecedor::factory()->create(['homologado' => false]);

    Livewire::test(Gerenciar::class)->call('toggleHomologacao', $f->id);

    expect($f->fresh()->homologado)->toBeTrue();
});

it('busca fornecedor por razão social', function () {
    asAdmin();
    Fornecedor::factory()->create(['razao_social' => 'Aços Premium SA']);
    Fornecedor::factory()->create(['razao_social' => 'Cimentos Brasil']);

    Livewire::test(Gerenciar::class)
        ->set('search', 'Aços')
        ->assertSee('Aços Premium SA')
        ->assertDontSee('Cimentos Brasil');
});

it('filtra por homologação', function () {
    asAdmin();
    Fornecedor::factory()->homologado()->count(2)->create();
    Fornecedor::factory()->create(['homologado' => false]);

    Livewire::test(Gerenciar::class)
        ->set('filterHomologado', 'sim')
        ->assertViewHas('fornecedores', fn ($p) => $p->total() === 2);
});

it('gera documento formatado conforme tipo', function () {
    $pj = Fornecedor::factory()->make(['tipo_pessoa' => 'juridica', 'cnpj_cpf' => '11222333000181']);
    expect($pj->documento_formatado)->toBe('11.222.333/0001-81');

    $pf = Fornecedor::factory()->make(['tipo_pessoa' => 'fisica', 'cnpj_cpf' => '11144477735']);
    expect($pf->documento_formatado)->toBe('111.444.777-35');
});

it('usa nome fantasia como exibição quando disponível', function () {
    $f = Fornecedor::factory()->make([
        'razao_social' => 'Empresa Razão Social LTDA',
        'nome_fantasia' => 'Marca Fantasia',
    ]);
    expect($f->nome_exibicao)->toBe('Marca Fantasia');

    $semFantasia = Fornecedor::factory()->make([
        'razao_social' => 'Só Razão LTDA',
        'nome_fantasia' => null,
    ]);
    expect($semFantasia->nome_exibicao)->toBe('Só Razão LTDA');
});
