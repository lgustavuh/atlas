<?php

declare(strict_types=1);

use App\Livewire\Republicas\Gerenciar;
use App\Livewire\Republicas\Ocupacoes;
use App\Models\Colaborador;
use App\Models\Republica;
use App\Models\RepublicaOcupacao;
use App\Models\User;
use App\Services\RepublicaService;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

it('admin pode criar república', function () {
    asAdmin();

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('nome', 'República dos Bandeirantes')
        ->set('endereco', 'Rua das Flores, 123')
        ->set('capacidade_total', 6)
        ->set('aluguel_mensal', 2500)
        ->call('save')
        ->assertHasNoErrors();

    expect(Republica::where('nome', 'República dos Bandeirantes')->exists())->toBeTrue();
});

it('rejeita nome muito curto', function () {
    asAdmin();
    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('nome', 'A')
        ->set('endereco', 'endereço válido aqui')
        ->set('capacidade_total', 4)
        ->call('save')
        ->assertHasErrors('nome');
});

it('rejeita capacidade zero', function () {
    asAdmin();
    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('nome', 'Nome válido')
        ->set('endereco', 'endereço válido aqui')
        ->set('capacidade_total', 0)
        ->call('save')
        ->assertHasErrors('capacidade_total');
});

it('alocar adiciona ocupação', function () {
    $service = app(RepublicaService::class);
    $republica = Republica::factory()->create(['capacidade_total' => 4]);
    $col = Colaborador::factory()->create();

    $ocup = $service->alocar($republica, $col->id, now()->toDateString());

    expect($ocup->id)->not->toBeNull();
    expect($ocup->republica_id)->toBe($republica->id);
    expect($ocup->colaborador_id)->toBe($col->id);
    expect($republica->fresh()->ocupacoesAtuais()->count())->toBe(1);
});

it('alocar bloqueia se república está lotada', function () {
    $service = app(RepublicaService::class);
    $republica = Republica::factory()->create(['capacidade_total' => 2]);

    // Lota a república
    foreach (Colaborador::factory()->count(2)->create() as $c) {
        $service->alocar($republica, $c->id, now()->toDateString());
    }

    // Tenta alocar terceiro
    $novo = Colaborador::factory()->create();
    expect(fn () => $service->alocar($republica, $novo->id, now()->toDateString()))
        ->toThrow(ValidationException::class);
});

it('alocar bloqueia colaborador já em outra república', function () {
    $service = app(RepublicaService::class);
    $r1 = Republica::factory()->create(['capacidade_total' => 4]);
    $r2 = Republica::factory()->create(['capacidade_total' => 4]);
    $col = Colaborador::factory()->create();

    $service->alocar($r1, $col->id, now()->toDateString());

    expect(fn () => $service->alocar($r2, $col->id, now()->toDateString()))
        ->toThrow(ValidationException::class);
});

it('darSaida encerra ocupação com data', function () {
    $service = app(RepublicaService::class);
    $republica = Republica::factory()->create();
    $col = Colaborador::factory()->create();

    $ocup = $service->alocar($republica, $col->id, now()->subDays(30)->toDateString());
    $service->darSaida($ocup, now()->toDateString());

    expect($ocup->fresh()->data_saida)->not->toBeNull();
    // E libera vaga
    expect($republica->fresh()->ocupacoesAtuais()->count())->toBe(0);
});

it('darSaida bloqueia se data_saida anterior à entrada', function () {
    $service = app(RepublicaService::class);
    $republica = Republica::factory()->create();
    $col = Colaborador::factory()->create();
    $ocup = $service->alocar($republica, $col->id, now()->toDateString());

    expect(fn () => $service->darSaida($ocup, now()->subDays(10)->toDateString()))
        ->toThrow(ValidationException::class);
});

it('darSaida bloqueia se ocupação já encerrada', function () {
    $service = app(RepublicaService::class);
    $republica = Republica::factory()->create();
    $col = Colaborador::factory()->create();

    $ocup = $service->alocar($republica, $col->id, now()->subDays(30)->toDateString());
    $service->darSaida($ocup, now()->subDays(10)->toDateString());

    expect(fn () => $service->darSaida($ocup->fresh(), now()->toDateString()))
        ->toThrow(ValidationException::class);
});

it('mesmo colaborador pode voltar depois de sair', function () {
    $service = app(RepublicaService::class);
    $republica = Republica::factory()->create();
    $col = Colaborador::factory()->create();

    // Entra, sai, entra de novo
    $ocup1 = $service->alocar($republica, $col->id, now()->subDays(60)->toDateString());
    $service->darSaida($ocup1, now()->subDays(30)->toDateString());

    // Deve permitir realocar
    $ocup2 = $service->alocar($republica, $col->id, now()->toDateString());
    expect($ocup2->id)->not->toBe($ocup1->id);
});

it('atualizar bloqueia reduzir capacidade abaixo de ocupantes atuais', function () {
    $service = app(RepublicaService::class);
    $republica = Republica::factory()->create(['capacidade_total' => 4]);

    // Coloca 3 ocupantes
    foreach (Colaborador::factory()->count(3)->create() as $c) {
        $service->alocar($republica, $c->id, now()->toDateString());
    }

    expect(fn () => $service->atualizar($republica, ['capacidade_total' => 2]))
        ->toThrow(ValidationException::class);
});

it('Policy não permite excluir república com ocupantes', function () {
    $republica = Republica::factory()->create();
    $col = Colaborador::factory()->create();
    app(RepublicaService::class)->alocar($republica, $col->id, now()->toDateString());

    $user = User::factory()->create();
    $user->givePermissionTo('republicas.delete');

    expect($user->can('delete', $republica))->toBeFalse();
});

it('Policy permite excluir república sem ocupantes', function () {
    $republica = Republica::factory()->create();
    $user = User::factory()->create();
    $user->givePermissionTo('republicas.delete');

    expect($user->can('delete', $republica))->toBeTrue();
});

it('calcula vagas_disponiveis e percentual_ocupacao', function () {
    $service = app(RepublicaService::class);
    $republica = Republica::factory()->create(['capacidade_total' => 4]);
    foreach (Colaborador::factory()->count(3)->create() as $c) {
        $service->alocar($republica, $c->id, now()->toDateString());
    }

    $fresh = Republica::withCount('ocupacoesAtuais')->find($republica->id);
    expect($fresh->vagas_disponiveis)->toBe(1);
    expect($fresh->percentual_ocupacao)->toBe(75);
    expect($fresh->lotada)->toBeFalse();
});

it('detecta república lotada', function () {
    $service = app(RepublicaService::class);
    $republica = Republica::factory()->create(['capacidade_total' => 2]);
    foreach (Colaborador::factory()->count(2)->create() as $c) {
        $service->alocar($republica, $c->id, now()->toDateString());
    }

    $fresh = Republica::withCount('ocupacoesAtuais')->find($republica->id);
    expect($fresh->lotada)->toBeTrue();
    expect($fresh->percentual_ocupacao)->toBe(100);
});

it('busca por nome', function () {
    asAdmin();
    Republica::factory()->create(['nome' => 'Pousada Centro']);
    Republica::factory()->create(['nome' => 'Casa Jardim']);

    Livewire::test(Gerenciar::class)
        ->set('search', 'Pousada')
        ->assertViewHas('republicas', fn ($p) => $p->total() === 1);
});

it('Livewire Ocupacoes mostra ocupantes atuais', function () {
    asAdmin();
    $republica = Republica::factory()->create(['capacidade_total' => 3]);
    $col = Colaborador::factory()->create(['nome' => 'João Silva']);
    app(RepublicaService::class)->alocar($republica, $col->id, now()->toDateString());

    Livewire::test(Ocupacoes::class, ['id' => $republica->id])
        ->assertSee('João Silva');
});

it('Livewire alocar via componente funciona', function () {
    asAdmin();
    $republica = Republica::factory()->create(['capacidade_total' => 3]);
    $col = Colaborador::factory()->create();

    Livewire::test(Ocupacoes::class, ['id' => $republica->id])
        ->call('openAlocar')
        ->set('colaborador_id', $col->id)
        ->set('data_entrada', now()->toDateString())
        ->call('alocar')
        ->assertHasNoErrors();

    expect(RepublicaOcupacao::where('republica_id', $republica->id)->count())->toBe(1);
});
