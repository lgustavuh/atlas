<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Advertencia;
use App\Models\AlertaAdm;
use App\Models\Atestado;
use App\Models\BibliotecaArea;
use App\Models\BibliotecaDocumento;
use App\Models\Candidato;
use App\Models\Cargo;
use App\Models\Classificacao;
use App\Models\Colaborador;
use App\Models\Departamento;
use App\Models\Ferias;
use App\Models\Fornecedor;
use App\Models\GrupoMaterial;
use App\Models\Material;
use App\Models\Obra;
use App\Models\PedidoCompra;
use App\Models\Republica;
use App\Models\User;
use App\Models\TransporteHospedagem;
use App\Models\Vaga;
use App\Models\Veiculo;
use App\Models\VeiculoManutencao;
use App\Policies\AdvertenciaPolicy;
use App\Policies\AlertaAdmPolicy;
use App\Policies\AtestadoPolicy;
use App\Policies\BibliotecaAreaPolicy;
use App\Policies\BibliotecaDocumentoPolicy;
use App\Policies\CandidatoPolicy;
use App\Policies\CargoPolicy;
use App\Policies\ClassificacaoPolicy;
use App\Policies\ColaboradorPolicy;
use App\Policies\DepartamentoPolicy;
use App\Policies\FeriasPolicy;
use App\Policies\FornecedorPolicy;
use App\Policies\GrupoMaterialPolicy;
use App\Policies\MaterialPolicy;
use App\Policies\ObraPolicy;
use App\Policies\PedidoCompraPolicy;
use App\Policies\RepublicaPolicy;
use App\Policies\UserPolicy;
use App\Policies\TransporteHospedagemPolicy;
use App\Policies\VagaPolicy;
use App\Policies\VeiculoManutencaoPolicy;
use App\Policies\VeiculoPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // === Policies ===
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Colaborador::class, ColaboradorPolicy::class);
        Gate::policy(Cargo::class, CargoPolicy::class);
        Gate::policy(Departamento::class, DepartamentoPolicy::class);
        Gate::policy(Classificacao::class, ClassificacaoPolicy::class);
        Gate::policy(Advertencia::class, AdvertenciaPolicy::class);
        Gate::policy(AlertaAdm::class, AlertaAdmPolicy::class);
        Gate::policy(Atestado::class, AtestadoPolicy::class);
        Gate::policy(BibliotecaArea::class, BibliotecaAreaPolicy::class);
        Gate::policy(BibliotecaDocumento::class, BibliotecaDocumentoPolicy::class);
        Gate::policy(Candidato::class, CandidatoPolicy::class);
        Gate::policy(TransporteHospedagem::class, TransporteHospedagemPolicy::class);
        Gate::policy(Vaga::class, VagaPolicy::class);
        Gate::policy(Ferias::class, FeriasPolicy::class);
        Gate::policy(Fornecedor::class, FornecedorPolicy::class);
        Gate::policy(GrupoMaterial::class, GrupoMaterialPolicy::class);
        Gate::policy(Material::class, MaterialPolicy::class);
        Gate::policy(Obra::class, ObraPolicy::class);
        Gate::policy(PedidoCompra::class, PedidoCompraPolicy::class);
        Gate::policy(Republica::class, RepublicaPolicy::class);
        Gate::policy(Veiculo::class, VeiculoPolicy::class);
        Gate::policy(VeiculoManutencao::class, VeiculoManutencaoPolicy::class);

        // === Observers (notificações automáticas) ===
        PedidoCompra::observe(\App\Observers\PedidoCompraObserver::class);
        Candidato::observe(\App\Observers\CandidatoObserver::class);

        // === Boas práticas em todos os ambientes ===

        // Falha imediatamente se tentar acessar atributo que não foi carregado
        // (previne bugs de N+1 e atributos inexistentes)
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        // === Forçar HTTPS em produção ===
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }
}
