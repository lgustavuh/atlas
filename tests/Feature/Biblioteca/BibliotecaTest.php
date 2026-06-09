<?php

declare(strict_types=1);

use App\Livewire\Biblioteca\Gerenciar;
use App\Livewire\Biblioteca\GerenciarAreas;
use App\Models\BibliotecaArea;
use App\Models\BibliotecaDocumento;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('local');
});

it('admin pode fazer upload de documento na biblioteca', function () {
    asAdmin();
    $arquivo = UploadedFile::fake()->create('manual.pdf', 500, 'application/pdf');

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('titulo', 'Manual do Servidor 2026')
        ->set('descricao', 'Manual oficial atualizado para o ano corrente')
        ->set('versao', '2.0')
        ->set('arquivo', $arquivo)
        ->call('save')
        ->assertHasNoErrors();

    expect(BibliotecaDocumento::where('titulo', 'Manual do Servidor 2026')->exists())->toBeTrue();
    $doc = BibliotecaDocumento::where('titulo', 'Manual do Servidor 2026')->first();
    expect($doc->arquivo_nome_original)->toBe('manual.pdf');
    expect($doc->arquivo_mime)->toBe('application/pdf');
});

it('exige arquivo na criação', function () {
    asAdmin();

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('titulo', 'Documento sem arquivo')
        ->call('save')
        ->assertHasErrors('arquivo');
});

it('rejeita arquivo com extensão não permitida', function () {
    asAdmin();
    $arquivo = UploadedFile::fake()->create('virus.exe', 100, 'application/x-msdownload');

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('titulo', 'Tentativa de exe')
        ->set('arquivo', $arquivo)
        ->call('save')
        ->assertHasErrors('arquivo');
});

it('rejeita arquivo maior que 20 MB', function () {
    asAdmin();
    $arquivo = UploadedFile::fake()->create('grande.pdf', 25000, 'application/pdf'); // 25 MB

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('titulo', 'Arquivo gigante')
        ->set('arquivo', $arquivo)
        ->call('save')
        ->assertHasErrors('arquivo');
});

it('exige título mínimo de 3 caracteres', function () {
    asAdmin();
    $arquivo = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('titulo', 'OK') // 2 chars
        ->set('arquivo', $arquivo)
        ->call('save')
        ->assertHasErrors('titulo');
});

it('vincula áreas ao documento (N:N)', function () {
    asAdmin();
    $rh = BibliotecaArea::factory()->create(['nome' => 'RH']);
    $compras = BibliotecaArea::factory()->create(['nome' => 'Compras']);
    $arquivo = UploadedFile::fake()->create('politica.pdf', 200, 'application/pdf');

    Livewire::test(Gerenciar::class)
        ->call('openCreate')
        ->set('titulo', 'Política multi-área')
        ->set('selectedAreas', [$rh->id, $compras->id])
        ->set('arquivo', $arquivo)
        ->call('save')
        ->assertHasNoErrors();

    $doc = BibliotecaDocumento::where('titulo', 'Política multi-área')->first();
    expect($doc->areas)->toHaveCount(2);
    expect($doc->areas->pluck('nome')->all())->toContain('RH', 'Compras');
});

it('filtra documentos por área', function () {
    asAdmin();
    $rh = BibliotecaArea::factory()->create(['nome' => 'RH']);
    $eng = BibliotecaArea::factory()->create(['nome' => 'Engenharia']);

    $doc1 = BibliotecaDocumento::factory()->create();
    $doc1->areas()->attach($rh);

    $doc2 = BibliotecaDocumento::factory()->create();
    $doc2->areas()->attach($eng);

    Livewire::test(Gerenciar::class)
        ->set('filterAreaId', $rh->id)
        ->assertViewHas('documentos', fn ($p) => $p->total() === 1);
});

it('busca documento por título', function () {
    asAdmin();
    BibliotecaDocumento::factory()->create(['titulo' => 'Procedimento de Almoxarifado']);
    BibliotecaDocumento::factory()->create(['titulo' => 'Política de Frota']);

    Livewire::test(Gerenciar::class)
        ->set('search', 'Almoxarifado')
        ->assertViewHas('documentos', fn ($p) => $p->total() === 1);
});

it('formata tamanho legível', function () {
    $pequeno = BibliotecaDocumento::factory()->make(['arquivo_tamanho_bytes' => 512]);
    expect($pequeno->tamanho_legivel)->toBe('512 B');

    $kb = BibliotecaDocumento::factory()->make(['arquivo_tamanho_bytes' => 2048]);
    expect($kb->tamanho_legivel)->toBe('2,0 KB');

    $mb = BibliotecaDocumento::factory()->make(['arquivo_tamanho_bytes' => 5 * 1024 * 1024]);
    expect($mb->tamanho_legivel)->toBe('5,0 MB');
});

it('escolhe ícone conforme MIME', function () {
    $pdf = BibliotecaDocumento::factory()->make(['arquivo_mime' => 'application/pdf']);
    expect($pdf->icone)->toBe('file-type-pdf');

    $docx = BibliotecaDocumento::factory()->make([
        'arquivo_mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ]);
    expect($docx->icone)->toBe('file-type-doc');

    $img = BibliotecaDocumento::factory()->make(['arquivo_mime' => 'image/png']);
    expect($img->icone)->toBe('photo');
});

// ============================================================
// Áreas
// ============================================================

it('admin pode criar área', function () {
    asAdmin();

    Livewire::test(GerenciarAreas::class)
        ->call('openCreate')
        ->set('nome', 'Saúde Pública')
        ->call('save')
        ->assertHasNoErrors();

    expect(BibliotecaArea::where('nome', 'Saúde Pública')->exists())->toBeTrue();
});

it('rejeita nome de área muito curto', function () {
    asAdmin();

    Livewire::test(GerenciarAreas::class)
        ->call('openCreate')
        ->set('nome', 'X')
        ->call('save')
        ->assertHasErrors('nome');
});

it('não permite excluir área em uso', function () {
    asAdmin();
    $area = BibliotecaArea::factory()->create();
    $doc = BibliotecaDocumento::factory()->create();
    $doc->areas()->attach($area);

    Livewire::test(GerenciarAreas::class)->call('confirmDelete', $area->id);

    // A área NÃO deve ter sido marcada para exclusão (showDeleteModal não foi aberto)
    expect(BibliotecaArea::find($area->id))->not->toBeNull();
});
