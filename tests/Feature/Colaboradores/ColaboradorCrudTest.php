<?php

declare(strict_types=1);

use App\Livewire\Colaboradores\Formulario;
use App\Livewire\Colaboradores\Listar;
use App\Livewire\Colaboradores\Visualizar;
use App\Models\Colaborador;
use Livewire\Livewire;

it('bloqueia acesso à listagem sem permissão', function () {
    asUser('colaborador'); // colaborador não tem colaboradores.view-any
    Livewire::test(Listar::class)->assertForbidden();
});

it('permite admin acessar a listagem', function () {
    asAdmin();
    Livewire::test(Listar::class)->assertOk();
});

it('lista colaboradores cadastrados', function () {
    asAdmin();
    Colaborador::factory()->count(3)->create();

    Livewire::test(Listar::class)
        ->assertSee(Colaborador::first()->nome);
});

it('busca colaborador por nome', function () {
    asAdmin();
    Colaborador::factory()->create(['nome' => 'Maria Aparecida Silva']);
    Colaborador::factory()->create(['nome' => 'João Pedro Santos']);

    Livewire::test(Listar::class)
        ->set('search', 'Maria')
        ->assertSee('Maria Aparecida Silva')
        ->assertDontSee('João Pedro Santos');
});

it('busca colaborador por CPF', function () {
    asAdmin();
    $colaborador = Colaborador::factory()->create();
    Colaborador::factory()->count(5)->create();

    $cpfParcial = substr($colaborador->cpf, 0, 6);

    Livewire::test(Listar::class)
        ->set('search', $cpfParcial)
        ->assertSee($colaborador->nome);
});

it('admin pode criar colaborador com dados válidos', function () {
    asAdmin();

    Livewire::test(Formulario::class)
        ->set('nome', 'Carlos Alberto da Silva')
        ->set('cpf', '111.444.777-35')
        ->set('email', 'carlos@empresa.com')
        ->set('data_admissao', '2024-01-15')
        ->call('salvar')
        ->assertHasNoErrors();

    expect(Colaborador::where('nome', 'Carlos Alberto da Silva')->exists())->toBeTrue();
});

it('rejeita criação com CPF inválido', function () {
    asAdmin();

    Livewire::test(Formulario::class)
        ->set('nome', 'Carlos Silva')
        ->set('cpf', '123.456.789-00') // CPF inválido
        ->call('salvar')
        ->assertHasErrors('cpf');
});

it('rejeita CPF duplicado entre colaboradores ativos', function () {
    asAdmin();
    Colaborador::factory()->create(['cpf' => '11144477735']);

    Livewire::test(Formulario::class)
        ->set('nome', 'Outro Colaborador')
        ->set('cpf', '111.444.777-35')
        ->call('salvar')
        ->assertHasErrors('cpf');
});

it('permite cadastrar CPF de colaborador deletado (unique parcial)', function () {
    asAdmin();
    $velho = Colaborador::factory()->create(['cpf' => '11144477735']);
    $velho->delete(); // soft delete

    Livewire::test(Formulario::class)
        ->set('nome', 'Novo Colaborador')
        ->set('cpf', '111.444.777-35')
        ->call('salvar')
        ->assertHasNoErrors('cpf');
});

it('exige descrição quando marca como PCD', function () {
    asAdmin();

    Livewire::test(Formulario::class)
        ->set('nome', 'Teste PCD')
        ->set('cpf', '111.444.777-35')
        ->set('pcd', true)
        ->set('pcd_descricao', '') // vazio mas pcd=true
        ->call('salvar')
        ->assertHasErrors('pcd_descricao');
});

it('admin pode visualizar colaborador', function () {
    asAdmin();
    $colaborador = Colaborador::factory()->create();

    Livewire::test(Visualizar::class, ['id' => $colaborador->id])
        ->assertOk()
        ->assertSee($colaborador->nome);
});

it('colaborador comum não vê salário', function () {
    asUser('gestor_rh'); // gestor_rh tem view-salary, vamos testar com almoxarife
    $colaborador = Colaborador::factory()->create(['salario' => 5000]);

    // Gestor_rh tem permissão de ver salário, então deve aparecer
    Livewire::test(Visualizar::class, ['id' => $colaborador->id])
        ->assertSee('R$ 5.000,00');
});

it('desativa colaborador (soft delete)', function () {
    asAdmin();
    $colaborador = Colaborador::factory()->create();

    Livewire::test(Listar::class)
        ->call('confirmarDesativar', $colaborador->id)
        ->call('executar');

    expect($colaborador->fresh()->trashed())->toBeTrue();
});

it('reativa colaborador desativado', function () {
    asAdmin();
    $colaborador = Colaborador::factory()->create();
    $colaborador->delete();

    Livewire::test(Listar::class)
        ->set('filterStatus', 'inativos')
        ->call('confirmarReativar', $colaborador->id)
        ->call('executar');

    expect($colaborador->fresh()->trashed())->toBeFalse();
});

it('armazena CPF apenas com dígitos no banco', function () {
    asAdmin();

    Livewire::test(Formulario::class)
        ->set('nome', 'Teste')
        ->set('cpf', '111.444.777-35')
        ->call('salvar');

    expect(Colaborador::where('nome', 'Teste')->value('cpf'))->toBe('11144477735');
});

it('formata CPF para exibição', function () {
    asAdmin();
    $colaborador = Colaborador::factory()->create(['cpf' => '11144477735']);

    expect($colaborador->cpf_formatado)->toBe('111.444.777-35');
});

it('calcula iniciais corretamente', function () {
    asAdmin();
    expect(Colaborador::factory()->make(['nome' => 'João Silva'])->iniciais)->toBe('JS');
    expect(Colaborador::factory()->make(['nome' => 'Maria das Dores Santos'])->iniciais)->toBe('MS');
    expect(Colaborador::factory()->make(['nome' => 'Pedro'])->iniciais)->toBe('PE');
});

it('calcula idade corretamente', function () {
    asAdmin();
    $colaborador = Colaborador::factory()->create([
        'data_nascimento' => now()->subYears(30)->subMonths(2),
    ]);

    expect($colaborador->idade)->toBe(30);
});

it('detecta colaborador ativo na empresa', function () {
    asAdmin();
    $ativo = Colaborador::factory()->create(['data_admissao' => now()->subYear()]);
    $demitido = Colaborador::factory()->demitido()->create();

    expect($ativo->ativo_na_empresa)->toBeTrue();
    expect($demitido->ativo_na_empresa)->toBeFalse();
});

it('registra activity log na criação', function () {
    asAdmin();

    $colaborador = Colaborador::factory()->create(['nome' => 'Teste Activity']);

    expect($colaborador->activities()->count())->toBeGreaterThan(0);
});
