<div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full">

    {{-- Header --}}
    <div class="sm:flex sm:items-center sm:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Colaboradores</h1>
            <p class="mt-1 text-sm text-gray-600">Gerencie o cadastro de funcionários da empresa.</p>
        </div>

        <div class="mt-4 sm:mt-0 flex space-x-2">
            @can('viewAny', App\Models\Colaborador::class)
                <x-button variant="secondary" icon="file-spreadsheet" wire:click="exportar">
                    Exportar
                </x-button>
            @endcan
            @can('create', App\Models\Colaborador::class)
                <x-button icon="user-plus" href="{{ route('colaboradores.create') }}">
                    <a href="{{ route('colaboradores.create') }}" wire:navigate.hover>Novo colaborador</a>
                </x-button>
            @endcan
        </div>
    </div>

    {{-- Filtros --}}
    <x-card class="mb-4" padding="p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-2">
                <x-input
                    label="Buscar"
                    wire:model.live.debounce.400ms="search"
                    placeholder="Nome, CPF, matrícula ou email..."
                />
            </div>

            <x-select label="Cargo" wire:model.live="filterCargo">
                <option value="">Todos</option>
                @foreach ($cargos as $cargo)
                    <option value="{{ $cargo->id }}">{{ $cargo->nome }}</option>
                @endforeach
            </x-select>

            <x-select label="Departamento" wire:model.live="filterDepartamento">
                <option value="">Todos</option>
                @foreach ($departamentos as $dep)
                    <option value="{{ $dep->id }}">{{ $dep->nome }}</option>
                @endforeach
            </x-select>
        </div>

        <div class="mt-4 flex items-center justify-between flex-wrap gap-2">
            <div class="flex space-x-2">
                <button
                    wire:click="$set('filterStatus', 'ativos')"
                    class="px-3 py-1 text-xs rounded-full border {{ $filterStatus === 'ativos' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}"
                >
                    Ativos
                </button>
                <button
                    wire:click="$set('filterStatus', 'inativos')"
                    class="px-3 py-1 text-xs rounded-full border {{ $filterStatus === 'inativos' ? 'bg-gray-600 text-white border-gray-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}"
                >
                    Inativos
                </button>
                <button
                    wire:click="$set('filterStatus', 'todos')"
                    class="px-3 py-1 text-xs rounded-full border {{ $filterStatus === 'todos' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}"
                >
                    Todos
                </button>
            </div>

            <button wire:click="limparFiltros" class="text-xs text-indigo-600 hover:text-indigo-700">
                <i class="ti ti-x" aria-hidden="true"></i> Limpar filtros
            </button>
        </div>
    </x-card>

    {{-- Tabela --}}
    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Colaborador</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CPF</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cargo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Departamento</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Admissão</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($colaboradores as $colaborador)
                        <tr wire:key="col-{{ $colaborador->id }}" class="hover:bg-gray-50">
                            <td class="px-6 py-3 whitespace-nowrap">
                                <div class="flex items-center">
                                    @if ($colaborador->foto_path)
                                        <img src="{{ $colaborador->foto_url }}" alt="" class="w-9 h-9 rounded-full object-cover">
                                    @else
                                        <div class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-medium text-xs">
                                            {{ $colaborador->iniciais }}
                                        </div>
                                    @endif
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-900">{{ $colaborador->nome }}</p>
                                        @if ($colaborador->matricula)
                                            <p class="text-xs text-gray-500">Mat. {{ $colaborador->matricula }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600 font-mono">
                                {{ $colaborador->cpf_formatado }}
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">
                                {{ $colaborador->cargo?->nome ?? '—' }}
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">
                                {{ $colaborador->departamento?->nome ?? '—' }}
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">
                                {{ $colaborador->data_admissao?->format('d/m/Y') ?? '—' }}
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                @if ($colaborador->trashed())
                                    <x-badge variant="gray">Desativado</x-badge>
                                @elseif ($colaborador->data_demissao)
                                    <x-badge variant="red">Demitido</x-badge>
                                @else
                                    <x-badge variant="green" icon="circle-check">Ativo</x-badge>
                                @endif
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-medium space-x-3">
                                <a href="{{ route('colaboradores.show', $colaborador->id) }}" wire:navigate.hover
                                   class="text-gray-500 hover:text-gray-700" title="Ver detalhes">
                                    <i class="ti ti-eye" aria-hidden="true"></i>
                                </a>
                                @can('update', $colaborador)
                                    @if (!$colaborador->trashed())
                                        <a href="{{ route('colaboradores.edit', $colaborador->id) }}" wire:navigate.hover
                                           class="text-indigo-600 hover:text-indigo-900" title="Editar">
                                            <i class="ti ti-edit" aria-hidden="true"></i>
                                        </a>
                                    @endif
                                @endcan
                                @if ($colaborador->trashed())
                                    @can('restore', $colaborador)
                                        <button wire:click="confirmarReativar({{ $colaborador->id }})"
                                                class="text-green-600 hover:text-green-900" title="Reativar">
                                            <i class="ti ti-restore" aria-hidden="true"></i>
                                        </button>
                                    @endcan
                                @else
                                    @can('delete', $colaborador)
                                        <button wire:click="confirmarDesativar({{ $colaborador->id }})"
                                                class="text-red-600 hover:text-red-900" title="Desativar">
                                            <i class="ti ti-user-off" aria-hidden="true"></i>
                                        </button>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <i class="ti ti-users-off text-4xl text-gray-300 mb-2 block" aria-hidden="true"></i>
                                <p class="text-sm text-gray-500">
                                    @if ($search !== '' || $filterCargo !== '' || $filterDepartamento !== '')
                                        Nenhum colaborador encontrado com esses filtros.
                                    @else
                                        Nenhum colaborador cadastrado ainda.
                                    @endif
                                </p>
                                @can('create', App\Models\Colaborador::class)
                                    <a href="{{ route('colaboradores.create') }}" wire:navigate.hover
                                       class="mt-3 inline-flex items-center text-sm text-indigo-600 hover:text-indigo-700">
                                        <i class="ti ti-plus mr-1" aria-hidden="true"></i>
                                        Cadastrar o primeiro
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($colaboradores->hasPages())
            <div class="px-6 py-3 border-t border-gray-200">
                {{ $colaboradores->links() }}
            </div>
        @endif
    </x-card>

    {{-- Modal de confirmação --}}
    <x-modal name="showActionModal" max-width="md" :title="$actionType === 'desativar' ? 'Confirmar desativação' : 'Confirmar reativação'">
        <div class="px-6 py-4">
            <div class="flex items-start">
                <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center {{ $actionType === 'desativar' ? 'bg-red-100' : 'bg-green-100' }}">
                    <i class="ti ti-{{ $actionType === 'desativar' ? 'alert-triangle text-red-600' : 'restore text-green-600' }} text-xl" aria-hidden="true"></i>
                </div>
                <div class="ml-4">
                    @if ($actionType === 'desativar')
                        <p class="text-sm text-gray-700">
                            Deseja desativar o colaborador <strong>{{ $actionColaboradorNome }}</strong>?
                        </p>
                        <p class="mt-1 text-xs text-gray-500">
                            O colaborador será marcado como inativo, mas os dados serão preservados. Poderá ser reativado depois.
                        </p>
                    @else
                        <p class="text-sm text-gray-700">
                            Deseja reativar o colaborador <strong>{{ $actionColaboradorNome }}</strong>?
                        </p>
                        <p class="mt-1 text-xs text-gray-500">
                            O colaborador voltará a aparecer nas listagens normais.
                        </p>
                    @endif
                </div>
            </div>
        </div>
        <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 flex justify-end space-x-2">
            <x-button variant="secondary" wire:click="$set('showActionModal', false)">Cancelar</x-button>
            <x-button :variant="$actionType === 'desativar' ? 'danger' : 'success'" wire:click="executar">
                {{ $actionType === 'desativar' ? 'Sim, desativar' : 'Sim, reativar' }}
            </x-button>
        </div>
    </x-modal>
</div>
