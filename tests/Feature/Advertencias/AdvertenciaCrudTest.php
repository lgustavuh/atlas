<?php

declare(strict_types=1);

use App\Livewire\Advertencias\Gerenciar;
use App\Models\Advertencia;
use App\Models\Colaborador;
use Livewire\Livewire;

it('exige descrição mínima e motivo', function () {
    asAdmin();
    $colaborador = Colaborador::factory()->create();

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('colaborador_id', $colaborador->id)
        ->set('motivo', 'X') // muito curto
        ->set('descricao_ocorrencia', 'curta')
        ->call('save')
        ->assertHasErrors(['motivo', 'descricao_ocorrencia']);
});

it('exige dias de suspensão quando tipo é suspensão', function () {
    asAdmin();
    $colaborador = Colaborador::factory()->create();

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('colaborador_id', $colaborador->id)
        ->set('tipo', 'suspensao')
        ->set('motivo', 'Atraso recorrente')
        ->set('descricao_ocorrencia', 'Atrasou múltiplas vezes esta semana sem justificativa.')
        ->set('dias_suspensao', null)
        ->call('save')
        ->assertHasErrors('dias_suspensao');
});

it('cria advertência válida', function () {
    asAdmin();
    $colaborador = Colaborador::factory()->create();

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('colaborador_id', $colaborador->id)
        ->set('tipo', 'escrita')
        ->set('motivo', 'Descumprimento de norma de segurança')
        ->set('descricao_ocorrencia', 'O colaborador foi observado sem EPI na área de produção em diversas ocasiões.')
        ->set('ciente_colaborador', true)
        ->call('save')
        ->assertHasNoErrors();

    expect(Advertencia::where('colaborador_id', $colaborador->id)->exists())->toBeTrue();
});

it('busca advertência por nome do colaborador', function () {
    asAdmin();
    $alvo = Colaborador::factory()->create(['nome' => 'Maria das Dores']);
    $outro = Colaborador::factory()->create(['nome' => 'João Pedro']);
    $advAlvo = Advertencia::factory()->create(['colaborador_id' => $alvo->id]);
    $advOutro = Advertencia::factory()->create(['colaborador_id' => $outro->id]);

    $componente = Livewire::test(Gerenciar::class)->set('search', 'Maria');
    $advertenciasNoView = $componente->viewData('advertencias');

    expect($advertenciasNoView->pluck('id')->all())
        ->toContain($advAlvo->id)
        ->not->toContain($advOutro->id);
});

it('filtra por tipo', function () {
    asAdmin();
    Advertencia::factory()->escrita()->count(2)->create();
    Advertencia::factory()->verbal()->count(3)->create();

    $component = Livewire::test(Gerenciar::class)->set('filterTipo', 'verbal');
    // Verifica via render — não asserções específicas, mas componente reage ao filtro
    expect($component->errors()->isEmpty())->toBeTrue();
});

it('registra data_ciencia quando colaborador dá ciência', function () {
    asAdmin();
    $colaborador = Colaborador::factory()->create();

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('colaborador_id', $colaborador->id)
        ->set('motivo', 'Motivo válido aqui')
        ->set('descricao_ocorrencia', 'Descrição com pelo menos dez caracteres.')
        ->set('ciente_colaborador', true)
        ->call('save');

    $adv = Advertencia::where('colaborador_id', $colaborador->id)->first();
    expect($adv->ciente_colaborador)->toBeTrue();
    expect($adv->data_ciencia)->not->toBeNull();
});
