<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Advertencia;
use App\Models\AlertaAdm;
use App\Models\Atestado;
use App\Models\BibliotecaArea;
use App\Models\BibliotecaDocumento;
use App\Models\Candidato;
use App\Models\Cargo;
use App\Models\Cidade;
use App\Models\Colaborador;
use App\Models\Departamento;
use App\Models\Estado;
use App\Models\Ferias;
use App\Models\Fornecedor;
use App\Models\GrupoMaterial;
use App\Models\Material;
use App\Models\Obra;
use App\Models\PedidoCompra;
use App\Models\PedidoCompraItem;
use App\Models\Republica;
use App\Models\TransporteHospedagem;
use App\Models\User;
use App\Models\Vaga;
use App\Models\Veiculo;
use App\Models\VeiculoManutencao;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seeder de dados fictícios para testes funcionais.
 *
 * Cria pelo menos 10 registros em cada módulo do sistema.
 *
 * COMO USAR:
 *   # Adicionar dados ao banco atual (preserva existentes)
 *   php artisan db:seed --class=DadosFicticiosSeeder
 *
 *   # No Docker:
 *   docker compose exec app php artisan db:seed --class=DadosFicticiosSeeder
 *
 *   # Para LIMPAR e recriar tudo do zero (CUIDADO em produção!):
 *   php artisan migrate:fresh --seed
 *   php artisan db:seed --class=DadosFicticiosSeeder
 *
 * SEGURANÇA:
 *   - Recusa rodar em APP_ENV=production (a menos que --force seja passado)
 *   - Usa transação: se algo falhar, NADA é gravado
 *   - Não cria usuários administradores (use o AdminUserSeeder)
 */
class DadosFicticiosSeeder extends Seeder
{
    /**
     * Quantidade mínima de registros por módulo.
     */
    private const QTD_MIN = 10;

    public function run(): void
    {
        // Trava de segurança: NÃO rodar em produção sem --force
        if (app()->environment('production') && ! $this->command->option('force')) {
            $this->command->error('   ERRO: Este seeder está bloqueado em produção.');
            $this->command->warn('         Se você TEM CERTEZA de que quer dados fictícios em produção,');
            $this->command->warn('         use: php artisan db:seed --class=DadosFicticiosSeeder --force');

            return;
        }

        $this->command->info('');
        $this->command->info('==================================================================');
        $this->command->info('  Atlas - Seeder de Dados Fictícios');
        $this->command->info('==================================================================');
        $this->command->info('');

        DB::transaction(function (): void {
            $this->seedGeografia();
            $this->seedDepartamentosECargos();
            $this->seedColaboradores();
            $this->seedAdvertencias();
            $this->seedAtestados();
            $this->seedFerias();
            $this->seedFornecedores();
            $this->seedMateriaisEGrupos();
            $this->seedPedidosCompra();
            $this->seedVeiculosEManutencoes();
            $this->seedObras();
            $this->seedVagasECandidatos();
            $this->seedBiblioteca();
            $this->seedAlertasAdministrativos();
            $this->seedRepublicas();
            $this->seedTransporteHospedagem();
        });

        $this->command->info('');
        $this->command->info('==================================================================');
        $this->command->info('  Dados fictícios criados com sucesso!');
        $this->command->info('==================================================================');
        $this->command->info('');
        $this->resumo();
    }

    // ---------------------------------------------------------------
    //  Cada módulo é uma função separada para facilitar manutenção
    // ---------------------------------------------------------------

    private function seedGeografia(): void
    {
        // Geografia já é populada pelo GeografiaSeeder + import IBGE.
        // Só garante que tem dados disponíveis.
        if (Estado::count() === 0) {
            $this->command->warn('[!]  Estados não encontrados. Rode primeiro: php artisan db:seed --class=GeografiaSeeder');
        }
        if (Cidade::count() < 10) {
            $this->command->warn('[!]  Poucas cidades. Considere rodar: php artisan etc:importar-cidades-ibge');
        }
    }

    private function seedDepartamentosECargos(): void
    {
        $this->command->info('-> Departamentos e Cargos...');

        $existentes = Departamento::count();
        if ($existentes < self::QTD_MIN) {
            Departamento::factory()->count(self::QTD_MIN - $existentes)->create();
        }

        $existentes = Cargo::count();
        if ($existentes < self::QTD_MIN) {
            Cargo::factory()->count(self::QTD_MIN - $existentes)->create();
        }

        $this->command->info('   OK  '.Departamento::count().' departamentos, '.Cargo::count().' cargos');
    }

    private function seedColaboradores(): void
    {
        $this->command->info('-> Colaboradores...');

        $cargos = Cargo::pluck('id')->all();
        $departamentos = Departamento::pluck('id')->all();

        $qtdAtual = Colaborador::count();
        $criar = max(0, 15 - $qtdAtual);

        if ($criar > 0) {
            // 12 ativos + 3 demitidos pra ter variedade
            Colaborador::factory()->count(12)->create([
                'cargo_id' => fn () => fake()->randomElement($cargos),
                'departamento_id' => fn () => fake()->randomElement($departamentos),
            ]);

            Colaborador::factory()->demitido()->count(3)->create([
                'cargo_id' => fn () => fake()->randomElement($cargos),
                'departamento_id' => fn () => fake()->randomElement($departamentos),
            ]);
        }

        $this->command->info('   OK  '.Colaborador::count().' colaboradores ('.Colaborador::whereNull('data_demissao')->count().' ativos)');
    }

    private function seedAdvertencias(): void
    {
        $this->command->info('-> Advertências...');

        $colaboradores = Colaborador::query()
            ->whereNull('data_demissao')
            ->pluck('id')
            ->all();

        if (empty($colaboradores)) {
            $this->command->warn('   !   Sem colaboradores ativos, pulando');

            return;
        }

        $criar = max(0, self::QTD_MIN - Advertencia::count());
        if ($criar > 0) {
            Advertencia::factory()->count($criar)->create([
                'colaborador_id' => fn () => fake()->randomElement($colaboradores),
            ]);
        }

        $this->command->info('   OK  '.Advertencia::count().' advertências');
    }

    private function seedAtestados(): void
    {
        $this->command->info('-> Atestados...');

        $colaboradores = Colaborador::query()
            ->whereNull('data_demissao')
            ->pluck('id')
            ->all();

        if (empty($colaboradores)) {
            return;
        }

        $criar = max(0, self::QTD_MIN - Atestado::count());
        if ($criar > 0) {
            Atestado::factory()->count($criar)->create([
                'colaborador_id' => fn () => fake()->randomElement($colaboradores),
            ]);
        }

        $this->command->info('   OK  '.Atestado::count().' atestados');
    }

    private function seedFerias(): void
    {
        $this->command->info('-> Férias...');

        $colaboradores = Colaborador::query()
            ->whereNull('data_demissao')
            ->pluck('id')
            ->all();

        if (empty($colaboradores)) {
            return;
        }

        $criar = max(0, self::QTD_MIN - Ferias::count());
        if ($criar > 0) {
            Ferias::factory()->count($criar)->create([
                'colaborador_id' => fn () => fake()->randomElement($colaboradores),
            ]);
        }

        $this->command->info('   OK  '.Ferias::count().' férias');
    }

    private function seedFornecedores(): void
    {
        $this->command->info('-> Fornecedores...');

        $criar = max(0, self::QTD_MIN - Fornecedor::count());
        if ($criar > 0) {
            Fornecedor::factory()->count($criar)->create();
        }

        $this->command->info('   OK  '.Fornecedor::count().' fornecedores');
    }

    private function seedMateriaisEGrupos(): void
    {
        $this->command->info('-> Grupos e Materiais...');

        $criarGrupos = max(0, self::QTD_MIN - GrupoMaterial::count());
        if ($criarGrupos > 0) {
            GrupoMaterial::factory()->count($criarGrupos)->create();
        }

        $criarMat = max(0, self::QTD_MIN - Material::count());
        if ($criarMat > 0) {
            $grupos = GrupoMaterial::pluck('id')->all();
            Material::factory()->count($criarMat)->create([
                'grupo_id' => fn () => fake()->randomElement($grupos),
            ]);
        }

        $this->command->info('   OK  '.GrupoMaterial::count().' grupos, '.Material::count().' materiais');
    }

    private function seedPedidosCompra(): void
    {
        $this->command->info('-> Pedidos de Compra (com itens)...');

        $fornecedores = Fornecedor::pluck('id')->all();
        $solicitantes = Colaborador::query()
            ->whereNull('data_demissao')
            ->pluck('id')
            ->all();
        $materiais = Material::all();

        if (empty($fornecedores) || empty($solicitantes) || $materiais->isEmpty()) {
            $this->command->warn('   !   Dependências faltando, pulando');

            return;
        }

        $criar = max(0, self::QTD_MIN - PedidoCompra::count());
        if ($criar > 0) {
            // 4 rascunho, 3 aguardando, 2 aprovados, 1 enviado pra ter variedade
            $estados = array_merge(
                array_fill(0, 4, 'rascunho'),
                array_fill(0, 3, 'aguardando'),
                array_fill(0, 2, 'aprovado'),
                array_fill(0, 1, 'enviado'),
            );
            shuffle($estados);

            for ($i = 0; $i < $criar; $i++) {
                $estado = $estados[$i] ?? 'rascunho';

                $factory = PedidoCompra::factory();
                $factory = match ($estado) {
                    'aguardando' => $factory->aguardandoLiberacao(),
                    'aprovado' => $factory->aprovado(),
                    'enviado' => $factory->enviado(),
                    default => $factory,
                };

                /** @var PedidoCompra $pedido */
                $pedido = $factory->create([
                    'fornecedor_id' => fake()->randomElement($fornecedores),
                    'solicitante_id' => fake()->randomElement($solicitantes),
                ]);

                // Adicionar 2-5 itens em cada pedido
                $valorTotal = 0;
                $qtdItens = random_int(2, 5);
                foreach ($materiais->random(min($qtdItens, $materiais->count())) as $material) {
                    $qtd = random_int(1, 20);
                    $preco = (float) $material->preco_referencia ?: 100.0;
                    $subtotal = $qtd * $preco;
                    $valorTotal += $subtotal;

                    PedidoCompraItem::create([
                        'pedido_compra_id' => $pedido->id,
                        'material_id' => $material->id,
                        'quantidade' => $qtd,
                        'preco_unitario' => $preco,
                        'subtotal' => $subtotal,
                    ]);
                }

                $pedido->update([
                    'valor_total' => $valorTotal,
                    'valor_final' => $valorTotal,
                ]);
            }
        }

        $this->command->info('   OK  '.PedidoCompra::count().' pedidos, '.PedidoCompraItem::count().' itens');
    }

    private function seedVeiculosEManutencoes(): void
    {
        $this->command->info('-> Veículos e Manutenções...');

        $criarVeiculos = max(0, self::QTD_MIN - Veiculo::count());
        if ($criarVeiculos > 0) {
            Veiculo::factory()->count($criarVeiculos)->create();
        }

        $veiculos = Veiculo::pluck('id')->all();
        $criarManut = max(0, self::QTD_MIN - VeiculoManutencao::count());
        if ($criarManut > 0 && ! empty($veiculos)) {
            VeiculoManutencao::factory()->count($criarManut)->create([
                'veiculo_id' => fn () => fake()->randomElement($veiculos),
            ]);
        }

        $this->command->info('   OK  '.Veiculo::count().' veículos, '.VeiculoManutencao::count().' manutenções');
    }

    private function seedObras(): void
    {
        $this->command->info('-> Obras...');

        $criar = max(0, self::QTD_MIN - Obra::count());
        if ($criar > 0) {
            // Distribuição realista de status
            Obra::factory()->count(3)->create(); // planejamento
            Obra::factory()->emAndamento()->count(5)->create();
            Obra::factory()->concluida()->count(2)->create();
        }

        $this->command->info('   OK  '.Obra::count().' obras');
    }

    private function seedVagasECandidatos(): void
    {
        $this->command->info('-> Vagas e Candidatos...');

        $criarVagas = max(0, self::QTD_MIN - Vaga::count());
        if ($criarVagas > 0) {
            $cargos = Cargo::pluck('id')->all();
            $deps = Departamento::pluck('id')->all();

            // 7 abertas + 3 rascunho
            Vaga::factory()->aberta()->count(7)->create([
                'cargo_id' => fn () => fake()->randomElement($cargos),
                'departamento_id' => fn () => fake()->randomElement($deps),
            ]);

            Vaga::factory()->count(3)->create([
                'cargo_id' => fn () => fake()->randomElement($cargos),
                'departamento_id' => fn () => fake()->randomElement($deps),
            ]);
        }

        $vagas = Vaga::query()->where('status', Vaga::STATUS_ABERTA)->pluck('id')->all();
        if (! empty($vagas)) {
            $criarCand = max(0, (self::QTD_MIN * 2) - Candidato::count()); // 20 candidatos
            if ($criarCand > 0) {
                Candidato::factory()->count($criarCand)->create([
                    'vaga_id' => fn () => fake()->randomElement($vagas),
                ]);
            }
        }

        $this->command->info('   OK  '.Vaga::count().' vagas, '.Candidato::count().' candidatos');
    }

    private function seedBiblioteca(): void
    {
        $this->command->info('-> Biblioteca (áreas + documentos)...');

        $criarAreas = max(0, self::QTD_MIN - BibliotecaArea::count());
        if ($criarAreas > 0) {
            BibliotecaArea::factory()->count($criarAreas)->create();
        }

        $areas = BibliotecaArea::pluck('id')->all();
        $criarDocs = max(0, self::QTD_MIN - BibliotecaDocumento::count());
        if ($criarDocs > 0 && ! empty($areas)) {
            // BibliotecaDocumento tem relação N:N com áreas (pivot biblioteca_documento_areas)
            $documentos = BibliotecaDocumento::factory()->count($criarDocs)->create();
            foreach ($documentos as $doc) {
                // Cada documento recebe 1-3 áreas aleatórias
                $areasDoDoc = collect($areas)->random(min(random_int(1, 3), count($areas)))->all();
                $doc->areas()->sync($areasDoDoc);
            }
        }

        $this->command->info('   OK  '.BibliotecaArea::count().' áreas, '.BibliotecaDocumento::count().' documentos');
    }

    private function seedAlertasAdministrativos(): void
    {
        $this->command->info('-> Alertas Administrativos...');

        $criar = max(0, self::QTD_MIN - AlertaAdm::count());
        if ($criar > 0) {
            AlertaAdm::factory()->count($criar)->create();
        }

        $this->command->info('   OK  '.AlertaAdm::count().' alertas');
    }

    private function seedRepublicas(): void
    {
        $this->command->info('-> Repúblicas...');

        $criar = max(0, self::QTD_MIN - Republica::count());
        if ($criar > 0) {
            Republica::factory()->count($criar)->create();
        }

        $this->command->info('   OK  '.Republica::count().' repúblicas');
    }

    private function seedTransporteHospedagem(): void
    {
        $this->command->info('-> Transporte e Hospedagem...');

        $colaboradores = Colaborador::query()
            ->whereNull('data_demissao')
            ->pluck('id')
            ->all();

        if (empty($colaboradores)) {
            return;
        }

        $criar = max(0, self::QTD_MIN - TransporteHospedagem::count());
        if ($criar > 0) {
            TransporteHospedagem::factory()->count($criar)->create([
                'colaborador_id' => fn () => fake()->randomElement($colaboradores),
            ]);
        }

        $this->command->info('   OK  '.TransporteHospedagem::count().' registros');
    }

    private function resumo(): void
    {
        $this->command->info('Resumo dos dados no banco:');
        $this->command->table(
            ['Módulo', 'Total'],
            [
                ['Usuários', User::count()],
                ['Departamentos', Departamento::count()],
                ['Cargos', Cargo::count()],
                ['Colaboradores', Colaborador::count()],
                ['  └─ Ativos', Colaborador::whereNull('data_demissao')->count()],
                ['  └─ Demitidos', Colaborador::whereNotNull('data_demissao')->count()],
                ['Advertências', Advertencia::count()],
                ['Atestados', Atestado::count()],
                ['Férias', Ferias::count()],
                ['Fornecedores', Fornecedor::count()],
                ['Grupos de Material', GrupoMaterial::count()],
                ['Materiais', Material::count()],
                ['Pedidos de Compra', PedidoCompra::count()],
                ['  └─ Itens', PedidoCompraItem::count()],
                ['Veículos', Veiculo::count()],
                ['Manutenções', VeiculoManutencao::count()],
                ['Obras', Obra::count()],
                ['Vagas', Vaga::count()],
                ['Candidatos', Candidato::count()],
                ['Biblioteca - Áreas', BibliotecaArea::count()],
                ['Biblioteca - Documentos', BibliotecaDocumento::count()],
                ['Alertas Admin', AlertaAdm::count()],
                ['Repúblicas', Republica::count()],
                ['Transporte/Hospedagem', TransporteHospedagem::count()],
            ],
        );
    }
}
