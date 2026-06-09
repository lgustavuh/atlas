<?php

declare(strict_types=1);

use App\Livewire\TransporteHospedagem\Gerenciar;
use App\Models\Colaborador;
use App\Models\Obra;
use App\Models\TransporteHospedagem;
use Livewire\Livewire;

it('admin pode criar registro de transporte', function () {
    asAdmin();
    $col = Colaborador::factory()->create();

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('tipo', 'transporte')
        ->set('colaborador_id', $col->id)
        ->set('data_inicio', now()->toDateString())
        ->set('origem', 'Itaú de Minas - MG')
        ->set('destino', 'Belo Horizonte - MG')
        ->set('meio_transporte', 'onibus')
        ->call('save')
        ->assertHasNoErrors();

    expect(TransporteHospedagem::where('destino', 'Belo Horizonte - MG')->exists())->toBeTrue();
});

it('admin pode criar registro de hospedagem', function () {
    asAdmin();
    $col = Colaborador::factory()->create();

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('tipo', 'hospedagem')
        ->set('colaborador_id', $col->id)
        ->set('data_inicio', now()->toDateString())
        ->set('hospedagem_local', 'Hotel Central')
        ->set('hospedagem_endereco', 'Av. Brasil, 100')
        ->call('save')
        ->assertHasNoErrors();

    $reg = TransporteHospedagem::where('hospedagem_local', 'Hotel Central')->first();
    expect($reg)->not->toBeNull();
    expect($reg->origem)->toBeNull();
    expect($reg->meio_transporte)->toBeNull();
});

it('admin pode criar registro de ambos', function () {
    asAdmin();
    $col = Colaborador::factory()->create();

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('tipo', 'ambos')
        ->set('colaborador_id', $col->id)
        ->set('data_inicio', now()->toDateString())
        ->set('origem', 'Itaú de Minas')
        ->set('destino', 'São Paulo')
        ->set('meio_transporte', 'aviao')
        ->set('hospedagem_local', 'Hotel SP')
        ->call('save')
        ->assertHasNoErrors();

    $reg = TransporteHospedagem::where('destino', 'São Paulo')->first();
    expect($reg->tipo)->toBe('ambos');
    expect($reg->temTransporte())->toBeTrue();
    expect($reg->temHospedagem())->toBeTrue();
});

it('tipo transporte exige origem, destino e meio', function () {
    asAdmin();
    $col = Colaborador::factory()->create();

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('tipo', 'transporte')
        ->set('colaborador_id', $col->id)
        ->set('data_inicio', now()->toDateString())
        // sem origem/destino/meio
        ->call('save')
        ->assertHasErrors(['origem', 'destino', 'meio_transporte']);
});

it('tipo hospedagem exige local', function () {
    asAdmin();
    $col = Colaborador::factory()->create();

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('tipo', 'hospedagem')
        ->set('colaborador_id', $col->id)
        ->set('data_inicio', now()->toDateString())
        ->call('save')
        ->assertHasErrors('hospedagem_local');
});

it('rejeita data_fim antes de data_inicio', function () {
    asAdmin();
    $col = Colaborador::factory()->create();

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('tipo', 'transporte')
        ->set('colaborador_id', $col->id)
        ->set('data_inicio', '2026-06-15')
        ->set('data_fim', '2026-06-10')
        ->set('origem', 'A')
        ->set('destino', 'B')
        ->set('meio_transporte', 'onibus')
        ->call('save')
        ->assertHasErrors('data_fim');
});

it('limpa campos não aplicáveis ao mudar tipo no edit', function () {
    asAdmin();
    $col = Colaborador::factory()->create();
    // Cria como ambos
    $reg = TransporteHospedagem::factory()->ambos()->create(['colaborador_id' => $col->id]);
    expect($reg->origem)->not->toBeNull();
    expect($reg->hospedagem_local)->not->toBeNull();

    // Edita para apenas transporte
    Livewire::test(Gerenciar::class)
        ->call('openEdit', $reg->id)
        ->set('tipo', 'transporte')
        ->call('save')
        ->assertHasNoErrors();

    $fresh = $reg->fresh();
    expect($fresh->origem)->not->toBeNull();
    expect($fresh->hospedagem_local)->toBeNull(); // limpou
    expect($fresh->hospedagem_endereco)->toBeNull();
});

it('detecta status temporal corretamente', function () {
    $futuro = TransporteHospedagem::factory()->futuro()->create();
    expect($futuro->status_temporal)->toBe('futura');

    $emAndamento = TransporteHospedagem::factory()->emAndamento()->create();
    expect($emAndamento->status_temporal)->toBe('em_andamento');

    $passado = TransporteHospedagem::factory()->create([
        'data_inicio' => now()->subDays(20),
        'data_fim' => now()->subDays(15),
    ]);
    expect($passado->status_temporal)->toBe('concluida');
});

it('scope emAndamento filtra corretamente', function () {
    TransporteHospedagem::factory()->emAndamento()->count(2)->create();
    TransporteHospedagem::factory()->futuro()->create();
    TransporteHospedagem::factory()->create([
        'data_inicio' => now()->subDays(20),
        'data_fim' => now()->subDays(15),
    ]);

    expect(TransporteHospedagem::emAndamento()->count())->toBe(2);
});

it('filtra por colaborador', function () {
    asAdmin();
    $col1 = Colaborador::factory()->create();
    $col2 = Colaborador::factory()->create();
    TransporteHospedagem::factory()->count(3)->create(['colaborador_id' => $col1->id]);
    TransporteHospedagem::factory()->count(2)->create(['colaborador_id' => $col2->id]);

    Livewire::test(Gerenciar::class)
        ->set('filterColaboradorId', $col1->id)
        ->assertViewHas('registros', fn ($p) => $p->total() === 3);
});

it('filtra por obra', function () {
    asAdmin();
    $obra = Obra::factory()->create();
    TransporteHospedagem::factory()->count(2)->create(['obra_id' => $obra->id]);
    TransporteHospedagem::factory()->create(['obra_id' => null]);

    Livewire::test(Gerenciar::class)
        ->set('filterObraId', $obra->id)
        ->assertViewHas('registros', fn ($p) => $p->total() === 2);
});

it('busca por origem/destino/colaborador', function () {
    asAdmin();
    $col = Colaborador::factory()->create(['nome' => 'João da Silva']);
    TransporteHospedagem::factory()->create([
        'colaborador_id' => $col->id,
        'destino' => 'Brasília',
    ]);
    TransporteHospedagem::factory()->create(['destino' => 'Recife']);

    Livewire::test(Gerenciar::class)
        ->set('search', 'Brasília')
        ->assertViewHas('registros', fn ($p) => $p->total() === 1);
});
