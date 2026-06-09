<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Dashboard principal.
 *
 * Mostra cards de resumo para módulos que o usuário tem permissão de ver.
 * Cada card é "preguiçoso": só faz query se o usuário pode ver o módulo.
 *
 * Performance: as estatísticas são cacheadas por 60 segundos por usuário.
 * Como a chave inclui o user_id, cada usuário tem seu próprio cache (e as
 * stats sao filtradas por permissoes desse usuario). 60s e curto o suficiente
 * pra dados nao ficarem "velhos" e longo o suficiente pra evitar 13 queries
 * a cada refresh ou navegacao via wire:navigate.
 */
#[Layout('layouts.app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public function render()
    {
        $user = Auth::user();

        // Só admin ve o resumo de estatisticas.
        // Demais perfis veem dashboard em branco por enquanto (ate decidirmos
        // o que cada perfil precisa ver - ver Dashboard::ehAdmin).
        $ehAdmin = $this->ehAdmin($user);

        $stats = [];
        if ($ehAdmin) {
            // Cache por usuario, 60s. Em uma equipe de 50 pessoas isso significa
            // no maximo 50 sets de queries por minuto em vez de N a cada navegacao.
            $stats = Cache::remember(
                'dashboard:stats:user:'.$user->id,
                now()->addSeconds(60),
                fn () => $this->coletarEstatisticas($user),
            );
        }

        return view('livewire.dashboard', [
            'stats' => $stats,
            'saudacao' => $this->saudacao(),
            'ehAdmin' => $ehAdmin,
        ]);
    }

    /**
     * Checa se o usuario tem perfil de admin (full access).
     */
    private function ehAdmin(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Permite invalidar manualmente o cache (chamado por listeners de eventos
     * de criacao/edicao/exclusao - opcional pra v1.9+).
     */
    public static function invalidarCache(int $userId): void
    {
        Cache::forget('dashboard:stats:user:'.$userId);
    }

    /**
     * @return array<string, array{label: string, value: int|string, icon: string, color: string, route: string|null, hint?: string}>
     */
    private function coletarEstatisticas(User $user): array
    {
        $stats = [];

        // Colaboradores (apenas se tem permissão)
        if ($user->can('colaboradores.view-any')) {
            $stats['colaboradores'] = [
                'label' => 'Colaboradores ativos',
                'value' => \App\Models\Colaborador::count(),
                'icon' => 'users',
                'color' => 'indigo',
                'route' => 'colaboradores.index',
                'hint' => 'cadastrados no sistema',
            ];
        }

        // Cargos
        if ($user->can('cargos.view-any')) {
            $stats['cargos'] = [
                'label' => 'Cargos',
                'value' => \App\Models\Cargo::count(),
                'icon' => 'briefcase',
                'color' => 'green',
                'route' => 'cargos.index',
                'hint' => 'funções cadastradas',
            ];
        }

        // Departamentos
        if ($user->can('departamentos.view-any')) {
            $stats['departamentos'] = [
                'label' => 'Departamentos',
                'value' => \App\Models\Departamento::count(),
                'icon' => 'building',
                'color' => 'yellow',
                'route' => 'departamentos.index',
                'hint' => 'na estrutura',
            ];
        }

        // Atestados pendentes (chama atenção!)
        if ($user->can('atestados.view-any')) {
            $pendentes = \App\Models\Atestado::pendentes()->count();
            $stats['atestados_pendentes'] = [
                'label' => 'Atestados pendentes',
                'value' => $pendentes,
                'icon' => 'clock-hour-4',
                'color' => $pendentes > 0 ? 'red' : 'green',
                'route' => 'atestados.index',
                'hint' => $pendentes > 0 ? 'aguardando análise' : 'tudo em dia',
            ];
        }

        // Advertências do mês
        if ($user->can('advertencias.view-any')) {
            $stats['advertencias_mes'] = [
                'label' => 'Advertências no mês',
                'value' => \App\Models\Advertencia::whereMonth('data_aplicacao', now()->month)
                    ->whereYear('data_aplicacao', now()->year)
                    ->count(),
                'icon' => 'alert-octagon',
                'color' => 'yellow',
                'route' => 'advertencias.index',
                'hint' => 'aplicadas em ' . now()->translatedFormat('F'),
            ];
        }

        // Férias em gozo hoje
        if ($user->can('ferias.view-any')) {
            $emGozo = \App\Models\Ferias::emGozo()->count();
            $stats['ferias_em_gozo'] = [
                'label' => 'Em férias agora',
                'value' => $emGozo,
                'icon' => 'beach',
                'color' => 'blue',
                'route' => 'ferias.index',
                'hint' => $emGozo > 0 ? 'colaboradores em gozo' : 'nenhum em gozo',
            ];
        }

        // Pedidos de compra pendentes de liberação/aprovação
        if ($user->can('pedidos-compra.view-any')) {
            $pendentes = \App\Models\PedidoCompra::pendentesAprovacao()->count();
            $stats['pedidos_pendentes'] = [
                'label' => 'Pedidos pendentes',
                'value' => $pendentes,
                'icon' => 'shopping-cart',
                'color' => $pendentes > 0 ? 'yellow' : 'green',
                'route' => 'pedidos-compra.index',
                'hint' => $pendentes > 0 ? 'aguardando decisão' : 'nada pendente',
            ];
        }

        // Materiais abaixo do estoque mínimo
        if ($user->can('materiais.view-any')) {
            $estoqueBaixo = \App\Models\Material::abaixoDoMinimo()->count();
            if ($estoqueBaixo > 0) {
                $stats['estoque_baixo'] = [
                    'label' => 'Estoque baixo',
                    'value' => $estoqueBaixo,
                    'icon' => 'alert-triangle',
                    'color' => 'red',
                    'route' => 'materiais.index',
                    'hint' => 'materiais abaixo do mínimo',
                ];
            }
        }

        // Obras atrasadas
        if ($user->can('obras.view-any')) {
            $obrasAtrasadas = \App\Models\Obra::ativas()
                ->whereNotNull('data_termino_previsto')
                ->whereDate('data_termino_previsto', '<', now())
                ->count();
            if ($obrasAtrasadas > 0) {
                $stats['obras_atrasadas'] = [
                    'label' => 'Obras atrasadas',
                    'value' => $obrasAtrasadas,
                    'icon' => 'building-skyscraper',
                    'color' => 'red',
                    'route' => 'obras.index',
                    'hint' => 'passou do término previsto',
                ];
            }
        }

        // Veículos com documentação vencendo
        if ($user->can('veiculos.view-any')) {
            $licProx = \App\Models\Veiculo::licenciamentoProximo()->count();
            $segProx = \App\Models\Veiculo::seguroProximo()->count();
            if ($licProx > 0 || $segProx > 0) {
                $stats['veiculos_documentacao'] = [
                    'label' => 'Documentação vencendo',
                    'value' => $licProx + $segProx,
                    'icon' => 'license',
                    'color' => 'orange',
                    'route' => 'veiculos.index',
                    'hint' => "{$licProx} licenciamento(s), {$segProx} seguro(s) em até 30 dias",
                ];
            }
        }

        // Usuários do sistema
        if ($user->can('users.view-any')) {
            $stats['usuarios'] = [
                'label' => 'Usuários do sistema',
                'value' => User::where('active', true)->count(),
                'icon' => 'user-cog',
                'color' => 'blue',
                'route' => 'users.index',
                'hint' => 'contas ativas',
            ];
        }

        // Perfis
        if ($user->can('roles.view-any')) {
            $stats['perfis'] = [
                'label' => 'Perfis configurados',
                'value' => \Spatie\Permission\Models\Role::count(),
                'icon' => 'shield-lock',
                'color' => 'purple',
                'route' => 'roles.index',
                'hint' => 'incluindo o admin',
            ];
        }

        return $stats;
    }

    private function saudacao(): string
    {
        $hora = (int) now()->format('H');
        return match (true) {
            $hora < 12 => 'Bom dia',
            $hora < 18 => 'Boa tarde',
            default => 'Boa noite',
        };
    }
}
