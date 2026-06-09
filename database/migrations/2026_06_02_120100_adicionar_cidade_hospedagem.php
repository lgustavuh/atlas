<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Item 5 do v1.9: cidade da hospedagem.
 *
 * O campo `hospedagem_local` continua sendo nome do hotel/pousada;
 * mas faltava registrar a cidade onde o colaborador esta hospedado,
 * que e dado importante pra logistica/relatorios.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transportes_hospedagens', function (Blueprint $table) {
            $table->foreignId('hospedagem_cidade_id')
                ->nullable()
                ->after('hospedagem_local')
                ->constrained('cidades')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transportes_hospedagens', function (Blueprint $table) {
            $table->dropConstrainedForeignId('hospedagem_cidade_id');
        });
    }
};
