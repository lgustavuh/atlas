<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona campos de documento ao veiculo (CRLV em PDF) e completa
 * os campos do comprovante de manutencao (originalmente so tinha o path).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('veiculos', function (Blueprint $table) {
            // Path relativo do PDF do documento do veiculo (CRLV / documento oficial)
            $table->string('documento_path', 500)->nullable()->after('apolice');
            // Hash SHA-256 do arquivo (auditoria + dedupe)
            $table->string('documento_hash', 64)->nullable()->after('documento_path');
            // Nome original do arquivo (pra exibir / download)
            $table->string('documento_nome_original', 255)->nullable()->after('documento_hash');
        });

        Schema::table('veiculo_manutencoes', function (Blueprint $table) {
            // Manutencao ja tinha comprovante_path mas faltavam metadados
            // pra servir o arquivo de volta com nome correto e Content-Type.
            $table->string('comprovante_hash', 64)->nullable()->after('comprovante_path');
            $table->string('comprovante_nome_original', 255)->nullable()->after('comprovante_hash');
            $table->string('comprovante_mime', 100)->nullable()->after('comprovante_nome_original');
        });
    }

    public function down(): void
    {
        Schema::table('veiculos', function (Blueprint $table) {
            $table->dropColumn(['documento_path', 'documento_hash', 'documento_nome_original']);
        });

        Schema::table('veiculo_manutencoes', function (Blueprint $table) {
            $table->dropColumn(['comprovante_hash', 'comprovante_nome_original', 'comprovante_mime']);
        });
    }
};
