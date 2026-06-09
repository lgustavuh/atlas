<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabela de usuários do sistema.
 *
 * Substitui a tabela `usuarionivelacesso` do legado, que tinha ~30 colunas
 * NivelXxx hardcoded. Aqui só guardamos identidade e autenticação;
 * as permissões ficam nas tabelas do spatie/laravel-permission.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // Identidade
            $table->string('name', 150);
            // citext = case-insensitive nativo no Postgres (não precisa LOWER() em todo lugar)
            $table->string('email')->unique();
            $table->timestampTz('email_verified_at')->nullable();

            // Segurança
            // Senha armazenada como hash Argon2id pelo Laravel
            $table->string('password');
            $table->rememberToken();

            // Status
            $table->boolean('active')->default(true)->index();

            // Para rate limiting de login e detecção de tentativas suspeitas
            $table->timestampTz('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable(); // 45 = IPv6 max
            $table->unsignedInteger('failed_login_attempts')->default(0);
            $table->timestampTz('locked_until')->nullable();

            // Vínculo opcional com colaborador (será criado depois)
            // Nullable porque alguns usuários podem ser puramente administrativos
            $table->foreignId('colaborador_id')->nullable()->index();

            $table->timestampsTz();
            $table->softDeletesTz(); // deleted_at timestamptz

            // Auditoria de quem criou/editou (preenchido por observer)
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
        });

        // Promover email para citext (case-insensitive) usando SQL raw
        // Não pode ser feito direto no schema builder do Laravel
        \DB::statement('ALTER TABLE users ALTER COLUMN email TYPE citext');
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
