<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabela central de Colaboradores.
 *
 * Substitui a tabela `colaborador` do legado, que tinha ~50 colunas
 * misturando dados pessoais, endereço, profissional e documentos.
 *
 * Aqui dividimos em:
 *   - colaboradores: dados principais e profissionais
 *   - colaborador_enderecos: endereços (residencial + naturalidade)
 *   - colaborador_dependentes: filhos/cônjuge (NOVA estrutura, antes era texto)
 *
 * Decisões importantes:
 *   - CPF como string (não int) — preserva zeros à esquerda
 *   - CPF unique (entre não-deletados) — controle de duplicidade
 *   - Datas como DATE/DATETIME, nunca VARCHAR
 *   - Salário como DECIMAL(15,2), nunca FLOAT
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colaboradores', function (Blueprint $table) {
            $table->id();

            // === Dados Pessoais ===
            $table->string('nome', 150);
            $table->string('nome_social', 150)->nullable();
            $table->string('cpf', 14)->comment('Formato: 000.000.000-00');
            $table->string('rg', 20)->nullable();
            $table->string('rg_orgao_emissor', 20)->nullable();
            $table->date('rg_data_emissao')->nullable();
            $table->string('pis', 20)->nullable()->comment('PIS/PASEP/NIT');
            $table->string('ctps_numero', 20)->nullable();
            $table->string('ctps_serie', 10)->nullable();
            $table->string('ctps_uf', 5)->nullable();
            $table->string('titulo_eleitor', 20)->nullable();
            $table->string('titulo_zona', 10)->nullable();
            $table->string('titulo_secao', 10)->nullable();
            $table->string('cnh', 20)->nullable();
            $table->string('cnh_categoria', 5)->nullable();
            $table->date('cnh_validade')->nullable();
            $table->string('reservista', 20)->nullable();

            $table->date('data_nascimento')->nullable();
            $table->enum('sexo', ['M', 'F', 'O'])->nullable();
            $table->enum('estado_civil', [
                'solteiro', 'casado', 'divorciado', 'viuvo', 'uniao_estavel', 'separado'
            ])->nullable();
            $table->string('nacionalidade', 50)->nullable();
            $table->string('nome_pai', 150)->nullable();
            $table->string('nome_mae', 150)->nullable();
            $table->string('escolaridade', 50)->nullable();
            $table->string('raca_cor', 30)->nullable();
            $table->string('tipo_sanguineo', 5)->nullable();
            $table->boolean('doador_orgaos')->default(false);
            $table->boolean('pcd')->default(false)->comment('Pessoa com deficiência');
            $table->text('pcd_descricao')->nullable();

            // === Naturalidade ===
            $table->foreignId('naturalidade_cidade_id')->nullable()
                ->constrained('cidades')->nullOnDelete();

            // === Contato ===
            $table->string('telefone_residencial', 20)->nullable();
            $table->string('telefone_celular', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('email_pessoal')->nullable();

            // === Dados Profissionais ===
            $table->string('matricula', 20)->nullable()->unique();
            $table->foreignId('cargo_id')->nullable()->constrained('cargos')->nullOnDelete();
            $table->foreignId('departamento_id')->nullable()->constrained('departamentos')->nullOnDelete();
            $table->foreignId('classificacao_id')->nullable()->constrained('classificacoes')->nullOnDelete();
            $table->date('data_admissao')->nullable();
            $table->date('data_demissao')->nullable();
            $table->enum('regime_contratacao', [
                'clt', 'pj', 'estagio', 'temporario', 'autonomo', 'terceirizado'
            ])->nullable();
            $table->decimal('salario', 15, 2)->nullable();
            $table->enum('jornada', [
                'integral', 'meio_periodo', 'turno_revezamento', 'home_office', 'hibrido'
            ])->nullable();
            $table->time('horario_entrada')->nullable();
            $table->time('horario_saida')->nullable();

            // === Dados Bancários ===
            $table->string('banco_codigo', 5)->nullable();
            $table->string('banco_nome', 100)->nullable();
            $table->string('banco_agencia', 10)->nullable();
            $table->string('banco_conta', 20)->nullable();
            $table->enum('banco_tipo_conta', ['corrente', 'poupanca', 'salario'])->nullable();
            $table->string('pix_chave', 100)->nullable();

            // === Foto ===
            $table->string('foto_path')->nullable()->comment('Caminho no storage privado');

            // === Observações ===
            $table->text('observacoes')->nullable();

            // === Auditoria ===
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            // === Índices ===
            $table->index('nome');
            $table->index('data_admissao');
            $table->index('cargo_id');
            $table->index('departamento_id');
        });

        // CPF único entre colaboradores ativos (não deletados)
        // Postgres permite unique parcial — perfeito pra soft delete
        \DB::statement('CREATE UNIQUE INDEX colaboradores_cpf_unique ON colaboradores (cpf) WHERE deleted_at IS NULL');

        // PIS único entre ativos (quando preenchido)
        \DB::statement('CREATE UNIQUE INDEX colaboradores_pis_unique ON colaboradores (pis) WHERE deleted_at IS NULL AND pis IS NOT NULL');

        // Busca trigram por nome (busca rápida tipo "MARIA%")
        \DB::statement('CREATE INDEX colaboradores_nome_trgm_idx ON colaboradores USING gin (nome gin_trgm_ops)');

        // Adiciona FK user → colaborador agora que a tabela existe
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('colaborador_id')->references('id')->on('colaboradores')->nullOnDelete();
        });

        // === Endereços do colaborador ===
        Schema::create('colaborador_enderecos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('colaborador_id')->constrained('colaboradores')->cascadeOnDelete();
            $table->enum('tipo', ['residencial', 'comercial', 'correspondencia'])
                ->default('residencial');
            $table->string('cep', 10)->nullable();
            $table->string('logradouro', 200);
            $table->string('numero', 20)->nullable();
            $table->string('complemento', 100)->nullable();
            $table->string('bairro', 100)->nullable();
            $table->foreignId('cidade_id')->nullable()->constrained('cidades')->nullOnDelete();
            $table->boolean('principal')->default(true);
            $table->timestampsTz();

            $table->index(['colaborador_id', 'tipo']);
        });

        // === Dependentes ===
        // No legado isso era texto livre. Aqui estruturamos.
        Schema::create('colaborador_dependentes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('colaborador_id')->constrained('colaboradores')->cascadeOnDelete();
            $table->string('nome', 150);
            $table->date('data_nascimento')->nullable();
            $table->enum('parentesco', [
                'filho', 'filha', 'enteado', 'enteada', 'conjuge',
                'companheiro', 'companheira', 'pai', 'mae', 'outro'
            ]);
            $table->string('cpf', 14)->nullable();
            $table->boolean('dependente_ir')->default(false)->comment('Dependente para IR');
            $table->boolean('dependente_salario_familia')->default(false);
            $table->boolean('pcd')->default(false);
            $table->text('observacoes')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index('colaborador_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['colaborador_id']);
        });

        Schema::dropIfExists('colaborador_dependentes');
        Schema::dropIfExists('colaborador_enderecos');
        Schema::dropIfExists('colaboradores');
    }
};
