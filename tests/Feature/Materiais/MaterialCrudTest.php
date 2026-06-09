<?php

declare(strict_types=1);

use App\Livewire\Materiais\Gerenciar;
use App\Models\Material;
use Livewire\Livewire;

it('admin pode criar material', function () {
    asAdmin();

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('nome', 'Cimento CP-II')
        ->set('unidade_medida', 'KG')
        ->set('estoque_atual', 100)
        ->set('estoque_minimo', 20)
        ->call('save')
        ->assertHasNoErrors();

    expect(Material::where('nome', 'Cimento CP-II')->exists())->toBeTrue();
});

it('rejeita código duplicado', function () {
    asAdmin();
    Material::factory()->create(['codigo' => 'MAT-001']);

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('nome', 'Outro material')
        ->set('codigo', 'MAT-001')
        ->set('unidade_medida', 'UN')
        ->set('estoque_atual', 0)
        ->set('estoque_minimo', 0)
        ->call('save')
        ->assertHasErrors('codigo');
});

it('rejeita estoque máximo menor que mínimo', function () {
    asAdmin();

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('nome', 'Material teste')
        ->set('unidade_medida', 'UN')
        ->set('estoque_atual', 10)
        ->set('estoque_minimo', 50)
        ->set('estoque_maximo', 20)
        ->call('save')
        ->assertHasErrors('estoque_maximo');
});

it('detecta estoque baixo', function () {
    $material = Material::factory()->estoqueBaixo()->create();
    expect($material->estoque_baixo)->toBeTrue();

    $normal = Material::factory()->create(['estoque_atual' => 100, 'estoque_minimo' => 10]);
    expect($normal->estoque_baixo)->toBeFalse();
});

it('filtra materiais abaixo do mínimo', function () {
    asAdmin();
    Material::factory()->estoqueBaixo()->count(2)->create();
    Material::factory()->create(['estoque_atual' => 100, 'estoque_minimo' => 10]);

    Livewire::test(Gerenciar::class)
        ->set('filterEstoque', 'baixo')
        ->assertViewHas('materiais', fn ($p) => $p->total() === 2);
});

it('formata estoque com unidade', function () {
    $m = Material::factory()->make([
        'estoque_atual' => 10.5,
        'unidade_medida' => 'KG',
    ]);
    expect($m->estoque_formatado)->toBe('10,5 KG');

    $inteiro = Material::factory()->make([
        'estoque_atual' => 100,
        'unidade_medida' => 'UN',
    ]);
    expect($inteiro->estoque_formatado)->toBe('100 UN');
});

it('busca material por nome', function () {
    asAdmin();
    Material::factory()->create(['nome' => 'Cimento Portland']);
    Material::factory()->create(['nome' => 'Areia fina']);

    Livewire::test(Gerenciar::class)
        ->set('search', 'Cimento')
        ->assertSee('Cimento Portland')
        ->assertDontSee('Areia fina');
});
