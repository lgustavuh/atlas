<?php

declare(strict_types=1);

use App\Livewire\Departamentos\Gerenciar;
use App\Models\Departamento;
use Livewire\Livewire;

it('admin pode criar departamento', function () {
    asAdmin();

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('nome', 'TI')
        ->set('sigla', 'TI')
        ->call('save')
        ->assertHasNoErrors();

    expect(Departamento::where('nome', 'TI')->exists())->toBeTrue();
});

it('cria departamento com pai', function () {
    asAdmin();
    $pai = Departamento::factory()->create();

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('nome', 'Suporte')
        ->set('departamento_pai_id', $pai->id)
        ->call('save');

    expect(Departamento::where('nome', 'Suporte')->first()->departamento_pai_id)->toBe($pai->id);
});

it('detecta ciclo direto (dep não pode ser pai de si mesmo)', function () {
    $dep = Departamento::factory()->create();

    expect($dep->criariaCiclo($dep->id))->toBeTrue();
});

it('detecta ciclo indireto', function () {
    // Hierarquia: A -> B -> C
    // Tentar fazer A filho de C deve detectar ciclo
    $a = Departamento::factory()->create(['nome' => 'A']);
    $b = Departamento::factory()->create(['nome' => 'B', 'departamento_pai_id' => $a->id]);
    $c = Departamento::factory()->create(['nome' => 'C', 'departamento_pai_id' => $b->id]);

    expect($a->criariaCiclo($c->id))->toBeTrue();
});

it('aceita hierarquia válida', function () {
    $a = Departamento::factory()->create();
    $b = Departamento::factory()->create();

    expect($b->criariaCiclo($a->id))->toBeFalse();
});

it('não exclui departamento com colaboradores', function () {
    asAdmin();
    $dep = Departamento::factory()->create();
    \App\Models\Colaborador::factory()->create(['departamento_id' => $dep->id]);

    Livewire::test(Gerenciar::class)->call('confirmDelete', $dep->id);

    expect(Departamento::find($dep->id))->not->toBeNull();
});

it('calcula caminho completo na árvore', function () {
    $diretoria = Departamento::factory()->create(['nome' => 'Diretoria']);
    $ti = Departamento::factory()->create(['nome' => 'TI', 'departamento_pai_id' => $diretoria->id]);
    $suporte = Departamento::factory()->create(['nome' => 'Suporte', 'departamento_pai_id' => $ti->id]);

    expect($suporte->caminho_completo)->toBe('Diretoria > TI > Suporte');
});
