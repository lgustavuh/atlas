<?php

declare(strict_types=1);

use App\Livewire\Auditoria\Consultar;
use App\Models\Colaborador;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

it('admin pode acessar a tela', function () {
    asAdmin();

    Livewire::test(Consultar::class)
        ->assertOk();
});

it('usuário sem permissão audit.view-any recebe 403', function () {
    $user = User::factory()->create();
    $user->assignRole('colaborador'); // colaborador não tem audit.view-any
    $this->actingAs($user);

    Livewire::test(Consultar::class)
        ->assertForbidden();
});

it('lista atividades geradas por criação de modelo', function () {
    asAdmin();
    // Criar um colaborador gera atividade automática (LogsActivity)
    Colaborador::factory()->create(['nome' => 'Maria Teste']);

    Livewire::test(Consultar::class)
        ->assertViewHas('atividades', fn ($p) => $p->total() > 0);
});

it('filtra por log_name (módulo)', function () {
    asAdmin();
    $colab = Colaborador::factory()->create();
    // Criar pedido para gerar atividade em outro módulo
    \App\Models\Vaga::factory()->create();

    // Atividade do colaborador deve estar isolada quando filtrar por colaborador
    Livewire::test(Consultar::class)
        ->set('filterLogName', 'colaborador')
        ->assertViewHas('atividades', function ($p) {
            return $p->getCollection()->every(fn ($a) => $a->log_name === 'colaborador');
        });
});

it('filtra por evento', function () {
    asAdmin();
    $colab = Colaborador::factory()->create(); // created
    $colab->update(['observacoes' => 'atualizado']); // updated

    Livewire::test(Consultar::class)
        ->set('filterEvent', 'created')
        ->assertViewHas('atividades', function ($p) {
            return $p->getCollection()->every(fn ($a) => $a->event === 'created');
        });
});

it('filtra por usuário (causer)', function () {
    $u1 = User::factory()->create();
    $u1->assignRole('admin');
    $u2 = User::factory()->create();
    $u2->assignRole('admin');

    // u1 cria colaborador
    auth()->login($u1);
    Colaborador::factory()->create();

    // u2 cria outro
    auth()->login($u2);
    Colaborador::factory()->create();

    // Logado como u1 (admin), filtrando por u1
    auth()->login($u1);

    Livewire::test(Consultar::class)
        ->set('filterCauserId', $u1->id)
        ->assertViewHas('atividades', function ($p) use ($u1) {
            return $p->getCollection()->every(fn ($a) => $a->causer_id === $u1->id);
        });
});

it('filtra por intervalo de datas', function () {
    asAdmin();

    // Cria atividade hoje
    Colaborador::factory()->create();

    // Cria atividade antiga manualmente
    Activity::create([
        'log_name' => 'teste',
        'description' => 'evento antigo',
        'created_at' => now()->subDays(10),
        'updated_at' => now()->subDays(10),
    ]);

    Livewire::test(Consultar::class)
        ->set('filterDataDe', now()->subDays(1)->toDateString())
        ->set('filterDataAte', now()->toDateString())
        ->assertViewHas('atividades', function ($p) {
            return $p->getCollection()->every(fn ($a) => $a->created_at->isToday());
        });
});

it('busca textual na descrição', function () {
    asAdmin();
    Activity::create([
        'log_name' => 'teste',
        'description' => 'pagamento aprovado pelo gestor',
    ]);
    Activity::create([
        'log_name' => 'teste',
        'description' => 'compra cancelada',
    ]);

    Livewire::test(Consultar::class)
        ->set('search', 'pagamento')
        ->assertViewHas('atividades', fn ($p) => $p->total() === 1);
});

it('limpar filtros zera todos os campos de busca', function () {
    asAdmin();

    $componente = Livewire::test(Consultar::class)
        ->set('search', 'algo')
        ->set('filterLogName', 'colaborador')
        ->set('filterEvent', 'created');

    $componente->call('limparFiltros');

    expect($componente->get('search'))->toBe('');
    expect($componente->get('filterLogName'))->toBe('');
    expect($componente->get('filterEvent'))->toBe('');
});

it('abrirDetalhe carrega o registro selecionado', function () {
    asAdmin();
    $colab = Colaborador::factory()->create();
    $act = Activity::query()->orderBy('id')->first();

    Livewire::test(Consultar::class)
        ->call('abrirDetalhe', $act->id)
        ->assertViewHas('detalhe', fn ($d) => $d?->id === $act->id);
});

it('stats contam atividades de hoje e última hora', function () {
    asAdmin();
    // Limpa o log para isolar o teste
    Activity::truncate();

    // 2 hoje
    Activity::create(['log_name' => 't', 'description' => 'a']);
    Activity::create(['log_name' => 't', 'description' => 'b']);

    // 1 antiga
    Activity::create([
        'log_name' => 't',
        'description' => 'antiga',
        'created_at' => now()->subDays(5),
        'updated_at' => now()->subDays(5),
    ]);

    $stats = Livewire::test(Consultar::class)->viewData('stats');

    expect($stats['total'])->toBe(3);
    expect($stats['hoje'])->toBe(2);
});
