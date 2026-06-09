<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/**
 * Helper para montar o menu lateral.
 *
 * Define todos os itens em um único lugar; cada item declara qual permissão
 * é necessária para vê-lo. Itens sem permissão ficam invisíveis para o
 * usuário (não aparecem nem desabilitados — limpa a UI).
 *
 * Cada seção é uma "categoria" no menu (RH, Compras, etc), e seções vazias
 * (sem nenhum item visível) somem.
 */
class NavigationMenu
{
    /**
     * @return array<string, array{label: string, icon: string, items: list<array{label: string, route: string, icon?: string, permission?: string}>}>
     */
    public static function sections(): array
    {
        $sections = [
            'principal' => [
                'label' => 'Principal',
                'icon' => 'home',
                'items' => [
                    [
                        'label' => 'Dashboard',
                        'route' => 'dashboard',
                        'icon' => 'layout-dashboard',
                    ],
                ],
            ],
            'rh' => [
                'label' => 'Recursos Humanos',
                'icon' => 'users',
                'items' => [
                    [
                        'label' => 'Colaboradores',
                        'route' => 'colaboradores.index',
                        'icon' => 'user',
                        'permission' => 'colaboradores.view-any',
                    ],
                    [
                        'label' => 'Cargos',
                        'route' => 'cargos.index',
                        'icon' => 'briefcase',
                        'permission' => 'cargos.view-any',
                    ],
                    [
                        'label' => 'Departamentos',
                        'route' => 'departamentos.index',
                        'icon' => 'building',
                        'permission' => 'departamentos.view-any',
                    ],
                    [
                        'label' => 'Classificações',
                        'route' => 'classificacoes.index',
                        'icon' => 'tags',
                        'permission' => 'classificacoes.view-any',
                    ],
                    [
                        'label' => 'Advertências',
                        'route' => 'advertencias.index',
                        'icon' => 'alert-octagon',
                        'permission' => 'advertencias.view-any',
                    ],
                    [
                        'label' => 'Atestados',
                        'route' => 'atestados.index',
                        'icon' => 'file-medical',
                        'permission' => 'atestados.view-any',
                    ],
                    [
                        'label' => 'Férias',
                        'route' => 'ferias.index',
                        'icon' => 'beach',
                        'permission' => 'ferias.view-any',
                    ],
                ],
            ],
            'compras' => [
                'label' => 'Compras',
                'icon' => 'shopping-cart',
                'items' => [
                    [
                        'label' => 'Fornecedores',
                        'route' => 'fornecedores.index',
                        'icon' => 'truck',
                        'permission' => 'fornecedores.view-any',
                    ],
                    [
                        'label' => 'Materiais',
                        'route' => 'materiais.index',
                        'icon' => 'package',
                        'permission' => 'materiais.view-any',
                    ],
                    [
                        'label' => 'Grupos de Materiais',
                        'route' => 'materiais.grupos',
                        'icon' => 'category',
                        'permission' => 'materiais.view-any',
                    ],
                    [
                        'label' => 'Pedidos de Compra',
                        'route' => 'pedidos-compra.index',
                        'icon' => 'shopping-bag',
                        'permission' => 'pedidos-compra.view-any',
                    ],
                ],
            ],
            'patrimonio' => [
                'label' => 'Patrimônio',
                'icon' => 'box',
                'items' => [
                    [
                        'label' => 'Veículos',
                        'route' => 'veiculos.index',
                        'icon' => 'car',
                        'permission' => 'veiculos.view-any',
                    ],
                    [
                        'label' => 'Manutenções',
                        'route' => 'manutencoes.index',
                        'icon' => 'tools',
                        'permission' => 'manutencoes.view-any',
                    ],
                    [
                        'label' => 'Transporte/Hospedagem',
                        'route' => 'transporte-hospedagem.index',
                        'icon' => 'route',
                        'permission' => 'transporte-hospedagem.view-any',
                    ],
                ],
            ],
            'administrativo' => [
                'label' => 'Administrativo',
                'icon' => 'folder',
                'items' => [
                    [
                        'label' => 'Obras',
                        'route' => 'obras.index',
                        'icon' => 'building-skyscraper',
                        'permission' => 'obras.view-any',
                    ],
                    [
                        'label' => 'Alertas',
                        'route' => 'alertas.index',
                        'icon' => 'bell',
                        'permission' => 'alertas-adm.view-any',
                    ],
                    [
                        'label' => 'Biblioteca',
                        'route' => 'biblioteca.index',
                        'icon' => 'book',
                        'permission' => 'biblioteca.view-any',
                    ],
                    [
                        'label' => 'Vagas',
                        'route' => 'recrutamento.vagas',
                        'icon' => 'briefcase',
                        'permission' => 'recrutamento.view-any',
                    ],
                    [
                        'label' => 'Candidatos',
                        'route' => 'recrutamento.candidatos',
                        'icon' => 'users',
                        'permission' => 'recrutamento.view-any',
                    ],
                    [
                        'label' => 'Repúblicas',
                        'route' => 'republicas.index',
                        'icon' => 'home',
                        'permission' => 'republicas.view-any',
                    ],
                    [
                        'label' => 'Geografia',
                        'route' => 'geografia.index',
                        'icon' => 'map',
                    ],
                ],
            ],
            'sistema' => [
                'label' => 'Sistema',
                'icon' => 'settings',
                'items' => [
                    [
                        'label' => 'Usuários',
                        'route' => 'users.index',
                        'icon' => 'user-cog',
                        'permission' => 'users.view-any',
                    ],
                    [
                        'label' => 'Perfis',
                        'route' => 'roles.index',
                        'icon' => 'shield-lock',
                        'permission' => 'roles.view-any',
                    ],
                    [
                        'label' => 'Auditoria',
                        'route' => 'auditoria.index',
                        'icon' => 'history',
                        'permission' => 'audit.view-any',
                    ],
                ],
            ],
        ];

        return self::filterVisible($sections);
    }

    /**
     * Filtra itens sem permissão e seções vazias.
     * Também filtra itens cujas rotas ainda não existem (módulos futuros).
     */
    private static function filterVisible(array $sections): array
    {
        $user = Auth::user();

        foreach ($sections as $sectionKey => &$section) {
            $section['items'] = array_values(array_filter(
                $section['items'],
                function (array $item) use ($user): bool {
                    // Item exige permissão? Verifica
                    if (isset($item['permission'])) {
                        if (!$user || !$user->can($item['permission'])) {
                            return false;
                        }
                    }

                    // Rota não registrada ainda? Esconde até implementarmos
                    if (!Route::has($item['route'])) {
                        return false;
                    }

                    return true;
                }
            ));

            // Remove seção se não restou nada
            if ($section['items'] === []) {
                unset($sections[$sectionKey]);
            }
        }

        return $sections;
    }

    /**
     * Verifica se uma rota está "ativa" para destacar no menu.
     */
    public static function isActive(string $routeName): bool
    {
        $current = Route::currentRouteName();
        if (!$current) {
            return false;
        }

        // Match exato OU prefixo (ex: 'users.index' destaca também 'users.create')
        $base = explode('.', $routeName)[0];
        return $current === $routeName || str_starts_with($current, $base . '.');
    }
}
