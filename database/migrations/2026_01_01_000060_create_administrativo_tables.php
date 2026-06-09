<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Módulos administrativos diversos:
 *   - Alertas administrativos (avisos para usuários)
 *   - Obras (projetos/centros de custo)
 *   - Repúblicas (alojamentos de colaboradores)
 *   - Biblioteca de documentos padronizados
 *   - Compartilhamento de arquivos
 *   - Recrutamento e Seleção
 *   - Transporte e Hospedagem
 */
return new class extends Migration
{
    public function up(): void
    {
        // === Alertas Administrativos ===
        // No legado: AlertaADM + AlertaADMVisualiza
        // Mensagens fixadas pelo admin, visíveis aos usuários selecionados
        Schema::create('alertas_adm', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 200);
            $table->text('mensagem');
            $table->enum('prioridade', ['baixa', 'normal', 'alta', 'critica'])->default('normal');
            $table->boolean('ativo')->default(true);
            $table->date('data_inicio')->nullable();
            $table->date('data_fim')->nullable();

            $table->timestampsTz();
            $table->softDeletesTz();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['ativo', 'data_fim']);
        });

        // Quem deve visualizar cada alerta (N:N)
        Schema::create('alerta_adm_destinatarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alerta_adm_id')->constrained('alertas_adm')->cascadeOnDelete();
            $table->foreignId('colaborador_id')->constrained('colaboradores')->cascadeOnDelete();
            $table->timestampTz('visualizado_em')->nullable();
            $table->timestampsTz();

            $table->unique(['alerta_adm_id', 'colaborador_id']);
            $table->index('colaborador_id');
        });

        // === Obras / Centros de Custo ===
        Schema::create('obras', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30)->nullable();
            $table->string('nome', 200);
            $table->text('descricao')->nullable();
            $table->string('endereco', 300)->nullable();
            $table->foreignId('cidade_id')->nullable()->constrained('cidades')->nullOnDelete();

            $table->foreignId('responsavel_id')->nullable()
                ->constrained('colaboradores')->nullOnDelete();

            $table->date('data_inicio')->nullable();
            $table->date('data_termino_previsto')->nullable();
            $table->date('data_termino_real')->nullable();

            $table->decimal('orcamento', 15, 2)->nullable();

            $table->enum('status', ['planejamento', 'em_andamento', 'pausada', 'concluida', 'cancelada'])
                ->default('planejamento');

            $table->timestampsTz();
            $table->softDeletesTz();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index('status');
        });

        \DB::statement('CREATE UNIQUE INDEX obras_codigo_unique ON obras (codigo) WHERE deleted_at IS NULL AND codigo IS NOT NULL');

        // === Repúblicas (alojamentos) ===
        Schema::create('republicas', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 150);
            $table->string('endereco', 300);
            $table->foreignId('cidade_id')->nullable()->constrained('cidades')->nullOnDelete();
            $table->unsignedSmallInteger('capacidade_total');
            $table->decimal('aluguel_mensal', 15, 2)->nullable();
            $table->string('responsavel_externo_nome', 150)->nullable()
                ->comment('Locador / proprietário');
            $table->string('responsavel_externo_telefone', 20)->nullable();
            $table->boolean('ativa')->default(true);
            $table->text('observacoes')->nullable();

            $table->timestampsTz();
            $table->softDeletesTz();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
        });

        // Ocupação atual de cada república (quem mora ali)
        Schema::create('republica_ocupacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('republica_id')->constrained('republicas')->cascadeOnDelete();
            $table->foreignId('colaborador_id')->constrained('colaboradores')->cascadeOnDelete();
            $table->date('data_entrada');
            $table->date('data_saida')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestampsTz();

            $table->index(['republica_id', 'data_saida']);
            $table->index('colaborador_id');
        });

        // === Biblioteca de Documentos Padronizados ===
        // No legado: BibliotecaPadrao* (vários)
        Schema::create('biblioteca_areas', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 150);
            $table->text('descricao')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();
        });

        Schema::create('biblioteca_documentos', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 200);
            $table->text('descricao')->nullable();
            $table->string('versao', 20)->nullable();

            $table->string('arquivo_path');
            $table->string('arquivo_nome_original');
            $table->string('arquivo_mime', 100);
            $table->unsignedInteger('arquivo_tamanho_bytes');
            $table->string('arquivo_hash', 64)->nullable();

            $table->unsignedInteger('downloads_count')->default(0);

            $table->timestampsTz();
            $table->softDeletesTz();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index('titulo');
        });

        // Áreas que cada documento pertence (N:N)
        Schema::create('biblioteca_documento_areas', function (Blueprint $table) {
            $table->foreignId('documento_id')->constrained('biblioteca_documentos')->cascadeOnDelete();
            $table->foreignId('area_id')->constrained('biblioteca_areas')->cascadeOnDelete();
            $table->primary(['documento_id', 'area_id']);
        });

        // === Compartilhamento de Arquivos ===
        // Upload livre para troca entre usuários (no legado: CompartilhamentoArquivos)
        Schema::create('compartilhamentos', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 200);
            $table->text('descricao')->nullable();

            $table->string('arquivo_path');
            $table->string('arquivo_nome_original');
            $table->string('arquivo_mime', 100);
            $table->unsignedInteger('arquivo_tamanho_bytes');
            $table->string('arquivo_hash', 64)->nullable();

            $table->date('data_expiracao')->nullable()
                ->comment('Compartilhamentos podem ter prazo');

            $table->unsignedInteger('downloads_count')->default(0);

            $table->timestampsTz();
            $table->softDeletesTz();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
        });

        // === Recrutamento e Seleção ===
        Schema::create('vagas', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 200);
            $table->foreignId('cargo_id')->nullable()->constrained('cargos')->nullOnDelete();
            $table->foreignId('departamento_id')->nullable()->constrained('departamentos')->nullOnDelete();
            $table->text('descricao');
            $table->text('requisitos')->nullable();
            $table->text('beneficios')->nullable();
            $table->decimal('salario_de', 15, 2)->nullable();
            $table->decimal('salario_ate', 15, 2)->nullable();
            $table->boolean('salario_publicar')->default(false);
            $table->unsignedSmallInteger('quantidade_vagas')->default(1);
            $table->date('data_abertura')->nullable();
            $table->date('data_fechamento')->nullable();
            $table->enum('status', ['rascunho', 'aberta', 'em_selecao', 'preenchida', 'cancelada'])->default('rascunho');

            $table->timestampsTz();
            $table->softDeletesTz();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index('status');
        });

        Schema::create('candidatos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vaga_id')->constrained('vagas')->cascadeOnDelete();
            $table->string('nome', 150);
            $table->string('cpf', 14)->nullable();
            $table->string('email');
            $table->string('telefone', 20)->nullable();
            $table->text('experiencia')->nullable();
            $table->string('curriculo_path')->nullable();
            $table->enum('status', ['inscrito', 'triagem', 'entrevista', 'aprovado', 'rejeitado', 'contratado'])
                ->default('inscrito');
            $table->text('observacoes')->nullable();
            $table->unsignedTinyInteger('pontuacao')->nullable();

            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index(['vaga_id', 'status']);
        });

        // === Transporte e Hospedagem ===
        Schema::create('transportes_hospedagens', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['transporte', 'hospedagem', 'ambos']);
            $table->foreignId('colaborador_id')->constrained('colaboradores')->restrictOnDelete();
            $table->foreignId('obra_id')->nullable()->constrained('obras')->nullOnDelete();
            $table->date('data_inicio');
            $table->date('data_fim')->nullable();

            // Transporte
            $table->string('origem', 200)->nullable();
            $table->string('destino', 200)->nullable();
            $table->enum('meio_transporte', ['onibus', 'aviao', 'carro_proprio', 'carro_empresa', 'van', 'outro'])->nullable();

            // Hospedagem
            $table->string('hospedagem_local', 200)->nullable();
            $table->string('hospedagem_endereco', 300)->nullable();

            $table->decimal('valor', 15, 2)->nullable();
            $table->foreignId('fornecedor_id')->nullable()->constrained('fornecedores')->nullOnDelete();
            $table->text('observacoes')->nullable();

            $table->timestampsTz();
            $table->softDeletesTz();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index('colaborador_id');
            $table->index('data_inicio');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transportes_hospedagens');
        Schema::dropIfExists('candidatos');
        Schema::dropIfExists('vagas');
        Schema::dropIfExists('compartilhamentos');
        Schema::dropIfExists('biblioteca_documento_areas');
        Schema::dropIfExists('biblioteca_documentos');
        Schema::dropIfExists('biblioteca_areas');
        Schema::dropIfExists('republica_ocupacoes');
        Schema::dropIfExists('republicas');
        Schema::dropIfExists('obras');
        Schema::dropIfExists('alerta_adm_destinatarios');
        Schema::dropIfExists('alertas_adm');
    }
};
