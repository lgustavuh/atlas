<?php

declare(strict_types=1);

use App\Livewire\Auth\ForgotPassword;
use App\Models\Advertencia;
use App\Models\Colaborador;
use App\Models\User;
use App\Services\DocumentoUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

// =========================
// 1. Mass Assignment defense
// =========================

it('mass assignment não permite forjar created_by', function () {
    $vitima = User::factory()->create();
    $atacante = User::factory()->create();
    // Login como atacante
    $this->actingAs($atacante);

    // Atacante tenta criar colaborador alegando que foi a vítima quem criou
    $colab = Colaborador::create([
        'nome' => 'Colaborador teste',
        'cpf' => '11144477735',
        'created_by' => $vitima->id, // 👀 deve ser sobrescrito pelo trait
    ]);

    // O trait AuditaUsuario sobrescreveu com o ID do usuário autenticado real
    expect($colab->created_by)->toBe($atacante->id);
    expect($colab->created_by)->not->toBe($vitima->id);
});

it('mass assignment não permite forjar updated_by', function () {
    $vitima = User::factory()->create();
    $atacante = User::factory()->create();
    $this->actingAs($atacante);

    $colab = Colaborador::factory()->create(['nome' => 'Original']);

    $colab->update([
        'nome' => 'Modificado',
        'updated_by' => $vitima->id, // tentativa de forjar
    ]);

    expect($colab->fresh()->updated_by)->toBe($atacante->id);
});

it('mass assignment não permite injetar deleted_at via input', function () {
    expect(fn () => Colaborador::create([
        'nome' => 'Teste',
        'cpf' => '11144477735',
        'deleted_at' => now(), // está em $guarded
    ]))->toThrow(\Illuminate\Database\Eloquent\MassAssignmentException::class);
});

it('created_by é imutável depois de criado', function () {
    $original = User::factory()->create();
    $atacante = User::factory()->create();

    $this->actingAs($original);
    $colab = Colaborador::factory()->create();
    $original_creator_id = $colab->created_by;

    // Atacante tenta mudar
    $this->actingAs($atacante);
    $colab->update([
        'created_by' => $atacante->id,
        'nome' => 'Edit',
    ]);

    expect($colab->fresh()->created_by)->toBe($original_creator_id);
});

// =========================
// 2. Sanitização de filename
// =========================

it('sanitizarNome remove caracteres perigosos de header injection', function () {
    Storage::fake('local');

    $service = app(DocumentoUploadService::class);

    // Arquivo com nome agressivo: aspas, CRLF, path traversal, scripts
    $nomeAtaque = "rel\"; \r\nSet-Cookie: x=y;<script>'.exe";
    $file = UploadedFile::fake()->createWithContent($nomeAtaque . '.pdf', '%PDF-1.4');

    $info = $service->armazenar($file, 'teste');

    // Não pode conter aspas, CR, LF, ; (que quebram Content-Disposition)
    expect($info['arquivo_nome_original'])
        ->not->toContain('"')
        ->not->toContain("\r")
        ->not->toContain("\n")
        ->not->toContain(';')
        ->not->toContain('<')
        ->not->toContain("'");
});

it('sanitizarNome impede path traversal', function () {
    Storage::fake('local');
    $service = app(DocumentoUploadService::class);

    $file = UploadedFile::fake()->createWithContent('../../etc/passwd', 'fake');
    $info = $service->armazenar($file, 'teste');

    expect($info['arquivo_nome_original'])
        ->not->toContain('/')
        ->not->toContain('\\')
        ->not->toContain('..');
});

it('sanitizarNome trata nome só com caracteres ilegais', function () {
    Storage::fake('local');
    $service = app(DocumentoUploadService::class);

    $file = UploadedFile::fake()->createWithContent('///\\\\"""', 'fake');
    $info = $service->armazenar($file, 'teste');

    // Não deve ficar vazio
    expect(trim($info['arquivo_nome_original'], '_'))->not->toBe('');
});

// =========================
// 3. mimetypes em uploads
// =========================

it('upload de currículo rejeita arquivo com mimetype não-permitido', function () {
    asAdmin();

    $vaga = \App\Models\Vaga::factory()->create();

    // .exe — MIME application/x-msdownload, fora dos permitidos
    $fakeFile = UploadedFile::fake()->create('script.exe', 100, 'application/x-msdownload');

    Livewire::test(\App\Livewire\Recrutamento\Candidatos::class)
        ->call('openCreate')
        ->set('vaga_id', $vaga->id)
        ->set('nome', 'Hacker')
        ->set('email', 'h@h.com')
        ->set('curriculo', $fakeFile)
        ->call('save')
        ->assertHasErrors('curriculo');
});

// =========================
// 4. Rate limit em ForgotPassword
// =========================

it('ForgotPassword bloqueia após 3 tentativas', function () {
    RateLimiter::clear('forgot:teste@email.com|127.0.0.1');

    // 3 tentativas válidas
    for ($i = 0; $i < 3; $i++) {
        Livewire::test(ForgotPassword::class)
            ->set('email', 'teste@email.com')
            ->call('send')
            ->assertHasNoErrors();
    }

    // 4ª tentativa: bloqueada
    Livewire::test(ForgotPassword::class)
        ->set('email', 'teste@email.com')
        ->call('send')
        ->assertHasErrors('email');
});

// =========================
// 5. Security Headers
// =========================

it('aplica header X-Frame-Options em todas as respostas', function () {
    asAdmin();
    $response = $this->get('/dashboard');

    expect($response->headers->get('X-Frame-Options'))->toBe('DENY');
});

it('aplica header X-Content-Type-Options em todas as respostas', function () {
    asAdmin();
    $response = $this->get('/dashboard');

    expect($response->headers->get('X-Content-Type-Options'))->toBe('nosniff');
});

it('aplica Referrer-Policy', function () {
    asAdmin();
    $response = $this->get('/dashboard');

    expect($response->headers->get('Referrer-Policy'))->toBe('same-origin');
});

it('aplica Permissions-Policy desabilitando câmera/microfone', function () {
    asAdmin();
    $response = $this->get('/dashboard');

    $policy = $response->headers->get('Permissions-Policy');
    expect($policy)->toContain('camera=()');
    expect($policy)->toContain('microphone=()');
});

it('aplica Content-Security-Policy', function () {
    asAdmin();
    $response = $this->get('/dashboard');

    $csp = $response->headers->get('Content-Security-Policy');
    expect($csp)->not->toBeNull();
    expect($csp)->toContain("default-src 'self'");
    expect($csp)->toContain("frame-ancestors 'none'");
});

it('NÃO aplica HSTS em HTTP (só HTTPS)', function () {
    asAdmin();
    $response = $this->get('/dashboard');

    // Testes rodam em HTTP por default — HSTS não deve aparecer
    expect($response->headers->get('Strict-Transport-Security'))->toBeNull();
});

// =========================
// 6. Vazamento de exception no toast
// =========================

it('Content-Disposition é seguro mesmo com nome ofensivo no banco', function () {
    asAdmin();
    Storage::fake('local');

    $colab = Colaborador::factory()->create();

    // Cria atestado direto no banco com nome ofensivo (simula registro pré-existente)
    $atestado = \App\Models\Atestado::factory()->create([
        'colaborador_id' => $colab->id,
        'arquivo_path' => 'private/atestados/fake-hash.pdf',
        'arquivo_nome_original' => 'atestado"<>;\\\\\r\n.pdf',
        'arquivo_mime' => 'application/pdf',
    ]);

    Storage::disk('local')->put('private/atestados/fake-hash.pdf', '%PDF-1.4');

    $response = $this->get(route('documentos.atestado', ['id' => $atestado->id]));

    $cd = $response->headers->get('Content-Disposition');
    // Header não pode conter aspas literais ou CRLF no fallback ASCII
    expect($cd)->not->toContain("\r");
    expect($cd)->not->toContain("\n");
    // Tem filename* RFC 5987
    expect($cd)->toContain('filename*=');
});
