<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Estrutura organizacional: cargos, departamentos e classificações.
 *
 * No legado eram tabelas separadas sem FK, com Status='Ativado'/'Desativado'.
 * Aqui usamos soft delete nativo do Laravel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departamentos', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 150);
            $table->string('sigla', 20)->nullable();
            $table->text('descricao')->nullable();

            // Estrutura hierárquica (departamento pai)
            $table->foreignId('departamento_pai_id')->nullable()
                ->constrained('departamentos')->nullOnDelete();

            $table->timestampsTz();
            $table->softDeletesTz();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->unique(['nome', 'deleted_at']); // permite reusar nome após exclusão
        });

        Schema::create('cargos', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 150);
            $table->string('cbo', 10)->nullable()->comment('Código Brasileiro de Ocupações');
            $table->text('descricao')->nullable();
            $table->text('atribuicoes')->nullable();
            $table->text('requisitos')->nullable();

            // Faixa salarial (opcional, para gestão de cargos)
            $table->decimal('salario_minimo', 15, 2)->nullable();
            $table->decimal('salario_maximo', 15, 2)->nullable();

            // Vinculado a um departamento (opcional)
            $table->foreignId('departamento_id')->nullable()
                ->constrained('departamentos')->nullOnDelete();

            $table->timestampsTz();
            $table->softDeletesTz();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index('nome');
        });

        // Classificações: categorias administrativas (ex: Operacional, Administrativo,
        // Comercial). No legado existia tabela `classificacao` que era usada como
        // tag dos colaboradores.
        Schema::create('classificacoes', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100);
            $table->string('cor_hex', 7)->nullable()->comment('Cor para exibição no front, ex: #3B82F6');
            $table->text('descricao')->nullable();

            $table->timestampsTz();
            $table->softDeletesTz();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index('nome');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classificacoes');
        Schema::dropIfExists('cargos');
        Schema::dropIfExists('departamentos');
    }
};
