<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Módulo de Compras.
 *
 * Fluxo (como deduzido do legado):
 *   Requisição → Liberação → Aprovação → Pedido de Compra → Recebimento
 *
 * No legado o pedido tinha múltiplos status espalhados em tabelas separadas.
 * Aqui consolidamos em uma máquina de estados clara.
 */
return new class extends Migration
{
    public function up(): void
    {
        // === Fornecedores ===
        Schema::create('fornecedores', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo_pessoa', ['fisica', 'juridica'])->default('juridica');
            $table->string('razao_social', 200);
            $table->string('nome_fantasia', 200)->nullable();
            $table->string('cnpj_cpf', 18)->comment('Formato: 00.000.000/0000-00 ou 000.000.000-00');
            $table->string('inscricao_estadual', 30)->nullable();
            $table->string('inscricao_municipal', 30)->nullable();

            // Contato
            $table->string('email')->nullable();
            $table->string('telefone', 20)->nullable();
            $table->string('celular', 20)->nullable();
            $table->string('site', 200)->nullable();
            $table->string('contato_nome', 150)->nullable();
            $table->string('contato_cargo', 100)->nullable();

            // Endereço
            $table->string('cep', 10)->nullable();
            $table->string('logradouro', 200)->nullable();
            $table->string('numero', 20)->nullable();
            $table->string('complemento', 100)->nullable();
            $table->string('bairro', 100)->nullable();
            $table->foreignId('cidade_id')->nullable()->constrained('cidades')->nullOnDelete();

            // Dados bancários
            $table->string('banco_codigo', 5)->nullable();
            $table->string('banco_agencia', 10)->nullable();
            $table->string('banco_conta', 20)->nullable();
            $table->string('pix_chave', 100)->nullable();

            // Avaliação interna
            $table->unsignedTinyInteger('avaliacao')->nullable()
                ->comment('Nota de 1 a 5');
            $table->text('observacoes')->nullable();
            $table->boolean('homologado')->default(false);

            $table->timestampsTz();
            $table->softDeletesTz();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index('razao_social');
            $table->index('homologado');
        });

        \DB::statement('CREATE UNIQUE INDEX fornecedores_cnpj_cpf_unique ON fornecedores (cnpj_cpf) WHERE deleted_at IS NULL');

        // === Grupos de Materiais ===
        Schema::create('grupos_materiais', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100);
            $table->string('codigo', 20)->nullable();
            $table->text('descricao')->nullable();
            $table->foreignId('grupo_pai_id')->nullable()
                ->constrained('grupos_materiais')->nullOnDelete();

            $table->timestampsTz();
            $table->softDeletesTz();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
        });

        // === Materiais ===
        Schema::create('materiais', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30)->nullable()->comment('Código interno ou SKU');
            $table->string('nome', 200);
            $table->text('descricao')->nullable();
            $table->foreignId('grupo_id')->nullable()
                ->constrained('grupos_materiais')->nullOnDelete();

            // Unidade de medida (UN, KG, M, L, CX, etc)
            $table->string('unidade_medida', 10);

            // Estoque
            $table->decimal('estoque_atual', 15, 4)->default(0);
            $table->decimal('estoque_minimo', 15, 4)->default(0);
            $table->decimal('estoque_maximo', 15, 4)->nullable();
            $table->decimal('preco_referencia', 15, 4)->nullable();

            $table->string('localizacao_estoque', 100)->nullable()
                ->comment('Ex: "Almoxarifado A - Prateleira 3"');

            $table->timestampsTz();
            $table->softDeletesTz();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index('nome');
            $table->index('grupo_id');
        });

        \DB::statement('CREATE UNIQUE INDEX materiais_codigo_unique ON materiais (codigo) WHERE deleted_at IS NULL AND codigo IS NOT NULL');
        \DB::statement('CREATE INDEX materiais_nome_trgm_idx ON materiais USING gin (nome gin_trgm_ops)');

        // === Pedidos de Compra ===
        Schema::create('pedidos_compra', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 30)->unique()
                ->comment('Número do pedido (gerado automaticamente: ano/sequencial)');

            $table->foreignId('fornecedor_id')->constrained('fornecedores')->restrictOnDelete();
            $table->foreignId('solicitante_id')->constrained('colaboradores')->restrictOnDelete();

            $table->date('data_pedido');
            $table->date('data_entrega_prevista')->nullable();
            $table->date('data_entrega_realizada')->nullable();

            // Máquina de estados
            $table->enum('status', [
                'rascunho',        // criando
                'aguardando_liberacao',
                'liberado',         // liberação aprovada (1º passo)
                'aguardando_aprovacao',
                'aprovado',         // aprovação final (2º passo)
                'enviado_fornecedor',
                'parcialmente_recebido',
                'recebido',         // finalizado
                'cancelado',
                'rejeitado'
            ])->default('rascunho');

            $table->decimal('valor_total', 15, 2)->default(0);
            $table->decimal('valor_desconto', 15, 2)->default(0);
            $table->decimal('valor_frete', 15, 2)->default(0);
            $table->decimal('valor_final', 15, 2)->default(0);

            $table->enum('forma_pagamento', [
                'a_vista', 'boleto', 'transferencia', 'cartao', 'pix', 'cheque', 'parcelado', 'outro'
            ])->nullable();
            $table->unsignedTinyInteger('parcelas')->default(1);

            $table->text('observacoes')->nullable();
            $table->text('justificativa')->nullable()->comment('Por que o pedido é necessário');

            $table->timestampsTz();
            $table->softDeletesTz();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index('status');
            $table->index('data_pedido');
            $table->index('fornecedor_id');
        });

        // === Itens do Pedido ===
        Schema::create('pedido_compra_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_compra_id')->constrained('pedidos_compra')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materiais')->restrictOnDelete();
            $table->decimal('quantidade', 15, 4);
            $table->decimal('preco_unitario', 15, 4);
            $table->decimal('subtotal', 15, 2);
            $table->decimal('quantidade_recebida', 15, 4)->default(0);
            $table->text('observacoes')->nullable();
            $table->timestampsTz();

            $table->index('pedido_compra_id');
        });

        // === Histórico de Aprovações ===
        // Registra cada etapa do fluxo de aprovação (auditoria completa)
        Schema::create('pedido_compra_aprovacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_compra_id')->constrained('pedidos_compra')->cascadeOnDelete();
            $table->enum('etapa', ['liberacao', 'aprovacao'])
                ->comment('Tipo de aprovação no fluxo');
            $table->enum('decisao', ['aprovado', 'rejeitado', 'pendente']);
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->text('comentario')->nullable();
            $table->timestampsTz();

            $table->index(['pedido_compra_id', 'etapa']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_compra_aprovacoes');
        Schema::dropIfExists('pedido_compra_itens');
        Schema::dropIfExists('pedidos_compra');
        Schema::dropIfExists('materiais');
        Schema::dropIfExists('grupos_materiais');
        Schema::dropIfExists('fornecedores');
    }
};
