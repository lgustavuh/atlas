<div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full">

    <div class="sm:flex sm:items-center sm:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Obras</h1>
            <p class="mt-1 text-sm text-gray-600">Projetos e obras da prefeitura.</p>
        </div>
        @can('create', App\Models\Obra::class)
            <div class="mt-4 sm:mt-0">
                <x-button wire:click="openCreate" icon="plus">Nova obra</x-button>
            </div>
        @endcan
    </div>

    {{-- Cards de resumo --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-md bg-blue-50 flex items-center justify-center">
                    <i class="ti ti-building-skyscraper text-blue-600 text-xl" aria-hidden="true"></i>
                </div>
                <div class="ml-3">
                    <p class="text-xs text-gray-500">Em andamento</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['em_andamento'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-md bg-gray-100 flex items-center justify-center">
                    <i class="ti ti-blueprint text-gray-600 text-xl" aria-hidden="true"></i>
                </div>
                <div class="ml-3">
                    <p class="text-xs text-gray-500">Em planejamento</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['planejamento'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-md bg-red-50 flex items-center justify-center">
                    <i class="ti ti-alert-triangle text-red-600 text-xl" aria-hidden="true"></i>
                </div>
                <div class="ml-3">
                    <p class="text-xs text-gray-500">Atrasadas</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['atrasadas'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <x-card class="mb-4" padding="p-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-input label="Buscar" wire:model.live.debounce.400ms="search" placeholder="Nome, código ou endereço..." />
            <x-select label="Status" wire:model.live="filterStatus">
                <option value="">Todos</option>
                @foreach ($statuses as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </x-select>
        </div>
    </x-card>

    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Obra</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Local</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Responsável</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cronograma</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Orçamento</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($obras as $obra)
                        <tr wire:key="obra-{{ $obra->id }}" class="hover:bg-gray-50 {{ $obra->atrasada ? 'bg-red-50/30' : '' }}">
                            <td class="px-6 py-3">
                                <div class="text-sm font-medium text-gray-900">{{ $obra->nome }}</div>
                                @if ($obra->codigo)
                                    <div class="text-xs text-gray-500 font-mono">{{ $obra->codigo }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-600">
                                @if ($obra->cidade)
                                    {{ $obra->cidade->nome }}
                                @endif
                                @if ($obra->endereco)
                                    <div class="text-xs text-gray-400 truncate max-w-[180px]" title="{{ $obra->endereco }}">{{ $obra->endereco }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">
                                {{ $obra->responsavel?->nome ?? '—' }}
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-xs text-gray-600">
                                @if ($obra->data_inicio)
                                    <div>
                                        <i class="ti ti-calendar-event text-gray-400 mr-1" aria-hidden="true"></i>
                                        {{ $obra->data_inicio->format('d/m/Y') }}
                                    </div>
                                @endif
                                @if ($obra->data_termino_previsto)
                                    <div class="{{ $obra->atrasada ? 'text-red-600 font-medium' : 'text-gray-500' }}">
                                        <i class="ti ti-flag-check text-xs mr-1" aria-hidden="true"></i>
                                        {{ $obra->data_termino_previsto->format('d/m/Y') }}
                                        @if ($obra->atrasada)
                                            <span class="ml-1">(atrasada)</span>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-right text-sm text-gray-900">
                                @if ($obra->orcamento !== null)
                                    R$ {{ number_format((float) $obra->orcamento, 2, ',', '.') }}
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                <x-badge :variant="$obra->status_cor">{{ $obra->status_label }}</x-badge>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                @if (!in_array($obra->status, ['concluida', 'cancelada']))
                                    @can('update', $obra)
                                        <button wire:click="concluir({{ $obra->id }})"
                                                wire:confirm="Marcar esta obra como concluída?"
                                                class="text-green-600 hover:text-green-900" title="Concluir">
                                            <i class="ti ti-flag-check" aria-hidden="true"></i>
                                        </button>
                                    @endcan
                                @endif
                                @can('update', $obra)
                                    <button wire:click="openEdit({{ $obra->id }})" class="text-indigo-600 hover:text-indigo-900" title="Editar">
                                        <i class="ti ti-edit" aria-hidden="true"></i>
                                    </button>
                                @endcan
                                @can('delete', $obra)
                                    <button wire:click="confirmDelete({{ $obra->id }})" class="text-red-600 hover:text-red-900" title="Excluir">
                                        <i class="ti ti-trash" aria-hidden="true"></i>
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <i class="ti ti-building-off text-4xl text-gray-300 mb-2 block" aria-hidden="true"></i>
                                <p class="text-sm text-gray-500">Nenhuma obra cadastrada.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($obras->hasPages())
            <div class="px-6 py-3 border-t border-gray-200">{{ $obras->links() }}</div>
        @endif
    </x-card>

    {{-- Modal --}}
    <x-modal name="showModal" max-width="3xl" :title="$editando ? 'Editar obra' : 'Nova obra'">
        <form wire:submit.prevent="save">
            <div class="px-6 py-4 space-y-5 max-h-[70vh] overflow-y-auto">

                <div>
                    <h3 class="text-sm font-medium text-gray-900 mb-3">Identificação</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-2">
                            <x-input label="Nome da obra" name="nome" wire:model="nome" required />
                        </div>
                        <x-input label="Código" name="codigo" wire:model="codigo" hint="Opcional, único" />
                    </div>
                    <div class="mt-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                        <textarea wire:model="descricao" rows="2"
                                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                  placeholder="Escopo, detalhes, objetivos..."></textarea>
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-gray-900 mb-3">Localização</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <x-input label="Endereço" name="endereco" wire:model="endereco" />
                        </div>
                        <x-select label="Estado" wire:model.live="estadoFiltro">
                            <option value="">Selecione</option>
                            @foreach ($estados as $e)
                                <option value="{{ $e->id }}">{{ $e->nome }} ({{ $e->uf }})</option>
                            @endforeach
                        </x-select>
                        <x-select label="Cidade" name="cidade_id" wire:model="cidade_id" :disabled="!$estadoFiltro">
                            <option value="">Selecione</option>
                            @foreach ($cidadesDisponiveis as $c)
                                <option value="{{ $c->id }}">{{ $c->nome }}</option>
                            @endforeach
                        </x-select>
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-gray-900 mb-3">Cronograma</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <x-input label="Data de início" name="data_inicio" type="date" wire:model="data_inicio" />
                        <x-input label="Término previsto" name="data_termino_previsto" type="date" wire:model="data_termino_previsto" />
                        <x-input label="Término real" name="data_termino_real" type="date" wire:model="data_termino_real" hint="Preenchido ao concluir" />
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-gray-900 mb-3">Gestão</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <x-select label="Responsável" name="responsavel_id" wire:model="responsavel_id">
                            <option value="">Sem responsável</option>
                            @foreach ($colaboradores as $col)
                                <option value="{{ $col->id }}">{{ $col->nome }}</option>
                            @endforeach
                        </x-select>
                        <x-input label="Orçamento (R$)" name="orcamento" type="number" step="0.01" min="0" wire:model="orcamento" />
                        <x-select label="Status" name="status" wire:model="status" required>
                            @foreach ($statuses as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </x-select>
                    </div>
                </div>
            </div>
            <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 flex justify-end space-x-2">
                <x-button variant="secondary" type="button" wire:click="closeModal">Cancelar</x-button>
                <x-button type="submit" loading="save">Salvar</x-button>
            </div>
        </form>
    </x-modal>

    <x-modal name="showDeleteModal" max-width="md" title="Confirmar exclusão">
        <div class="px-6 py-4">
            <p class="text-sm text-gray-700">Excluir a obra <strong>{{ $deletingName }}</strong>?</p>
        </div>
        <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 flex justify-end space-x-2">
            <x-button variant="secondary" wire:click="$set('showDeleteModal', false)">Cancelar</x-button>
            <x-button variant="danger" wire:click="delete">Sim, excluir</x-button>
        </div>
    </x-modal>
</div>
