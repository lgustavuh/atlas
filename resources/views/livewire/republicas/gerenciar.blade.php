<div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full">

    <div class="sm:flex sm:items-center sm:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Repúblicas</h1>
            <p class="mt-1 text-sm text-gray-600">Imóveis alugados para hospedagem de servidores deslocados.</p>
        </div>
        @can('create', App\Models\Republica::class)
            <div class="mt-4 sm:mt-0">
                <x-button wire:click="openCreate" icon="plus">Nova república</x-button>
            </div>
        @endcan
    </div>

    <div class="grid grid-cols-3 gap-4 mb-4">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-md bg-indigo-50 flex items-center justify-center">
                    <i class="ti ti-home text-indigo-600 text-xl" aria-hidden="true"></i>
                </div>
                <div class="ml-3">
                    <p class="text-xs text-gray-500">Repúblicas ativas</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['total_ativas'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-md bg-green-50 flex items-center justify-center">
                    <i class="ti ti-users text-green-600 text-xl" aria-hidden="true"></i>
                </div>
                <div class="ml-3">
                    <p class="text-xs text-gray-500">Ocupantes atuais</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['ocupantes'] }}<span class="text-sm text-gray-400 ml-1">/ {{ $stats['capacidade_total'] }}</span></p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-md bg-yellow-50 flex items-center justify-center">
                    <i class="ti ti-bed-flat text-yellow-600 text-xl" aria-hidden="true"></i>
                </div>
                <div class="ml-3">
                    <p class="text-xs text-gray-500">Vagas livres</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ max(0, $stats['capacidade_total'] - $stats['ocupantes']) }}</p>
                </div>
            </div>
        </div>
    </div>

    <x-card class="mb-4" padding="p-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-input label="Buscar" wire:model.live.debounce.400ms="search" placeholder="Nome ou endereço..." />
            <x-select label="Status" wire:model.live="filterStatus">
                <option value="">Todas</option>
                <option value="ativas">Ativas</option>
                <option value="inativas">Inativas</option>
                <option value="lotadas">Lotadas</option>
            </x-select>
        </div>
    </x-card>

    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">República</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Endereço</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Ocupação</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aluguel</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($republicas as $r)
                        <tr wire:key="rep-{{ $r->id }}" class="hover:bg-gray-50">
                            <td class="px-6 py-3">
                                <div class="text-sm font-medium text-gray-900">{{ $r->nome }}</div>
                                @if ($r->responsavel_externo_nome)
                                    <div class="text-xs text-gray-500">
                                        Locador: {{ $r->responsavel_externo_nome }}
                                        @if ($r->responsavel_externo_telefone)
                                            · {{ $r->responsavel_externo_telefone }}
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-600">
                                <div>{{ $r->endereco }}</div>
                                @if ($r->cidade)
                                    <div class="text-xs text-gray-400">{{ $r->cidade->nome }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-center">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $r->ocupacoes_atuais_count }} / {{ $r->capacidade_total }}
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-1.5 mt-1">
                                    <div @class([
                                        'h-1.5 rounded-full',
                                        'bg-red-500' => $r->percentual_ocupacao === 100,
                                        'bg-yellow-500' => $r->percentual_ocupacao >= 75 && $r->percentual_ocupacao < 100,
                                        'bg-green-500' => $r->percentual_ocupacao < 75,
                                    ]) style="width: {{ $r->percentual_ocupacao }}%"></div>
                                </div>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-right text-sm text-gray-900">
                                @if ($r->aluguel_mensal !== null)
                                    R$ {{ number_format((float) $r->aluguel_mensal, 2, ',', '.') }}
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                @if ($r->ativa)
                                    <x-badge variant="green">Ativa</x-badge>
                                @else
                                    <x-badge variant="gray">Inativa</x-badge>
                                @endif
                                @if ($r->lotada)
                                    <x-badge variant="red">Lotada</x-badge>
                                @endif
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                <a href="{{ route('republicas.ocupacoes', $r->id) }}" wire:navigate.hover
                                   class="text-indigo-600 hover:text-indigo-900" title="Gerenciar ocupação">
                                    <i class="ti ti-users-group" aria-hidden="true"></i>
                                </a>
                                @can('update', $r)
                                    <button wire:click="toggleAtiva({{ $r->id }})"
                                            class="{{ $r->ativa ? 'text-yellow-600 hover:text-yellow-900' : 'text-green-600 hover:text-green-900' }}"
                                            title="{{ $r->ativa ? 'Desativar' : 'Ativar' }}">
                                        <i class="ti ti-{{ $r->ativa ? 'player-pause' : 'player-play' }}" aria-hidden="true"></i>
                                    </button>
                                    <button wire:click="openEdit({{ $r->id }})" class="text-indigo-600 hover:text-indigo-900">
                                        <i class="ti ti-edit" aria-hidden="true"></i>
                                    </button>
                                @endcan
                                @can('delete', $r)
                                    <button wire:click="confirmDelete({{ $r->id }})" class="text-red-600 hover:text-red-900">
                                        <i class="ti ti-trash" aria-hidden="true"></i>
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <i class="ti ti-home-off text-4xl text-gray-300 mb-2 block" aria-hidden="true"></i>
                                <p class="text-sm text-gray-500">Nenhuma república cadastrada.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($republicas->hasPages())
            <div class="px-6 py-3 border-t border-gray-200">{{ $republicas->links() }}</div>
        @endif
    </x-card>

    <x-modal name="showModal" max-width="3xl" :title="$editando ? 'Editar república' : 'Nova república'">
        <form wire:submit.prevent="save">
            <div class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
                <x-input label="Nome" name="nome" wire:model="nome" required />
                <x-input label="Endereço" name="endereco" wire:model="endereco" required />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-input label="Capacidade total (vagas)" name="capacidade_total" type="number" min="1" max="100" wire:model="capacidade_total" required />
                    <x-input label="Aluguel mensal (R$)" name="aluguel_mensal" type="number" step="0.01" min="0" wire:model="aluguel_mensal" />
                </div>

                <div class="bg-gray-50 border border-gray-200 rounded-md p-3">
                    <p class="text-xs font-medium text-gray-700 mb-2">Locador / Proprietário</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <x-input label="Nome" name="responsavel_externo_nome" wire:model="responsavel_externo_nome" />
                        <x-input label="Telefone" name="responsavel_externo_telefone" wire:model="responsavel_externo_telefone" />
                    </div>
                </div>

                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" wire:model="ativa" class="h-4 w-4 rounded text-indigo-600 focus:ring-indigo-500">
                    <span class="ml-2 text-sm text-gray-900">República ativa</span>
                </label>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                    <textarea wire:model="observacoes" rows="2"
                              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
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
            <p class="text-sm text-gray-700">Excluir a república <strong>{{ $deletingName }}</strong>?</p>
            <p class="text-xs text-gray-500 mt-2">Não pode ser excluída se tiver ocupantes atuais.</p>
        </div>
        <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 flex justify-end space-x-2">
            <x-button variant="secondary" wire:click="$set('showDeleteModal', false)">Cancelar</x-button>
            <x-button variant="danger" wire:click="delete">Sim, excluir</x-button>
        </div>
    </x-modal>
</div>
