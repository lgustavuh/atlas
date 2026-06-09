<?php

declare(strict_types=1);

use App\Mail\NovoCandidatoMail;
use App\Mail\PedidoAprovacaoPendenteMail;
use App\Mail\VencimentoDocumentoMail;
use App\Models\Candidato;
use App\Models\Colaborador;
use App\Models\PedidoCompra;
use App\Models\User;
use App\Models\Vaga;
use App\Models\Veiculo;
use Illuminate\Support\Facades\Mail;

// =========================
// Mailables — geração e conteúdo
// =========================

it('VencimentoDocumentoMail tem assunto correto para dias futuros', function () {
    $mail = new VencimentoDocumentoMail(
        tipoDocumento: 'CNH',
        referente: 'João Silva',
        dataVencimento: now()->addDays(15),
        linkAcesso: 'https://exemplo.com',
    );

    $envelope = $mail->envelope();
    expect($envelope->subject)->toContain('[ETC]');
    expect($envelope->subject)->toContain('CNH');
    expect($envelope->subject)->toContain('João Silva');
});

it('VencimentoDocumentoMail tem assunto especial para hoje', function () {
    $mail = new VencimentoDocumentoMail(
        tipoDocumento: 'Licenciamento',
        referente: 'ABC-1234',
        dataVencimento: now(),
        linkAcesso: 'x',
    );

    expect($mail->envelope()->subject)->toContain('HOJE');
});

it('VencimentoDocumentoMail tem assunto especial para vencido', function () {
    $mail = new VencimentoDocumentoMail(
        tipoDocumento: 'Seguro',
        referente: 'ABC-1234',
        dataVencimento: now()->subDays(5),
        linkAcesso: 'x',
    );

    expect($mail->envelope()->subject)->toContain('VENCIDO');
});

it('PedidoAprovacaoPendenteMail traz número do pedido no assunto', function () {
    $pedido = PedidoCompra::factory()->create(['numero' => '2026/0042']);
    $mail = new PedidoAprovacaoPendenteMail($pedido, 'liberacao', 'x');

    expect($mail->envelope()->subject)->toContain('2026/0042');
    expect($mail->envelope()->subject)->toContain('Liberação');
});

it('NovoCandidatoMail traz nome do candidato no assunto', function () {
    $vaga = Vaga::factory()->create(['titulo' => 'Auxiliar Administrativo']);
    $cand = Candidato::factory()->create([
        'vaga_id' => $vaga->id,
        'nome' => 'Maria Santos',
    ]);

    // recria pra não disparar observer (factory já criou antes do Mail::fake)
    $mail = new NovoCandidatoMail($cand, 'x');

    expect($mail->envelope()->subject)->toContain('Maria Santos');
    expect($mail->envelope()->subject)->toContain('Auxiliar Administrativo');
});

it('Mailable renderiza HTML válido', function () {
    $cand = Candidato::factory()->create();

    $mail = new NovoCandidatoMail($cand, 'https://exemplo.com');
    $html = $mail->render();

    expect($html)->toContain($cand->nome);
    expect($html)->toContain('PREFEITURA MUNICIPAL DE ITAÚ DE MINAS');
});

// =========================
// Observer: PedidoCompra status → email
// =========================

it('Observer envia email quando pedido vai para aguardando_liberacao', function () {
    Mail::fake();

    // Cria usuário que tem permissão de liberar
    $aprovador = User::factory()->create(['email' => 'aprovador@teste.local']);
    $aprovador->givePermissionTo('pedidos-compra.liberar');

    $pedido = PedidoCompra::factory()->create(['status' => PedidoCompra::STATUS_RASCUNHO]);

    // Muda para aguardando_liberacao
    $pedido->update(['status' => PedidoCompra::STATUS_AGUARDANDO_LIBERACAO]);

    Mail::assertQueued(PedidoAprovacaoPendenteMail::class, function ($mail) use ($aprovador) {
        return $mail->hasTo($aprovador->email);
    });
});

it('Observer envia email quando pedido vai para aguardando_aprovacao', function () {
    Mail::fake();

    $aprovador = User::factory()->create(['email' => 'final@teste.local']);
    $aprovador->givePermissionTo('pedidos-compra.aprovar');

    $pedido = PedidoCompra::factory()->create(['status' => PedidoCompra::STATUS_LIBERADO]);
    $pedido->update(['status' => PedidoCompra::STATUS_AGUARDANDO_APROVACAO]);

    Mail::assertQueued(PedidoAprovacaoPendenteMail::class);
});

it('Observer NÃO envia email se status muda para algo neutro', function () {
    Mail::fake();

    $pedido = PedidoCompra::factory()->create(['status' => PedidoCompra::STATUS_RASCUNHO]);
    $pedido->update(['status' => PedidoCompra::STATUS_CANCELADO]);

    Mail::assertNothingQueued();
});

// =========================
// Observer: Candidato criado → email
// =========================

it('Observer envia email quando novo candidato é cadastrado', function () {
    Mail::fake();

    $recrutador = User::factory()->create(['email' => 'rh@teste.local']);
    $recrutador->givePermissionTo('recrutamento.view-any');

    Candidato::factory()->create();

    Mail::assertQueued(NovoCandidatoMail::class, function ($mail) use ($recrutador) {
        return $mail->hasTo($recrutador->email);
    });
});

// =========================
// Command: notificacoes:vencimentos
// =========================

it('command notifica veículos com licenciamento vencendo em 15 dias', function () {
    Mail::fake();

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    // Veículo que vence em 15 dias
    Veiculo::factory()->create([
        'placa' => 'ABC1234',
        'licenciamento_vencimento' => now()->addDays(15)->toDateString(),
    ]);

    // Veículo que vence em 100 dias (não deve disparar)
    Veiculo::factory()->create([
        'licenciamento_vencimento' => now()->addDays(100)->toDateString(),
    ]);

    $this->artisan('notificacoes:vencimentos', ['--dias' => '15'])
        ->assertSuccessful();

    Mail::assertQueued(VencimentoDocumentoMail::class, 1);
});

it('command notifica CNH expirando em 7 dias', function () {
    Mail::fake();

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Colaborador::factory()->create([
        'cnh' => '12345678901',
        'cnh_validade' => now()->addDays(7)->toDateString(),
    ]);

    $this->artisan('notificacoes:vencimentos', ['--dias' => '7'])
        ->assertSuccessful();

    Mail::assertQueued(VencimentoDocumentoMail::class, fn ($mail) => $mail->tipoDocumento === 'CNH');
});

it('command em modo dry-run não envia emails', function () {
    Mail::fake();

    User::factory()->create()->assignRole('admin');
    Veiculo::factory()->create(['licenciamento_vencimento' => now()->addDays(15)->toDateString()]);

    $this->artisan('notificacoes:vencimentos', ['--dias' => '15', '--dry-run' => true])
        ->assertSuccessful();

    Mail::assertNothingQueued();
});

it('command sem destinatários cadastrados aborta com aviso', function () {
    Mail::fake();
    // Nenhum admin/gestor_frota cadastrado
    Veiculo::factory()->create(['licenciamento_vencimento' => now()->addDays(15)->toDateString()]);

    $this->artisan('notificacoes:vencimentos')
        ->expectsOutputToContain('Nenhum destinatário')
        ->assertSuccessful();

    Mail::assertNothingQueued();
});

it('command com várias janelas processa todas', function () {
    Mail::fake();
    User::factory()->create()->assignRole('admin');

    // 3 veículos em janelas diferentes
    Veiculo::factory()->create(['licenciamento_vencimento' => now()->addDays(30)->toDateString()]);
    Veiculo::factory()->create(['licenciamento_vencimento' => now()->addDays(15)->toDateString()]);
    Veiculo::factory()->create(['licenciamento_vencimento' => now()->addDays(7)->toDateString()]);

    $this->artisan('notificacoes:vencimentos', ['--dias' => '30,15,7'])
        ->assertSuccessful();

    Mail::assertQueued(VencimentoDocumentoMail::class, 3);
});
