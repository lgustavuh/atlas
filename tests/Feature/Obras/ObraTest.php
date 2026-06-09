<?php

declare(strict_types=1);

use App\Livewire\Obras\Gerenciar;
use App\Models\Obra;
use Livewire\Livewire;

it('admin pode criar obra válida', function () {
    asAdmin();

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('nome', 'Pavimentação da Rua Central')
        ->set('codigo', 'OBR-001')
        ->set('orcamento', 250000)
        ->set('status', 'planejamento')
        ->call('save')
        ->assertHasNoErrors();

    expect(Obra::where('nome', 'Pavimentação da Rua Central')->exists())->toBeTrue();
});

it('rejeita nome muito curto', function () {
    asAdmin();

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('nome', 'AB')
        ->set('status', 'planejamento')
        ->call('save')
        ->assertHasErrors('nome');
});

it('rejeita código duplicado', function () {
    asAdmin();
    Obra::factory()->create(['codigo' => 'OBR-DUPL']);

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('nome', 'Outra obra')
        ->set('codigo', 'OBR-DUPL')
        ->set('status', 'planejamento')
        ->call('save')
        ->assertHasErrors('codigo');
});

it('permite código vazio (é opcional)', function () {
    asAdmin();

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('nome', 'Obra sem código')
        ->set('codigo', '')
        ->set('status', 'planejamento')
        ->call('save')
        ->assertHasNoErrors();

    expect(Obra::where('nome', 'Obra sem código')->value('codigo'))->toBeNull();
});

it('rejeita término previsto antes do início', function () {
    asAdmin();

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('nome', 'Obra de teste')
        ->set('data_inicio', '2026-01-01')
        ->set('data_termino_previsto', '2025-12-01')
        ->set('status', 'planejamento')
        ->call('save')
        ->assertHasErrors('data_termino_previsto');
});

it('detecta obra atrasada', function () {
    $atrasada = Obra::factory()->atrasada()->create();
    $no_prazo = Obra::factory()->emAndamento()->create([
        'data_termino_previsto' => now()->addMonths(2),
    ]);
    $concluida = Obra::factory()->concluida()->create([
        'data_termino_previsto' => now()->subDays(60), // passou, mas tá concluída
    ]);

    expect($atrasada->atrasada)->toBeTrue();
    expect($no_prazo->atrasada)->toBeFalse();
    expect($concluida->atrasada)->toBeFalse();
});

it('marca obra como concluída e preenche data_termino_real', function () {
    asAdmin();
    $obra = Obra::factory()->emAndamento()->create(['data_termino_real' => null]);

    Livewire::test(Gerenciar::class)->call('concluir', $obra->id);

    $fresh = $obra->fresh();
    expect($fresh->status)->toBe(Obra::STATUS_CONCLUIDA);
    expect($fresh->data_termino_real)->not->toBeNull();
});

it('Policy não permite excluir obra concluída (mesmo para quem tem permissão delete)', function () {
    $obra = Obra::factory()->concluida()->create();
    $user = \App\Models\User::factory()->create();
    $user->givePermissionTo('obras.delete');

    // Mesmo com a permissão obras.delete, a Policy deve bloquear obras concluídas
    expect($user->can('delete', $obra))->toBeFalse();

    // Para uma obra não-concluída, o mesmo user pode excluir
    $obraAtiva = Obra::factory()->emAndamento()->create();
    expect($user->can('delete', $obraAtiva))->toBeTrue();
});

it('busca obra por nome', function () {
    asAdmin();
    Obra::factory()->create(['nome' => 'Pavimentação Centro']);
    Obra::factory()->create(['nome' => 'Reforma Escolar']);

    Livewire::test(Gerenciar::class)
        ->set('search', 'Pavimentação')
        ->assertViewHas('obras', fn ($p) => $p->total() === 1);
});

it('filtra por status', function () {
    asAdmin();
    Obra::factory()->emAndamento()->count(3)->create();
    Obra::factory()->concluida()->count(2)->create();

    Livewire::test(Gerenciar::class)
        ->set('filterStatus', 'em_andamento')
        ->assertViewHas('obras', fn ($p) => $p->total() === 3);
});

it('calcula stats no dashboard', function () {
    asAdmin();
    Obra::factory()->emAndamento()->count(2)->create();
    Obra::factory()->atrasada()->create();
    Obra::factory()->state(['status' => 'planejamento'])->create();

    $componente = Livewire::test(Gerenciar::class);
    $stats = $componente->viewData('stats');

    expect($stats['em_andamento'])->toBe(3); // 2 em_andamento + 1 atrasada (que é em_andamento)
    expect($stats['planejamento'])->toBe(1);
    expect($stats['atrasadas'])->toBe(1);
});
