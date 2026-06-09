<?php

declare(strict_types=1);

use App\Livewire\Cargos\Gerenciar;
use App\Models\Cargo;
use App\Models\Colaborador;
use Livewire\Livewire;

it('bloqueia acesso sem permissão', function () {
    asUser('visualizador');
    Livewire::test(Gerenciar::class)->assertOk(); // visualizador tem view-any
});

it('admin pode criar cargo', function () {
    asAdmin();

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('nome', 'Engenheiro de Software')
        ->set('cbo', '212405')
        ->set('salario_minimo', 5000)
        ->set('salario_maximo', 12000)
        ->call('save')
        ->assertHasNoErrors();

    expect(Cargo::where('nome', 'Engenheiro de Software')->exists())->toBeTrue();
});

it('rejeita salário máximo menor que mínimo', function () {
    asAdmin();

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('nome', 'Cargo Teste')
        ->set('salario_minimo', 5000)
        ->set('salario_maximo', 3000)
        ->call('save')
        ->assertHasErrors('salario_maximo');
});

it('rejeita nome de cargo duplicado', function () {
    asAdmin();
    Cargo::factory()->create(['nome' => 'Cargo Único']);

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('nome', 'Cargo Único')
        ->call('save')
        ->assertHasErrors('nome');
});

it('não permite excluir cargo em uso por colaboradores', function () {
    asAdmin();
    $cargo = Cargo::factory()->create();
    Colaborador::factory()->create(['cargo_id' => $cargo->id]);

    Livewire::test(Gerenciar::class)
        ->call('confirmDelete', $cargo->id);

    expect(Cargo::find($cargo->id))->not->toBeNull();
});

it('exclui cargo sem colaboradores', function () {
    asAdmin();
    $cargo = Cargo::factory()->create();

    Livewire::test(Gerenciar::class)
        ->call('confirmDelete', $cargo->id)
        ->call('delete');

    expect(Cargo::find($cargo->id))->toBeNull();
});

it('busca cargo por nome', function () {
    asAdmin();
    Cargo::factory()->create(['nome' => 'Analista de Dados']);
    Cargo::factory()->create(['nome' => 'Programador']);

    Livewire::test(Gerenciar::class)
        ->set('search', 'Analista')
        ->assertSee('Analista de Dados')
        ->assertDontSee('Programador');
});
