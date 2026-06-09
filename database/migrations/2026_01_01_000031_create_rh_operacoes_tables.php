<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Operações de RH ligadas ao colaborador:
 *   - Advertências (verbal, escrita, suspensão)
 *   - Atestados médicos (com anexo)
 *   - Férias (gozadas, programadas, abono)
 *   - Documentação do funcionário (uploads diversos)
 */
return new class extends Migration
{
    public function up(): void
    {
        // === Advertências ===
        Schema::create('advertencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('colaborador_id')->constrained('colaboradores')->restrictOnDelete();
            $table->enum('tipo', ['verbal', 'escrita', 'suspensao'])->default('escrita');
            $table->date('data_ocorrencia');
            $table->date('data_aplicacao');
            $table->text('motivo');
            $table->text('descricao_ocorrencia');
            $table->unsignedSmallInteger('dias_suspensao')->nullable()
                ->comment('Apenas para tipo=suspensao');
            $table->foreignId('aplicado_por_id')->nullable()
                ->constrained('colaboradores')->nullOnDelete()
                ->comment('Quem aplicou a advertência');
            $table->boolean('ciente_colaborador')->default(false);
            $table->timestampTz('data_ciencia')->nullable();
            $table->string('documento_path')->nullable()
                ->comment('Caminho do PDF assinado (storage privado)');
            $table->text('observacoes')->nullable();

            $table->timestampsTz();
            $table->softDeletesTz();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['colaborador_id', 'data_ocorrencia']);
            $table->index('data_aplicacao');
        });

        // === Atestados ===
        Schema::create('atestados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('colaborador_id')->constrained('colaboradores')->restrictOnDelete();
            $table->enum('tipo', [
                'medico', 'odontologico', 'acompanhante', 'declaracao_comparecimento'
            ])->default('medico');
            $table->date('data_inicio');
            $table->date('data_fim');
            $table->unsignedSmallInteger('dias_afastamento')
                ->comment('Calculado: data_fim - data_inicio + 1');
            $table->string('cid', 10)->nullable()->comment('CID-10 (opcional, dado sensível)');
            $table->string('medico_nome', 150)->nullable();
            $table->string('medico_crm', 20)->nullable();
            $table->string('medico_crm_uf', 5)->nullable();
            $table->text('observacoes')->nullable();

            // Status do atestado
            $table->enum('status', ['pendente', 'aprovado', 'rejeitado'])->default('pendente');
            $table->foreignId('aprovado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('data_aprovacao')->nullable();
            $table->text('motivo_rejeicao')->nullable();

            // Arquivo anexado (obrigatório)
            $table->string('arquivo_path')->comment('Caminho do atestado no storage privado');
            $table->string('arquivo_nome_original')->nullable();
            $table->string('arquivo_mime', 100)->nullable();
            $table->unsignedInteger('arquivo_tamanho_bytes')->nullable();
            $table->string('arquivo_hash', 64)->nullable()->comment('SHA-256 para detecção de duplicatas');

            $table->timestampsTz();
            $table->softDeletesTz();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['colaborador_id', 'data_inicio']);
            $table->index('status');

            // Validação: data_fim >= data_inicio (constraint a nível de banco)
        });
        \DB::statement('ALTER TABLE atestados ADD CONSTRAINT atestados_data_fim_check CHECK (data_fim >= data_inicio)');

        // === Férias ===
        Schema::create('ferias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('colaborador_id')->constrained('colaboradores')->restrictOnDelete();

            // Período aquisitivo (12 meses de trabalho que geram o direito)
            $table->date('periodo_aquisitivo_inicio');
            $table->date('periodo_aquisitivo_fim');

            // Período de gozo (quando o colaborador efetivamente sai)
            $table->date('data_inicio_gozo')->nullable();
            $table->date('data_fim_gozo')->nullable();
            $table->unsignedSmallInteger('dias_gozo')->nullable();

            // Abono pecuniário (vende até 10 dias)
            $table->boolean('abono_pecuniario')->default(false);
            $table->unsignedSmallInteger('dias_abono')->default(0);

            // Adiantamento do 13º junto (regra CLT)
            $table->boolean('adiantar_13_salario')->default(false);

            // Status de fluxo
            $table->enum('status', [
                'programada', 'aprovada', 'em_gozo', 'concluida', 'cancelada'
            ])->default('programada');

            $table->foreignId('aprovado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('data_aprovacao')->nullable();
            $table->text('observacoes')->nullable();

            $table->timestampsTz();
            $table->softDeletesTz();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['colaborador_id', 'periodo_aquisitivo_inicio']);
            $table->index('status');
        });

        // === Documentação do Funcionário ===
        // Repositório genérico de documentos do colaborador
        // (RG, CPF, comprovante de residência, certificados, etc)
        Schema::create('colaborador_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('colaborador_id')->constrained('colaboradores')->cascadeOnDelete();
            $table->string('titulo', 200);
            $table->enum('categoria', [
                'identificacao', 'comprovante_residencia', 'certidao',
                'certificado', 'diploma', 'contrato', 'exame_medico',
                'foto', 'asignatura', 'outro'
            ])->default('outro');
            $table->text('descricao')->nullable();
            $table->date('data_emissao')->nullable();
            $table->date('data_validade')->nullable()
                ->comment('Para documentos com validade — sistema pode alertar próximos do vencimento');

            // Arquivo
            $table->string('arquivo_path');
            $table->string('arquivo_nome_original');
            $table->string('arquivo_mime', 100);
            $table->unsignedInteger('arquivo_tamanho_bytes');
            $table->string('arquivo_hash', 64)->nullable();

            $table->timestampsTz();
            $table->softDeletesTz();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['colaborador_id', 'categoria']);
            $table->index('data_validade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colaborador_documentos');
        Schema::dropIfExists('ferias');
        Schema::dropIfExists('atestados');
        Schema::dropIfExists('advertencias');
    }
};
