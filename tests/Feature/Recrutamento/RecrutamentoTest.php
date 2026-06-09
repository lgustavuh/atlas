<?php

declare(strict_types=1);

use App\Livewire\Recrutamento\Candidatos;
use App\Livewire\Recrutamento\Vagas;
use App\Models\Candidato;
use App\Models\User;
use App\Models\Vaga;
use App\Services\CandidatoService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('local');
});

// =========================
// Vagas
// =========================

it('admin pode criar vaga válida', function () {
    asAdmin();

    Livewire::test(Vagas::class)
        ->call('openCreate')
        ->set('titulo', 'Engenheiro Civil')
        ->set('descricao', 'Responsável por projetos e fiscalização de obras públicas.')
        ->set('quantidade_vagas', 2)
        ->set('status', 'rascunho')
        ->call('save')
        ->assertHasNoErrors();

    expect(Vaga::where('titulo', 'Engenheiro Civil')->exists())->toBeTrue();
});

it('rejeita descrição muito curta', function () {
    asAdmin();

    Livewire::test(Vagas::class)
        ->call('openCreate')
        ->set('titulo', 'Cargo válido')
        ->set('descricao', 'curto')
        ->call('save')
        ->assertHasErrors('descricao');
});

it('rejeita salário máximo menor que mínimo', function () {
    asAdmin();

    Livewire::test(Vagas::class)
        ->call('openCreate')
        ->set('titulo', 'Auxiliar')
        ->set('descricao', 'Descrição válida com mais de 10 caracteres')
        ->set('salario_de', 5000)
        ->set('salario_ate', 3000)
        ->call('save')
        ->assertHasErrors('salario_ate');
});

it('rejeita data_fechamento antes de data_abertura', function () {
    asAdmin();

    Livewire::test(Vagas::class)
        ->call('openCreate')
        ->set('titulo', 'Vaga teste datas')
        ->set('descricao', 'Descrição válida com mais de 10 caracteres')
        ->set('data_abertura', '2026-06-01')
        ->set('data_fechamento', '2026-05-01')
        ->call('save')
        ->assertHasErrors('data_fechamento');
});

it('publicar avança do rascunho para aberta', function () {
    asAdmin();
    $vaga = Vaga::factory()->create(['status' => 'rascunho']);

    Livewire::test(Vagas::class)->call('publicar', $vaga->id);

    expect($vaga->fresh()->status)->toBe(Vaga::STATUS_ABERTA);
});

it('publicar não atua em vaga já publicada', function () {
    asAdmin();
    $vaga = Vaga::factory()->aberta()->create();

    Livewire::test(Vagas::class)->call('publicar', $vaga->id);

    // Status não mudou
    expect($vaga->fresh()->status)->toBe(Vaga::STATUS_ABERTA);
});

it('faixa salarial: A combinar quando publicar=false', function () {
    $v = Vaga::factory()->make([
        'salario_de' => 2000, 'salario_ate' => 3000, 'salario_publicar' => false,
    ]);
    expect($v->faixa_salarial)->toBe('A combinar');
});

it('faixa salarial: formata com range', function () {
    $v = Vaga::factory()->make([
        'salario_de' => 2500, 'salario_ate' => 3500, 'salario_publicar' => true,
    ]);
    expect($v->faixa_salarial)->toContain('R$ 2.500,00');
    expect($v->faixa_salarial)->toContain('R$ 3.500,00');
});

it('detecta vaga expirada', function () {
    $expirada = Vaga::factory()->expirada()->create();
    $valida = Vaga::factory()->aberta()->create([
        'data_fechamento' => now()->addMonth(),
    ]);

    expect($expirada->expirada)->toBeTrue();
    expect($valida->expirada)->toBeFalse();
});

it('Policy não permite excluir vaga com candidato contratado', function () {
    $vaga = Vaga::factory()->create();
    Candidato::factory()->contratado()->create(['vaga_id' => $vaga->id]);

    $user = User::factory()->create();
    $user->givePermissionTo('recrutamento.delete');

    expect($user->can('delete', $vaga))->toBeFalse();
});

// =========================
// Candidatos
// =========================

it('admin pode criar candidato', function () {
    asAdmin();
    $vaga = Vaga::factory()->aberta()->create();

    Livewire::test(Candidatos::class)
        ->call('openCreate')
        ->set('vaga_id', $vaga->id)
        ->set('nome', 'João da Silva')
        ->set('email', 'joao@email.com')
        ->call('save')
        ->assertHasNoErrors();

    expect(Candidato::where('email', 'joao@email.com')->exists())->toBeTrue();
});

it('rejeita e-mail inválido no candidato', function () {
    asAdmin();
    $vaga = Vaga::factory()->aberta()->create();

    Livewire::test(Candidatos::class)
        ->call('openCreate')
        ->set('vaga_id', $vaga->id)
        ->set('nome', 'Maria Teste')
        ->set('email', 'email-invalido')
        ->call('save')
        ->assertHasErrors('email');
});

it('rejeita pontuação acima de 100', function () {
    asAdmin();
    $vaga = Vaga::factory()->aberta()->create();

    Livewire::test(Candidatos::class)
        ->call('openCreate')
        ->set('vaga_id', $vaga->id)
        ->set('nome', 'Pedro')
        ->set('email', 'pedro@email.com')
        ->set('pontuacao', 150)
        ->call('save')
        ->assertHasErrors('pontuacao');
});

it('CPF é armazenado só com dígitos', function () {
    asAdmin();
    $vaga = Vaga::factory()->aberta()->create();

    Livewire::test(Candidatos::class)
        ->call('openCreate')
        ->set('vaga_id', $vaga->id)
        ->set('nome', 'Ana')
        ->set('email', 'ana@email.com')
        ->set('cpf', '111.444.777-35')
        ->call('save');

    $cand = Candidato::where('email', 'ana@email.com')->first();
    expect($cand->cpf)->toBe('11144477735');
});

it('aceita upload de currículo PDF', function () {
    asAdmin();
    $vaga = Vaga::factory()->aberta()->create();
    $cv = UploadedFile::fake()->create('cv.pdf', 200, 'application/pdf');

    Livewire::test(Candidatos::class)
        ->call('openCreate')
        ->set('vaga_id', $vaga->id)
        ->set('nome', 'Carlos')
        ->set('email', 'carlos@email.com')
        ->set('curriculo', $cv)
        ->call('save')
        ->assertHasNoErrors();

    $cand = Candidato::where('email', 'carlos@email.com')->first();
    expect($cand->curriculo_path)->not->toBeNull();
});

it('rejeita currículo em formato inválido', function () {
    asAdmin();
    $vaga = Vaga::factory()->aberta()->create();
    $cv = UploadedFile::fake()->create('exe.exe', 100, 'application/x-msdownload');

    Livewire::test(Candidatos::class)
        ->call('openCreate')
        ->set('vaga_id', $vaga->id)
        ->set('nome', 'Hacker')
        ->set('email', 'hack@email.com')
        ->set('curriculo', $cv)
        ->call('save')
        ->assertHasErrors('curriculo');
});

it('workflow: inscrito → triagem → entrevista → aprovado', function () {
    $service = app(CandidatoService::class);
    $cand = Candidato::factory()->inscrito()->create();

    $service->alterarStatus($cand, Candidato::STATUS_TRIAGEM);
    expect($cand->fresh()->status)->toBe(Candidato::STATUS_TRIAGEM);

    $service->alterarStatus($cand->fresh(), Candidato::STATUS_ENTREVISTA);
    expect($cand->fresh()->status)->toBe(Candidato::STATUS_ENTREVISTA);

    $service->alterarStatus($cand->fresh(), Candidato::STATUS_APROVADO);
    expect($cand->fresh()->status)->toBe(Candidato::STATUS_APROVADO);
});

it('workflow: inscrito pode ir direto a rejeitado', function () {
    $service = app(CandidatoService::class);
    $cand = Candidato::factory()->inscrito()->create();

    $service->alterarStatus($cand, Candidato::STATUS_REJEITADO);
    expect($cand->fresh()->status)->toBe(Candidato::STATUS_REJEITADO);
});

it('workflow rejeita transição inválida', function () {
    $service = app(CandidatoService::class);
    $cand = Candidato::factory()->inscrito()->create();

    // inscrito não pode pular direto para aprovado
    expect(fn () => $service->alterarStatus($cand, Candidato::STATUS_APROVADO))
        ->toThrow(ValidationException::class);
});

it('aprovado pode virar contratado', function () {
    $service = app(CandidatoService::class);
    $cand = Candidato::factory()->aprovado()->create();

    $service->alterarStatus($cand, Candidato::STATUS_CONTRATADO);
    expect($cand->fresh()->status)->toBe(Candidato::STATUS_CONTRATADO);
});

it('contratado não tem mais transições possíveis', function () {
    $cand = Candidato::factory()->contratado()->create();
    expect($cand->transicoesPossiveis())->toBe([]);
});

it('Policy não permite excluir candidato contratado', function () {
    $cand = Candidato::factory()->contratado()->create();
    $user = User::factory()->create();
    $user->givePermissionTo('recrutamento.delete');

    expect($user->can('delete', $cand))->toBeFalse();
});

it('filtra candidatos por vaga', function () {
    asAdmin();
    $v1 = Vaga::factory()->aberta()->create();
    $v2 = Vaga::factory()->aberta()->create();
    Candidato::factory()->count(3)->create(['vaga_id' => $v1->id]);
    Candidato::factory()->count(2)->create(['vaga_id' => $v2->id]);

    Livewire::test(Candidatos::class)
        ->set('filterVagaId', $v1->id)
        ->assertViewHas('candidatos', fn ($p) => $p->total() === 3);
});

it('busca candidato por nome', function () {
    asAdmin();
    $vaga = Vaga::factory()->aberta()->create();
    Candidato::factory()->create(['vaga_id' => $vaga->id, 'nome' => 'Maria Aparecida']);
    Candidato::factory()->create(['vaga_id' => $vaga->id, 'nome' => 'João Pedro']);

    Livewire::test(Candidatos::class)
        ->set('search', 'Maria')
        ->assertViewHas('candidatos', fn ($p) => $p->total() === 1);
});

it('alterarStatus pelo Livewire dispara workflow', function () {
    asAdmin();
    $cand = Candidato::factory()->inscrito()->create();

    Livewire::test(Candidatos::class)
        ->call('alterarStatus', $cand->id, Candidato::STATUS_TRIAGEM);

    expect($cand->fresh()->status)->toBe(Candidato::STATUS_TRIAGEM);
});
