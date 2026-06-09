<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Módulo de Veículos e Manutenções.
 *
 * No legado, veículos e manutenções eram tabelas separadas sem ligação clara.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('veiculos', function (Blueprint $table) {
            $table->id();
            $table->string('placa', 10)->comment('AAA-0000 ou AAA0A00 (Mercosul)');
            $table->string('renavam', 20)->nullable();
            $table->string('chassi', 25)->nullable();
            $table->string('marca', 50);
            $table->string('modelo', 100);
            $table->unsignedSmallInteger('ano_fabricacao')->nullable();
            $table->unsignedSmallInteger('ano_modelo')->nullable();
            $table->string('cor', 30)->nullable();
            $table->enum('combustivel', ['gasolina', 'etanol', 'flex', 'diesel', 'eletrico', 'hibrido', 'gnv'])->nullable();
            $table->enum('categoria', ['passeio', 'utilitario', 'caminhao', 'moto', 'onibus', 'maquina_pesada', 'outro'])->nullable();
            $table->unsignedInteger('km_atual')->default(0);
            $table->date('data_aquisicao')->nullable();
            $table->decimal('valor_aquisicao', 15, 2)->nullable();

            $table->enum('status', [
                'disponivel', 'em_uso', 'em_manutencao', 'inativo', 'vendido'
            ])->default('disponivel');

            // Documentação
            $table->date('licenciamento_vencimento')->nullable();
            $table->date('seguro_vencimento')->nullable();
            $table->string('seguradora', 100)->nullable();
            $table->string('apolice', 50)->nullable();

            // Responsável atual (motorista)
            $table->foreignId('responsavel_id')->nullable()
                ->constrained('colaboradores')->nullOnDelete();

            $table->text('observacoes')->nullable();

            $table->timestampsTz();
            $table->softDeletesTz();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index('status');
            $table->index('licenciamento_vencimento');
        });

        \DB::statement('CREATE UNIQUE INDEX veiculos_placa_unique ON veiculos (placa) WHERE deleted_at IS NULL');

        Schema::create('veiculo_manutencoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('veiculo_id')->constrained('veiculos')->cascadeOnDelete();
            $table->enum('tipo', ['preventiva', 'corretiva', 'revisao', 'troca_oleo', 'pneus', 'eletrica', 'funilaria', 'outro']);
            $table->date('data_manutencao');
            $table->unsignedInteger('km_no_momento')->nullable();
            $table->text('descricao');
            $table->text('servicos_realizados')->nullable();
            $table->foreignId('fornecedor_id')->nullable()
                ->constrained('fornecedores')->nullOnDelete()
                ->comment('Oficina/fornecedor que prestou o serviço');
            $table->decimal('valor', 15, 2)->nullable();
            $table->string('nota_fiscal', 30)->nullable();
            $table->date('proxima_manutencao_data')->nullable();
            $table->unsignedInteger('proxima_manutencao_km')->nullable();

            $table->string('comprovante_path')->nullable();

            $table->timestampsTz();
            $table->softDeletesTz();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['veiculo_id', 'data_manutencao']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('veiculo_manutencoes');
        Schema::dropIfExists('veiculos');
    }
};
