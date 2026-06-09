<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Índices de performance para v1.8.
 *
 * Adiciona índices nas colunas usadas em filtros do dashboard e listings
 * que ainda não tinham índice próprio.
 *
 * Ganho típico: queries de "vencendo em 30 dias" ou "obras atrasadas"
 * passam de seq scan para index scan, performance escala melhor com
 * crescimento da base de dados.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Veículos: seguro_vencimento já é usado em Veiculo::seguroProximo()
        Schema::table('veiculos', function (Blueprint $table) {
            $table->index('seguro_vencimento');
        });

        // Obras: data_termino_previsto é usado no dashboard pra detectar atrasadas
        Schema::table('obras', function (Blueprint $table) {
            $table->index('data_termino_previsto');
        });

        // Materiais: estoque_atual + estoque_minimo são usados em Material::abaixoDoMinimo()
        // Índice composto pra resolver a condicao WHERE estoque_atual < estoque_minimo
        // Como nao da pra indexar "menor que coluna", indexa estoque_atual
        Schema::table('materiais', function (Blueprint $table) {
            $table->index('estoque_atual');
        });

        // Atestados: data_aplicacao + data_inicio sao usados em filtros
        Schema::table('atestados', function (Blueprint $table) {
            $table->index('data_inicio');
        });
    }

    public function down(): void
    {
        Schema::table('veiculos', function (Blueprint $table) {
            $table->dropIndex(['seguro_vencimento']);
        });

        Schema::table('obras', function (Blueprint $table) {
            $table->dropIndex(['data_termino_previsto']);
        });

        Schema::table('materiais', function (Blueprint $table) {
            $table->dropIndex(['estoque_atual']);
        });

        Schema::table('atestados', function (Blueprint $table) {
            $table->dropIndex(['data_inicio']);
        });
    }
};
