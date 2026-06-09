<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration do pacote spatie/laravel-permission, ajustada para o projeto.
 *
 * Substitui as ~30 colunas `NivelXxx` da tabela `usuarionivelacesso` do legado
 * por um sistema relacional flexível:
 *
 *   - permissions:        ações granulares ("colaborador.create", "colaborador.delete")
 *   - roles:              perfis ("admin", "gestor_rh", "almoxarife", "visualizador")
 *   - model_has_permissions: usuário com permissões diretas (exceções)
 *   - model_has_roles:    usuário tem um ou mais perfis
 *   - role_has_permissions: perfil concede permissões
 *
 * Vantagens:
 *   - Adicionar novo módulo não exige ALTER TABLE
 *   - Auditável: dá pra ver exatamente quem tem o quê
 *   - Cacheável: permissões são cacheadas em Redis
 *   - Granular: permissões por ação (view, create, update, delete, export)
 */
return new class extends Migration
{
    public function up(): void
    {
        $teams = config('permission.teams', false);
        $tableNames = config('permission.table_names', [
            'roles' => 'roles',
            'permissions' => 'permissions',
            'model_has_permissions' => 'model_has_permissions',
            'model_has_roles' => 'model_has_roles',
            'role_has_permissions' => 'role_has_permissions',
        ]);
        $columnNames = config('permission.column_names', [
            'role_pivot_key' => null,
            'permission_pivot_key' => null,
            'model_morph_key' => 'model_id',
            'team_foreign_key' => 'team_id',
        ]);

        Schema::create($tableNames['permissions'], function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 125);       // ex: "colaborador.create"
            $table->string('guard_name', 125); // ex: "web"
            $table->timestampsTz();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create($tableNames['roles'], function (Blueprint $table) use ($teams) {
            $table->bigIncrements('id');
            if ($teams) {
                $table->unsignedBigInteger('team_id')->nullable()->index();
            }
            $table->string('name', 125);       // ex: "gestor_rh"
            $table->string('guard_name', 125);
            // Descrição amigável para UI ("Gestor de RH")
            $table->string('display_name', 150)->nullable();
            $table->text('description')->nullable();
            $table->timestampsTz();
            if ($teams) {
                $table->unique(['team_id', 'name', 'guard_name']);
            } else {
                $table->unique(['name', 'guard_name']);
            }
        });

        Schema::create($tableNames['model_has_permissions'], function (Blueprint $table) use ($tableNames, $columnNames) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key']);
            $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_permissions_model_id_model_type_index');

            $table->foreign('permission_id')
                ->references('id')->on($tableNames['permissions'])->cascadeOnDelete();

            $table->primary(['permission_id', $columnNames['model_morph_key'], 'model_type'],
                'model_has_permissions_permission_model_type_primary');
        });

        Schema::create($tableNames['model_has_roles'], function (Blueprint $table) use ($tableNames, $columnNames) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key']);
            $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_roles_model_id_model_type_index');

            $table->foreign('role_id')
                ->references('id')->on($tableNames['roles'])->cascadeOnDelete();

            $table->primary(['role_id', $columnNames['model_morph_key'], 'model_type'],
                'model_has_roles_role_model_type_primary');
        });

        Schema::create($tableNames['role_has_permissions'], function (Blueprint $table) use ($tableNames) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');

            $table->foreign('permission_id')
                ->references('id')->on($tableNames['permissions'])->cascadeOnDelete();
            $table->foreign('role_id')
                ->references('id')->on($tableNames['roles'])->cascadeOnDelete();

            $table->primary(['permission_id', 'role_id'], 'role_has_permissions_permission_id_role_id_primary');
        });

        app('cache')
            ->store(config('permission.cache.store') !== 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names', [
            'roles' => 'roles',
            'permissions' => 'permissions',
            'model_has_permissions' => 'model_has_permissions',
            'model_has_roles' => 'model_has_roles',
            'role_has_permissions' => 'role_has_permissions',
        ]);

        Schema::drop($tableNames['role_has_permissions']);
        Schema::drop($tableNames['model_has_roles']);
        Schema::drop($tableNames['model_has_permissions']);
        Schema::drop($tableNames['roles']);
        Schema::drop($tableNames['permissions']);
    }
};
