<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Estrutura geográfica usada em endereços de colaboradores, fornecedores,
 * obras, etc. Padrão IBGE para Brasil.
 *
 * No legado isso ficava em pastas Localidade/, com tabelas pais/estado/cidade
 * mas sem FKs entre elas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paises', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100);
            $table->string('iso2', 2)->unique()->comment('ISO 3166-1 alpha-2 (BR, US, AR)');
            $table->string('iso3', 3)->unique()->comment('ISO 3166-1 alpha-3 (BRA, USA, ARG)');
            $table->unsignedSmallInteger('codigo_numerico')->nullable()->comment('ISO 3166-1 numeric');
            $table->string('telefone_ddi', 5)->nullable();
            $table->timestampsTz();

            $table->index('nome');
        });

        Schema::create('estados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pais_id')->constrained('paises')->cascadeOnDelete();
            $table->string('nome', 100);
            $table->string('uf', 5)->comment('Sigla do estado (SP, MG, RJ)');
            $table->unsignedInteger('codigo_ibge')->nullable()->unique()->comment('Código IBGE para estados brasileiros');
            $table->timestampsTz();

            $table->unique(['pais_id', 'uf']);
            $table->index('nome');
        });

        Schema::create('cidades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estado_id')->constrained('estados')->cascadeOnDelete();
            $table->string('nome', 150);
            $table->unsignedInteger('codigo_ibge')->nullable()->unique()->comment('Código IBGE de 7 dígitos');
            $table->boolean('capital')->default(false);
            $table->timestampsTz();

            $table->index(['estado_id', 'nome']);
            // Para buscas tipo "começa com..."
            $table->index('nome');
        });

        // Índice GIN para busca fuzzy (pg_trgm) — busca tipo "ITA%" rápida
        \DB::statement('CREATE INDEX cidades_nome_trgm_idx ON cidades USING gin (nome gin_trgm_ops)');
    }

    public function down(): void
    {
        Schema::dropIfExists('cidades');
        Schema::dropIfExists('estados');
        Schema::dropIfExists('paises');
    }
};
