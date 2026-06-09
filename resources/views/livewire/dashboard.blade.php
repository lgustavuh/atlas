<div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full">

    {{-- Saudação --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">
            {{ $saudacao }}, {{ auth()->user()->first_name }}!
        </h1>
        @if ($ehAdmin)
            <p class="mt-1 text-sm text-gray-600">
                Aqui está um resumo do que está acontecendo no sistema.
            </p>
        @else
            <p class="mt-1 text-sm text-gray-600">
                Bem-vindo ao Sistema Atlas. Use o menu lateral para acessar os módulos disponíveis para você.
            </p>
        @endif
    </div>

    {{-- Cards de estatísticas (só pra admin) --}}
    @if ($ehAdmin && count($stats) > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 mb-8">
            @foreach ($stats as $key => $stat)
                @php
                    $colorClasses = [
                        'indigo' => ['bg' => 'bg-indigo-500', 'text' => 'text-indigo-600', 'light' => 'bg-indigo-50'],
                        'blue' => ['bg' => 'bg-blue-500', 'text' => 'text-blue-600', 'light' => 'bg-blue-50'],
                        'purple' => ['bg' => 'bg-purple-500', 'text' => 'text-purple-600', 'light' => 'bg-purple-50'],
                        'green' => ['bg' => 'bg-green-500', 'text' => 'text-green-600', 'light' => 'bg-green-50'],
                        'red' => ['bg' => 'bg-red-500', 'text' => 'text-red-600', 'light' => 'bg-red-50'],
                        'yellow' => ['bg' => 'bg-yellow-500', 'text' => 'text-yellow-600', 'light' => 'bg-yellow-50'],
                    ];
                    $c = $colorClasses[$stat['color']] ?? $colorClasses['indigo'];
                @endphp

                <div wire:key="stat-{{ $key }}" class="bg-white overflow-hidden rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-shadow">
                    @if ($stat['route'] && Route::has($stat['route']))
                        <a href="{{ route($stat['route']) }}" wire:navigate.hover class="block p-5">
                    @else
                        <div class="p-5">
                    @endif

                        <div class="flex items-center">
                            <div class="flex-shrink-0 w-12 h-12 rounded-md {{ $c['light'] }} flex items-center justify-center">
                                <i class="ti ti-{{ $stat['icon'] }} {{ $c['text'] }} text-2xl" aria-hidden="true"></i>
                            </div>
                            <div class="ml-4 min-w-0 flex-1">
                                <p class="text-sm font-medium text-gray-500 truncate">{{ $stat['label'] }}</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ number_format((int) $stat['value'], 0, ',', '.') }}</p>
                                @if (isset($stat['hint']))
                                    <p class="text-xs text-gray-500">{{ $stat['hint'] }}</p>
                                @endif
                            </div>
                        </div>

                    @if ($stat['route'] && Route::has($stat['route']))
                        </a>
                    @else
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- Status do projeto (só admin) --}}
    @if ($ehAdmin)
    <x-card title="Status do projeto" icon="info-circle">
        <div class="space-y-2 text-sm">
            <div class="flex items-center text-green-700">
                <i class="ti ti-circle-check mr-2" aria-hidden="true"></i>
                Fase 0 — Estrutura do projeto
            </div>
            <div class="flex items-center text-green-700">
                <i class="ti ti-circle-check mr-2" aria-hidden="true"></i>
                Fase 1 — Schema do banco
            </div>
            <div class="flex items-center text-green-700">
                <i class="ti ti-circle-check mr-2" aria-hidden="true"></i>
                Fase 2 — Autenticação e autorização
            </div>
            <div class="flex items-center text-green-700">
                <i class="ti ti-circle-check mr-2" aria-hidden="true"></i>
                Fase 4 — Módulo Colaboradores
            </div>
            <div class="flex items-center text-green-700">
                <i class="ti ti-circle-check mr-2" aria-hidden="true"></i>
                Fase 5 — Cargos, Departamentos e Geografia
            </div>
            <div class="flex items-center text-green-700">
                <i class="ti ti-circle-check mr-2" aria-hidden="true"></i>
                Fase 6 — Advertências e Atestados
            </div>
            <div class="flex items-center text-green-700">
                <i class="ti ti-circle-check mr-2" aria-hidden="true"></i>
                Fase 7 — Férias + roadmap dos demais módulos
            </div>
            <div class="flex items-center text-green-700">
                <i class="ti ti-circle-check mr-2" aria-hidden="true"></i>
                Fase 8 — Compras (Fornecedores, Materiais, Pedidos)
            </div>
            <div class="flex items-center text-green-700">
                <i class="ti ti-circle-check mr-2" aria-hidden="true"></i>
                Fase 10 — Veículos e Manutenções
            </div>
            <div class="flex items-center text-green-700">
                <i class="ti ti-circle-check mr-2" aria-hidden="true"></i>
                Fase 11 — Obras
            </div>
            <div class="flex items-center text-green-700">
                <i class="ti ti-circle-check mr-2" aria-hidden="true"></i>
                Fase 12 — Biblioteca
            </div>
            <div class="flex items-center text-green-700">
                <i class="ti ti-circle-check mr-2" aria-hidden="true"></i>
                Fase 13 — Alertas Administrativos
            </div>
            <div class="flex items-center text-green-700">
                <i class="ti ti-circle-check mr-2" aria-hidden="true"></i>
                Fase 14 — Recrutamento (Vagas + Candidatos)
            </div>
            <div class="flex items-center text-green-700">
                <i class="ti ti-circle-check mr-2" aria-hidden="true"></i>
                Fase 15 — Transporte e Hospedagem
            </div>
            <div class="flex items-center text-green-700">
                <i class="ti ti-circle-check mr-2" aria-hidden="true"></i>
                Fase 16 — Repúblicas (todos os módulos de domínio prontos)
            </div>
            <div class="flex items-center text-green-700">
                <i class="ti ti-circle-check mr-2" aria-hidden="true"></i>
                Fase 17 — Auditoria
            </div>
            <div class="flex items-center text-green-700">
                <i class="ti ti-circle-check mr-2" aria-hidden="true"></i>
                Fase 18 — Exportação Excel
            </div>
            <div class="flex items-center text-green-700">
                <i class="ti ti-circle-check mr-2" aria-hidden="true"></i>
                Fase 19 — Geração de PDFs
            </div>
            <div class="flex items-center text-green-700">
                <i class="ti ti-circle-check mr-2" aria-hidden="true"></i>
                Fase 20 — Notificações por e-mail <span class="ml-2 text-xs text-gray-500">(PROJETO COMPLETO)</span>
            </div>
        </div>
    </x-card>
    @endif

    {{-- Atalhos rápidos baseados em permissões --}}
    <div class="mt-8">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Acesso rápido</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @can('users.view-any')
                <a href="{{ route('users.index') }}" wire:navigate.hover
                   class="flex items-center p-4 bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md hover:border-indigo-300 transition">
                    <i class="ti ti-user-cog text-indigo-600 text-2xl mr-3" aria-hidden="true"></i>
                    <div>
                        <p class="font-medium text-gray-900">Gerenciar usuários</p>
                        <p class="text-xs text-gray-500">Criar, editar, desativar contas</p>
                    </div>
                </a>
            @endcan

            @can('roles.view-any')
                <a href="{{ route('roles.index') }}" wire:navigate.hover
                   class="flex items-center p-4 bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md hover:border-indigo-300 transition">
                    <i class="ti ti-shield-lock text-purple-600 text-2xl mr-3" aria-hidden="true"></i>
                    <div>
                        <p class="font-medium text-gray-900">Perfis e permissões</p>
                        <p class="text-xs text-gray-500">Configurar acessos por perfil</p>
                    </div>
                </a>
            @endcan

            <a href="{{ route('profile.edit') }}" wire:navigate.hover
               class="flex items-center p-4 bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md hover:border-indigo-300 transition">
                <i class="ti ti-user text-blue-600 text-2xl mr-3" aria-hidden="true"></i>
                <div>
                    <p class="font-medium text-gray-900">Meu Perfil</p>
                    <p class="text-xs text-gray-500">Ver e editar dados pessoais</p>
                </div>
            </a>

            <a href="{{ route('password.change') }}" wire:navigate.hover
               class="flex items-center p-4 bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md hover:border-indigo-300 transition">
                <i class="ti ti-key text-amber-600 text-2xl mr-3" aria-hidden="true"></i>
                <div>
                    <p class="font-medium text-gray-900">Trocar senha</p>
                    <p class="text-xs text-gray-500">Atualizar sua senha de acesso</p>
                </div>
            </a>
        </div>
    </div>
</div>
