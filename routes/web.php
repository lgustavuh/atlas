<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\ColaboradorFotoController;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\PdfController;
use App\Livewire\Advertencias\Gerenciar as AdvertenciasGerenciar;
use App\Livewire\Atestados\Gerenciar as AtestadosGerenciar;
use App\Livewire\Auth\ChangePassword;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Cargos\Gerenciar as CargosGerenciar;
use App\Livewire\Classificacoes\Gerenciar as ClassificacoesGerenciar;
use App\Livewire\Colaboradores\Formulario as ColaboradorFormulario;
use App\Livewire\Colaboradores\Listar as ColaboradorListar;
use App\Livewire\Colaboradores\Visualizar as ColaboradorVisualizar;
use App\Livewire\Dashboard;
use App\Livewire\Departamentos\Gerenciar as DepartamentosGerenciar;
use App\Http\Controllers\BibliotecaController;
use App\Livewire\Alertas\Gerenciar as AlertasGerenciar;
use App\Livewire\Auditoria\Consultar as AuditoriaConsultar;
use App\Livewire\Biblioteca\Gerenciar as BibliotecaGerenciar;
use App\Livewire\Biblioteca\GerenciarAreas as BibliotecaGerenciarAreas;
use App\Livewire\Geografia\Consultar as GeografiaConsultar;
use App\Livewire\Fornecedores\Gerenciar as FornecedoresGerenciar;
use App\Livewire\Materiais\Gerenciar as MateriaisGerenciar;
use App\Livewire\Materiais\GerenciarGrupos as MateriaisGerenciarGrupos;
use App\Livewire\Obras\Gerenciar as ObrasGerenciar;
use App\Livewire\Recrutamento\Candidatos as RecrutamentoCandidatos;
use App\Livewire\Recrutamento\Vagas as RecrutamentoVagas;
use App\Livewire\Republicas\Gerenciar as RepublicasGerenciar;
use App\Livewire\Republicas\Ocupacoes as RepublicasOcupacoes;
use App\Livewire\TransporteHospedagem\Gerenciar as TransporteHospedagemGerenciar;
use App\Livewire\PedidosCompra\Formulario as PedidoCompraFormulario;
use App\Livewire\PedidosCompra\Listar as PedidoCompraListar;
use App\Livewire\PedidosCompra\Visualizar as PedidoCompraVisualizar;
use App\Livewire\Profile\Edit as ProfileEdit;
use App\Livewire\Roles\RoleList;
use App\Livewire\Users\UserList;
use App\Livewire\Veiculos\Gerenciar as VeiculosGerenciar;
use App\Livewire\Veiculos\Manutencoes as VeiculosManutencoes;
use Illuminate\Support\Facades\Route;

// === Health check público (sem auth, para monitoramento externo) ===
// Endpoint /up já é provido pelo Laravel 11 nativo (mais raso, sem dependências).
// Este /health é completo: PG, Redis, cache, storage.
Route::get('/health', HealthController::class)->name('health');

// === Públicas ===
Route::middleware('guest')->group(function (): void {
    Route::get('/', Login::class)->name('login');
    Route::get('/forgot-password', ForgotPassword::class)->name('password.request');
    Route::get('/reset-password/{token}', ResetPassword::class)->name('password.reset');
});

// === Autenticadas ===
Route::middleware(['auth', 'account.active'])->group(function (): void {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/profile', ProfileEdit::class)->name('profile.edit');
    Route::get('/change-password', ChangePassword::class)->name('password.change');
    Route::post('/logout', LogoutController::class)->name('logout');

    // === RH ===
    Route::middleware('can:colaboradores.view-any')->group(function (): void {
        Route::get('/colaboradores', ColaboradorListar::class)->name('colaboradores.index');
        Route::get('/colaboradores/novo', ColaboradorFormulario::class)
            ->middleware('can:colaboradores.create')->name('colaboradores.create');
        Route::get('/colaboradores/{id}', ColaboradorVisualizar::class)
            ->whereNumber('id')->name('colaboradores.show');
        Route::get('/colaboradores/{id}/editar', ColaboradorFormulario::class)
            ->whereNumber('id')->middleware('can:colaboradores.update')->name('colaboradores.edit');
        Route::get('/colaboradores/{id}/foto', ColaboradorFotoController::class)
            ->whereNumber('id')->name('colaboradores.foto');
    });

    Route::middleware('can:cargos.view-any')->group(function (): void {
        Route::get('/cargos', CargosGerenciar::class)->name('cargos.index');
    });

    Route::middleware('can:departamentos.view-any')->group(function (): void {
        Route::get('/departamentos', DepartamentosGerenciar::class)->name('departamentos.index');
    });

    Route::middleware('can:classificacoes.view-any')->group(function (): void {
        Route::get('/classificacoes', ClassificacoesGerenciar::class)->name('classificacoes.index');
    });

    Route::middleware('can:advertencias.view-any')->group(function (): void {
        Route::get('/advertencias', AdvertenciasGerenciar::class)->name('advertencias.index');
    });

    Route::middleware('can:atestados.view-any')->group(function (): void {
        Route::get('/atestados', AtestadosGerenciar::class)->name('atestados.index');
    });

    Route::middleware('can:ferias.view-any')->group(function (): void {
        Route::get('/ferias', \App\Livewire\Ferias\Gerenciar::class)->name('ferias.index');
    });

    // === Documentos privados (servidos com verificação de autorização) ===
    Route::get('/documentos/atestado/{id}/{modo?}', [DocumentoController::class, 'atestado'])
        ->whereNumber('id')
        ->whereIn('modo', ['view', 'download'])
        ->name('documentos.atestado');
    Route::get('/documentos/advertencia/{id}/{modo?}', [DocumentoController::class, 'advertencia'])
        ->whereNumber('id')
        ->whereIn('modo', ['view', 'download'])
        ->name('documentos.advertencia');
    Route::get('/documentos/veiculo/{id}/{modo?}', [DocumentoController::class, 'veiculo'])
        ->whereNumber('id')
        ->whereIn('modo', ['view', 'download'])
        ->name('documentos.veiculo');
    Route::get('/documentos/manutencao/{id}/{modo?}', [DocumentoController::class, 'comprovanteManutencao'])
        ->whereNumber('id')
        ->whereIn('modo', ['view', 'download'])
        ->name('documentos.manutencao');

    // === Compras ===
    Route::middleware('can:fornecedores.view-any')->group(function (): void {
        Route::get('/fornecedores', FornecedoresGerenciar::class)->name('fornecedores.index');
    });

    Route::middleware('can:materiais.view-any')->group(function (): void {
        Route::get('/materiais', MateriaisGerenciar::class)->name('materiais.index');
        Route::get('/materiais/grupos', MateriaisGerenciarGrupos::class)->name('materiais.grupos');
    });

    Route::middleware('can:pedidos-compra.view-any')->group(function (): void {
        Route::get('/pedidos-compra', PedidoCompraListar::class)->name('pedidos-compra.index');
        Route::get('/pedidos-compra/novo', PedidoCompraFormulario::class)
            ->middleware('can:pedidos-compra.create')->name('pedidos-compra.create');
        Route::get('/pedidos-compra/{id}', PedidoCompraVisualizar::class)
            ->whereNumber('id')->name('pedidos-compra.show');
        Route::get('/pedidos-compra/{id}/editar', PedidoCompraFormulario::class)
            ->whereNumber('id')->middleware('can:pedidos-compra.update')->name('pedidos-compra.edit');
    });

    // === Veículos e Manutenções ===
    Route::middleware('can:veiculos.view-any')->group(function (): void {
        Route::get('/veiculos', VeiculosGerenciar::class)->name('veiculos.index');
    });

    Route::middleware('can:manutencoes.view-any')->group(function (): void {
        Route::get('/manutencoes', VeiculosManutencoes::class)->name('manutencoes.index');
    });

    // === Obras ===
    Route::middleware('can:obras.view-any')->group(function (): void {
        Route::get('/obras', ObrasGerenciar::class)->name('obras.index');
    });

    // === Biblioteca ===
    Route::middleware('can:biblioteca.view-any')->group(function (): void {
        Route::get('/biblioteca', BibliotecaGerenciar::class)->name('biblioteca.index');
        Route::get('/biblioteca/areas', BibliotecaGerenciarAreas::class)->name('biblioteca.areas');
        Route::get('/biblioteca/{id}/visualizar', [BibliotecaController::class, 'visualizar'])
            ->whereNumber('id')->name('biblioteca.visualizar');
        Route::get('/biblioteca/{id}/download', [BibliotecaController::class, 'download'])
            ->whereNumber('id')->name('biblioteca.download');
    });

    // === Alertas Administrativos ===
    Route::middleware('can:alertas-adm.view-any')->group(function (): void {
        Route::get('/alertas', AlertasGerenciar::class)->name('alertas.index');
    });

    // === Recrutamento ===
    Route::middleware('can:recrutamento.view-any')->group(function (): void {
        Route::get('/recrutamento/vagas', RecrutamentoVagas::class)->name('recrutamento.vagas');
        Route::get('/recrutamento/candidatos', RecrutamentoCandidatos::class)->name('recrutamento.candidatos');
    });

    // === Transporte e Hospedagem ===
    Route::middleware('can:transporte-hospedagem.view-any')->group(function (): void {
        Route::get('/transporte-hospedagem', TransporteHospedagemGerenciar::class)->name('transporte-hospedagem.index');
    });

    // === Repúblicas ===
    Route::middleware('can:republicas.view-any')->group(function (): void {
        Route::get('/republicas', RepublicasGerenciar::class)->name('republicas.index');
        Route::get('/republicas/{id}/ocupacoes', RepublicasOcupacoes::class)
            ->whereNumber('id')->name('republicas.ocupacoes');
    });

    // === Auditoria ===
    Route::middleware('can:audit.view-any')->group(function (): void {
        Route::get('/auditoria', AuditoriaConsultar::class)->name('auditoria.index');
    });

    // === PDFs ===
    Route::prefix('pdf')->name('pdf.')->group(function (): void {
        Route::get('advertencia/{advertencia}', [PdfController::class, 'advertencia'])->name('advertencia');
        Route::get('pedido-compra/{pedido}', [PdfController::class, 'pedidoCompra'])->name('pedido-compra');
        Route::get('colaborador/{colaborador}/ficha', [PdfController::class, 'colaboradorFicha'])->name('colaborador.ficha');
    });

    // === Geografia (todos autenticados) ===
    Route::get('/geografia', GeografiaConsultar::class)->name('geografia.index');

    // === Administração ===
    Route::middleware('can:users.view-any')->group(function (): void {
        Route::get('/users', UserList::class)->name('users.index');
    });

    Route::middleware('can:roles.view-any')->group(function (): void {
        Route::get('/roles', RoleList::class)->name('roles.index');
    });
});
