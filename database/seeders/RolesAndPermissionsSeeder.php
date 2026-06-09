<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeder de Permissões e Perfis.
 *
 * Substitui as ~30 colunas NivelXxx do legado por estrutura relacional.
 *
 * Padrão de nomenclatura: `<modulo>.<acao>`
 *   Ações comuns: view, view-any, create, update, delete, restore, export
 *   Algumas têm ações específicas: approve, reject, etc.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Lista de módulos e suas ações disponíveis.
     * Centralizada para facilitar adicionar/remover.
     */
    private array $modules = [
        // Administração
        'users'         => ['view-any', 'view', 'create', 'update', 'delete', 'restore'],
        'roles'         => ['view-any', 'view', 'create', 'update', 'delete'],
        'permissions'   => ['view-any'],
        'audit'         => ['view-any', 'view'],

        // RH base
        'colaboradores' => ['view-any', 'view', 'create', 'update', 'delete', 'restore', 'export', 'view-salary'],
        'cargos'        => ['view-any', 'view', 'create', 'update', 'delete', 'restore'],
        'departamentos' => ['view-any', 'view', 'create', 'update', 'delete', 'restore'],
        'classificacoes' => ['view-any', 'view', 'create', 'update', 'delete', 'restore'],

        // RH operações
        'advertencias'  => ['view-any', 'view', 'create', 'update', 'delete'],
        'atestados'     => ['view-any', 'view', 'create', 'update', 'delete', 'approve', 'reject'],
        'ferias'        => ['view-any', 'view', 'create', 'update', 'delete', 'approve', 'reject'],
        'documentos-colaborador' => ['view-any', 'view', 'create', 'update', 'delete'],

        // Compras
        'fornecedores'  => ['view-any', 'view', 'create', 'update', 'delete', 'restore'],
        'materiais'     => ['view-any', 'view', 'create', 'update', 'delete', 'restore'],
        'pedidos-compra' => ['view-any', 'view', 'create', 'update', 'delete', 'liberar', 'aprovar', 'rejeitar', 'receber'],

        // Patrimônio
        'veiculos'      => ['view-any', 'view', 'create', 'update', 'delete', 'restore'],
        'manutencoes'   => ['view-any', 'view', 'create', 'update', 'delete'],

        // Administrativo
        'alertas-adm'   => ['view-any', 'view', 'create', 'update', 'delete'],
        'obras'         => ['view-any', 'view', 'create', 'update', 'delete'],
        'republicas'    => ['view-any', 'view', 'create', 'update', 'delete'],
        'biblioteca'    => ['view-any', 'view', 'create', 'update', 'delete', 'download'],
        'compartilhamentos' => ['view-any', 'view', 'create', 'update', 'delete'],
        'recrutamento'  => ['view-any', 'view', 'create', 'update', 'delete'],
        'transporte-hospedagem' => ['view-any', 'view', 'create', 'update', 'delete'],
    ];

    public function run(): void
    {
        // Limpa o cache do spatie antes de mexer
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 1. Cria todas as permissões
        $allPermissions = [];
        foreach ($this->modules as $module => $actions) {
            foreach ($actions as $action) {
                $permName = "{$module}.{$action}";
                Permission::firstOrCreate([
                    'name' => $permName,
                    'guard_name' => 'web',
                ]);
                $allPermissions[] = $permName;
            }
        }

        // 2. Cria os perfis e atribui permissões

        // ADMIN — tem tudo
        $admin = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);
        $admin->update([
            'display_name' => 'Administrador',
            'description' => 'Acesso total ao sistema, incluindo gestão de usuários e permissões.',
        ]);
        $admin->syncPermissions(Permission::all());

        // GESTOR RH — gestão completa de pessoas
        $gestorRh = Role::firstOrCreate([
            'name' => 'gestor_rh',
            'guard_name' => 'web',
        ]);
        $gestorRh->update([
            'display_name' => 'Gestor de RH',
            'description' => 'Gerencia colaboradores, cargos, departamentos, advertências, atestados e férias.',
        ]);
        $gestorRh->syncPermissions([
            // Colaboradores
            'colaboradores.view-any', 'colaboradores.view', 'colaboradores.create',
            'colaboradores.update', 'colaboradores.delete', 'colaboradores.restore',
            'colaboradores.export', 'colaboradores.view-salary',
            // Estrutura
            'cargos.view-any', 'cargos.view', 'cargos.create', 'cargos.update', 'cargos.delete',
            'departamentos.view-any', 'departamentos.view', 'departamentos.create', 'departamentos.update', 'departamentos.delete',
            'classificacoes.view-any', 'classificacoes.view', 'classificacoes.create', 'classificacoes.update', 'classificacoes.delete',
            // Operações
            'advertencias.view-any', 'advertencias.view', 'advertencias.create', 'advertencias.update', 'advertencias.delete',
            'atestados.view-any', 'atestados.view', 'atestados.create', 'atestados.update', 'atestados.delete', 'atestados.approve', 'atestados.reject',
            'ferias.view-any', 'ferias.view', 'ferias.create', 'ferias.update', 'ferias.delete', 'ferias.approve', 'ferias.reject',
            'documentos-colaborador.view-any', 'documentos-colaborador.view',
            'documentos-colaborador.create', 'documentos-colaborador.update', 'documentos-colaborador.delete',
            // Recrutamento
            'recrutamento.view-any', 'recrutamento.view', 'recrutamento.create',
            'recrutamento.update', 'recrutamento.delete',
            // Auditoria
            'audit.view-any', 'audit.view',
        ]);

        // ALMOXARIFE — gestão de materiais e pedidos
        $almoxarife = Role::firstOrCreate([
            'name' => 'almoxarife',
            'guard_name' => 'web',
        ]);
        $almoxarife->update([
            'display_name' => 'Almoxarife',
            'description' => 'Gerencia materiais, fornecedores, pedidos de compra e estoque.',
        ]);
        $almoxarife->syncPermissions([
            'fornecedores.view-any', 'fornecedores.view', 'fornecedores.create', 'fornecedores.update', 'fornecedores.delete',
            'materiais.view-any', 'materiais.view', 'materiais.create', 'materiais.update', 'materiais.delete',
            'pedidos-compra.view-any', 'pedidos-compra.view', 'pedidos-compra.create', 'pedidos-compra.update', 'pedidos-compra.receber',
        ]);

        // APROVADOR DE COMPRAS — só aprova
        $aprovadorCompras = Role::firstOrCreate([
            'name' => 'aprovador_compras',
            'guard_name' => 'web',
        ]);
        $aprovadorCompras->update([
            'display_name' => 'Aprovador de Compras',
            'description' => 'Libera e aprova pedidos de compra. Sem permissão para criar.',
        ]);
        $aprovadorCompras->syncPermissions([
            'pedidos-compra.view-any', 'pedidos-compra.view',
            'pedidos-compra.liberar', 'pedidos-compra.aprovar', 'pedidos-compra.rejeitar',
            'fornecedores.view-any', 'fornecedores.view',
            'materiais.view-any', 'materiais.view',
        ]);

        // GESTOR DE FROTA
        $gestorFrota = Role::firstOrCreate([
            'name' => 'gestor_frota',
            'guard_name' => 'web',
        ]);
        $gestorFrota->update([
            'display_name' => 'Gestor de Frota',
            'description' => 'Gerencia veículos, manutenções e transporte.',
        ]);
        $gestorFrota->syncPermissions([
            'veiculos.view-any', 'veiculos.view', 'veiculos.create', 'veiculos.update', 'veiculos.delete',
            'manutencoes.view-any', 'manutencoes.view', 'manutencoes.create', 'manutencoes.update', 'manutencoes.delete',
            'transporte-hospedagem.view-any', 'transporte-hospedagem.view',
            'transporte-hospedagem.create', 'transporte-hospedagem.update', 'transporte-hospedagem.delete',
        ]);

        // VISUALIZADOR — só leitura geral (auditor, diretoria)
        $visualizador = Role::firstOrCreate([
            'name' => 'visualizador',
            'guard_name' => 'web',
        ]);
        $visualizador->update([
            'display_name' => 'Visualizador',
            'description' => 'Acesso somente leitura para consulta. Sem direito a editar.',
        ]);
        // Apenas todas as permissões de view e view-any, exceto as administrativas (users/roles)
        $viewPermissions = Permission::where(function ($q) {
                $q->where('name', 'LIKE', '%.view')
                  ->orWhere('name', 'LIKE', '%.view-any');
            })
            ->where('name', 'NOT LIKE', 'users.%')
            ->where('name', 'NOT LIKE', 'roles.%')
            ->get();
        $visualizador->syncPermissions($viewPermissions);

        // COLABORADOR — perfil mínimo, vê apenas o próprio
        // Esse perfil tem permissões "wide" mas as Policies vão restringir a "só os próprios dados"
        $colaborador = Role::firstOrCreate([
            'name' => 'colaborador',
            'guard_name' => 'web',
        ]);
        $colaborador->update([
            'display_name' => 'Colaborador',
            'description' => 'Acesso aos próprios dados, alertas e biblioteca.',
        ]);
        $colaborador->syncPermissions([
            'biblioteca.view-any', 'biblioteca.view', 'biblioteca.download',
            'compartilhamentos.view-any', 'compartilhamentos.view',
        ]);

        // Limpa cache novamente
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command->info('  ✓ ' . Permission::count() . ' permissões criadas');
        $this->command->info('  ✓ ' . Role::count() . ' perfis criados');
    }
}
