<?php

declare(strict_types=1);

use App\Livewire\Alertas\Banner;
use App\Livewire\Alertas\Gerenciar;
use App\Models\AlertaAdm;
use App\Models\AlertaAdmDestinatario;
use App\Models\Colaborador;
use App\Models\User;
use App\Services\AlertaAdmService;
use Livewire\Livewire;

it('admin pode criar alerta com destinatários', function () {
    asAdmin();
    $col1 = Colaborador::factory()->create();
    $col2 = Colaborador::factory()->create();

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('titulo', 'Recadastramento obrigatório')
        ->set('mensagem', 'Todos devem atualizar dados até o fim do mês.')
        ->set('prioridade', 'alta')
        ->set('colaboradorIds', [$col1->id, $col2->id])
        ->call('save')
        ->assertHasNoErrors();

    $alerta = AlertaAdm::where('titulo', 'Recadastramento obrigatório')->first();
    expect($alerta)->not->toBeNull();
    expect($alerta->destinatarios)->toHaveCount(2);
});

it('rejeita título curto', function () {
    asAdmin();

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('titulo', 'OK')
        ->set('mensagem', 'mensagem válida aqui')
        ->call('save')
        ->assertHasErrors('titulo');
});

it('rejeita mensagem curta', function () {
    asAdmin();

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('titulo', 'Título OK')
        ->set('mensagem', 'oi')
        ->call('save')
        ->assertHasErrors('mensagem');
});

it('rejeita data_fim antes de data_inicio', function () {
    asAdmin();

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('titulo', 'Título válido')
        ->set('mensagem', 'mensagem válida')
        ->set('data_inicio', '2026-06-01')
        ->set('data_fim', '2026-05-01')
        ->call('save')
        ->assertHasErrors('data_fim');
});

it('vigente = ativo e dentro da janela', function () {
    $vigente = AlertaAdm::factory()->create();
    expect($vigente->vigente)->toBeTrue();

    $inativo = AlertaAdm::factory()->inativo()->create();
    expect($inativo->vigente)->toBeFalse();

    $expirado = AlertaAdm::factory()->expirado()->create();
    expect($expirado->vigente)->toBeFalse();

    $futuro = AlertaAdm::factory()->futuro()->create();
    expect($futuro->vigente)->toBeFalse();
});

it('scope vigentes retorna só os ativos dentro da janela', function () {
    AlertaAdm::factory()->create(); // vigente
    AlertaAdm::factory()->inativo()->create();
    AlertaAdm::factory()->expirado()->create();
    AlertaAdm::factory()->futuro()->create();

    expect(AlertaAdm::vigentes()->count())->toBe(1);
});

it('marca como visualizado e fica idempotente', function () {
    $service = app(AlertaAdmService::class);
    $col = Colaborador::factory()->create();
    $alerta = AlertaAdm::factory()->create();
    $alerta->destinatarios()->create(['colaborador_id' => $col->id]);

    $primeiraVez = $service->marcarVisualizado($alerta->id, $col->id);
    expect($primeiraVez)->toBeTrue();

    $segundaVez = $service->marcarVisualizado($alerta->id, $col->id);
    expect($segundaVez)->toBeFalse(); // já estava visualizado

    $registro = AlertaAdmDestinatario::where('alerta_adm_id', $alerta->id)
        ->where('colaborador_id', $col->id)->first();
    expect($registro->visualizado_em)->not->toBeNull();
});

it('toggle ativo alterna estado', function () {
    asAdmin();
    $alerta = AlertaAdm::factory()->create(['ativo' => true]);

    Livewire::test(Gerenciar::class)->call('toggleAtivo', $alerta->id);
    expect($alerta->fresh()->ativo)->toBeFalse();

    Livewire::test(Gerenciar::class)->call('toggleAtivo', $alerta->id);
    expect($alerta->fresh()->ativo)->toBeTrue();
});

it('enviarParaTodos preenche destinatários com todos os colaboradores ativos', function () {
    $service = app(AlertaAdmService::class);
    Colaborador::factory()->count(3)->create();
    $alerta = AlertaAdm::factory()->create();

    $total = $service->enviarParaTodos($alerta);
    expect($total)->toBe(3);
    expect($alerta->destinatarios()->count())->toBe(3);
});

it('atualizar não perde visualizado_em de destinatários mantidos', function () {
    $service = app(AlertaAdmService::class);
    $col1 = Colaborador::factory()->create();
    $col2 = Colaborador::factory()->create();
    $col3 = Colaborador::factory()->create();

    $alerta = AlertaAdm::factory()->create();
    // Cria 2 destinatários
    $alerta->destinatarios()->create(['colaborador_id' => $col1->id, 'visualizado_em' => now()]);
    $alerta->destinatarios()->create(['colaborador_id' => $col2->id]);

    // Sync: mantém col1, remove col2, adiciona col3
    $service->atualizar($alerta, ['titulo' => $alerta->titulo, 'mensagem' => $alerta->mensagem,
        'prioridade' => $alerta->prioridade, 'ativo' => true,
        'data_inicio' => null, 'data_fim' => null], [$col1->id, $col3->id]);

    $col1Dest = AlertaAdmDestinatario::where('alerta_adm_id', $alerta->id)
        ->where('colaborador_id', $col1->id)->first();

    expect($col1Dest)->not->toBeNull();
    expect($col1Dest->visualizado_em)->not->toBeNull(); // preservado
    expect(AlertaAdmDestinatario::where('alerta_adm_id', $alerta->id)
        ->where('colaborador_id', $col2->id)->exists())->toBeFalse();
    expect(AlertaAdmDestinatario::where('alerta_adm_id', $alerta->id)
        ->where('colaborador_id', $col3->id)->exists())->toBeTrue();
});

it('banner mostra alertas pendentes para colaborador autenticado', function () {
    $col = Colaborador::factory()->create();
    $user = User::factory()->create(['colaborador_id' => $col->id]);
    $user->assignRole('visualizador');

    $alerta = AlertaAdm::factory()->create();
    $alerta->destinatarios()->create(['colaborador_id' => $col->id]);

    auth()->login($user);

    Livewire::test(Banner::class)
        ->assertSee($alerta->titulo);
});

it('banner não mostra alerta já visualizado', function () {
    $col = Colaborador::factory()->create();
    $user = User::factory()->create(['colaborador_id' => $col->id]);
    $user->assignRole('visualizador');

    $alerta = AlertaAdm::factory()->create();
    $alerta->destinatarios()->create(['colaborador_id' => $col->id, 'visualizado_em' => now()]);

    auth()->login($user);

    Livewire::test(Banner::class)
        ->assertDontSee($alerta->titulo);
});

it('descartar marca como visualizado', function () {
    $col = Colaborador::factory()->create();
    $user = User::factory()->create(['colaborador_id' => $col->id]);
    $user->assignRole('visualizador');

    $alerta = AlertaAdm::factory()->create();
    $alerta->destinatarios()->create(['colaborador_id' => $col->id]);

    auth()->login($user);

    Livewire::test(Banner::class)->call('descartar', $alerta->id);

    $registro = AlertaAdmDestinatario::where('alerta_adm_id', $alerta->id)
        ->where('colaborador_id', $col->id)->first();
    expect($registro->visualizado_em)->not->toBeNull();
});

it('busca alerta por título', function () {
    asAdmin();
    AlertaAdm::factory()->create(['titulo' => 'Recadastramento obrigatório']);
    AlertaAdm::factory()->create(['titulo' => 'Reunião mensal']);

    Livewire::test(Gerenciar::class)
        ->set('search', 'Recadastramento')
        ->assertViewHas('alertas', fn ($p) => $p->total() === 1);
});
