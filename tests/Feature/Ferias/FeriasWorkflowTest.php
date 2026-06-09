<?php

declare(strict_types=1);

use App\Livewire\Ferias\Gerenciar;
use App\Models\Colaborador;
use App\Models\Ferias;
use Livewire\Livewire;

it('respeita mínimo de 5 dias de gozo (CLT)', function () {
    asAdmin();
    $colaborador = Colaborador::factory()->create();

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('colaborador_id', $colaborador->id)
        ->set('periodo_aquisitivo_inicio', '2024-01-01')
        ->set('periodo_aquisitivo_fim', '2024-12-31')
        ->set('data_inicio_gozo', '2025-02-01')
        ->set('data_fim_gozo', '2025-02-03')
        ->set('dias_gozo', 3) // menor que mínimo
        ->call('save')
        ->assertHasErrors('dias_gozo');
});

it('respeita máximo de 30 dias por período aquisitivo', function () {
    asAdmin();
    $colaborador = Colaborador::factory()->create();

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('colaborador_id', $colaborador->id)
        ->set('periodo_aquisitivo_inicio', '2024-01-01')
        ->set('periodo_aquisitivo_fim', '2024-12-31')
        ->set('data_inicio_gozo', '2025-02-01')
        ->set('data_fim_gozo', '2025-03-15')
        ->set('dias_gozo', 35)
        ->call('save')
        ->assertHasErrors('dias_gozo');
});

it('limita abono a 10 dias (1/3 das férias)', function () {
    asAdmin();
    $colaborador = Colaborador::factory()->create();

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('colaborador_id', $colaborador->id)
        ->set('periodo_aquisitivo_inicio', '2024-01-01')
        ->set('periodo_aquisitivo_fim', '2024-12-31')
        ->set('data_inicio_gozo', '2025-02-01')
        ->set('data_fim_gozo', '2025-02-15')
        ->set('dias_gozo', 15)
        ->set('abono_pecuniario', true)
        ->set('dias_abono', 15)
        ->call('save')
        ->assertHasErrors('dias_abono');
});

it('rejeita total gozo + abono acima de 30', function () {
    asAdmin();
    $colaborador = Colaborador::factory()->create();

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('colaborador_id', $colaborador->id)
        ->set('periodo_aquisitivo_inicio', '2024-01-01')
        ->set('periodo_aquisitivo_fim', '2024-12-31')
        ->set('data_inicio_gozo', '2025-02-01')
        ->set('data_fim_gozo', '2025-02-28')
        ->set('dias_gozo', 28)
        ->set('abono_pecuniario', true)
        ->set('dias_abono', 10)
        ->call('save')
        ->assertHasErrors('dias_gozo');
});

it('aceita configuração válida (20 gozo + 10 abono)', function () {
    asAdmin();
    $colaborador = Colaborador::factory()->create();

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('colaborador_id', $colaborador->id)
        ->set('periodo_aquisitivo_inicio', '2024-01-01')
        ->set('periodo_aquisitivo_fim', '2024-12-31')
        ->set('data_inicio_gozo', '2025-02-01')
        ->set('data_fim_gozo', '2025-02-20')
        ->set('dias_gozo', 20)
        ->set('abono_pecuniario', true)
        ->set('dias_abono', 10)
        ->call('save')
        ->assertHasNoErrors();

    expect(Ferias::count())->toBe(1);
});

it('workflow completo: programada → aprovada → em gozo → concluída', function () {
    asAdmin();
    $ferias = Ferias::factory()->programada()->create();

    // Aprovar
    Livewire::test(Gerenciar::class)
        ->call('abrirAprovacao', $ferias->id, 'aprovar')
        ->set('approvalObs', '')
        ->call('confirmarAprovacao');

    expect($ferias->fresh()->status)->toBe(Ferias::STATUS_APROVADA);

    // Iniciar gozo
    Livewire::test(Gerenciar::class)->call('iniciarGozo', $ferias->id);
    expect($ferias->fresh()->status)->toBe(Ferias::STATUS_EM_GOZO);

    // Concluir
    Livewire::test(Gerenciar::class)->call('concluir', $ferias->id);
    expect($ferias->fresh()->status)->toBe(Ferias::STATUS_CONCLUIDA);
});

it('exige motivo na rejeição', function () {
    asAdmin();
    $ferias = Ferias::factory()->programada()->create();

    Livewire::test(Gerenciar::class)
        ->call('abrirAprovacao', $ferias->id, 'rejeitar')
        ->set('approvalObs', '')
        ->call('confirmarAprovacao')
        ->assertHasErrors('approvalObs');

    expect($ferias->fresh()->status)->toBe(Ferias::STATUS_PROGRAMADA);
});

it('não permite iniciar gozo de férias não aprovadas', function () {
    asAdmin();
    $ferias = Ferias::factory()->programada()->create();

    Livewire::test(Gerenciar::class)->call('iniciarGozo', $ferias->id);

    expect($ferias->fresh()->status)->toBe(Ferias::STATUS_PROGRAMADA);
});

it('não permite concluir férias que não estão em gozo', function () {
    asAdmin();
    $ferias = Ferias::factory()->aprovada()->create();

    Livewire::test(Gerenciar::class)->call('concluir', $ferias->id);

    expect($ferias->fresh()->status)->toBe(Ferias::STATUS_APROVADA);
});

it('não permite editar férias concluídas', function () {
    asUser('gestor_rh');
    $ferias = Ferias::factory()->concluida()->create();

    Livewire::test(Gerenciar::class)
        ->call('openEdit', $ferias->id)
        ->assertForbidden();
});

it('calcula total de dias usados e restantes', function () {
    $ferias = Ferias::factory()->make([
        'dias_gozo' => 20,
        'dias_abono' => 10,
    ]);

    expect($ferias->total_dias_usados)->toBe(30);
    expect($ferias->dias_restantes)->toBe(0);
});

it('detecta gozo em curso pela data', function () {
    $emCurso = Ferias::factory()->emGozo()->create();
    $futura = Ferias::factory()->aprovada()->create([
        'data_inicio_gozo' => now()->addDays(30),
        'data_fim_gozo' => now()->addDays(45),
    ]);

    expect($emCurso->em_gozo_hoje)->toBeTrue();
    expect($futura->em_gozo_hoje)->toBeFalse();
});
