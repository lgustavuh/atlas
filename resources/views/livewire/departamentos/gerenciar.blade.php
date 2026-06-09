<div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full">

    <div class="sm:flex sm:items-center sm:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Departamentos</h1>
            <p class="mt-1 text-sm text-gray-600">Estrutura organizacional da empresa.</p>
        </div>
        @can('create', App\Models\Departamento::class)
            <div class="mt-4 sm:mt-0">
                <x-button wire:click="openCreate" icon="plus">Novo departamento</x-button>
            </div>
        @endcan
    </div>

    <x-card class="mb-4" padding="p-4">
        <x-input label="Buscar" wire:model.live.debounce.400ms="search" placeholder="Nome ou sigla..." />
    </x-card>

    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Departamento</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sigla</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Departamento Pai</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Colaboradores</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Subdeptos</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($departamentos as $dep)
                        <tr wire:key="dep-{{ $dep->id }}" class="hover:bg-gray-50">
                            <td class="px-6 py-3 whitespace-nowrap text-sm font-medium text-gray-900">{{ $dep->nome }}</td>
                            <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600 font-mono">{{ $dep->sigla ?? '—' }}</td>
                            <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">
                                @if ($dep->departamentoPai)
                                    <x-badge variant="blue">{{ $dep->departamentoPai->nome }}</x-badge>
                                @else
                                    <span class="text-gray-400 italic">raiz</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-center">
                                <x-badge variant="{{ $dep->colaboradores_count > 0 ? 'indigo' : 'gray' }}">
                                    {{ $dep->colaboradores_count }}
                                </x-badge>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-center">
                                <x-badge variant="{{ $dep->sub_departamentos_count > 0 ? 'purple' : 'gray' }}">
                                    {{ $dep->sub_departamentos_count }}
                                </x-badge>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-medium space-x-3">
                                @can('update', $dep)
                                    <button wire:click="openEdit({{ $dep->id }})" class="text-indigo-600 hover:text-indigo-900">
                                        <i class="ti ti-edit" aria-hidden="true"></i>
                                    </button>
                                @endcan
                                @can('delete', $dep)
                                    <button wire:click="confirmDelete({{ $dep->id }})" class="text-red-600 hover:text-red-900">
                                        <i class="ti ti-trash" aria-hidden="true"></i>
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <i class="ti ti-building-off text-4xl text-gray-300 mb-2 block" aria-hidden="true"></i>
                                <p class="text-sm text-gray-500">Nenhum departamento cadastrado.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($departamentos->hasPages())
            <div class="px-6 py-3 border-t border-gray-200">{{ $departamentos->links() }}</div>
        @endif
    </x-card>

    <x-modal name="showModal" max-width="2xl" :title="$editando ? 'Editar departamento' : 'Novo departamento'">
        <form wire:submit.prevent="save">
            <div class="px-6 py-4 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <x-input label="Nome" name="nome" wire:model="nome" required />
                    </div>
                    <x-input label="Sigla" name="sigla" wire:model="sigla" placeholder="Ex: TI, RH" />
                </div>
                <x-select label="Departamento pai" name="departamento_pai_id" wire:model="departamento_pai_id"
                          hint="Deixe vazio para departamento raiz">
                    <option value="">Nenhum (raiz)</option>
                    @foreach ($departamentosDisponiveis as $d)
                        <option value="{{ $d->id }}">{{ $d->nome }}</option>
                    @endforeach
                </x-select>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                    <textarea wire:model="descricao" rows="3" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
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
            <div class="flex items-start">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                    <i class="ti ti-alert-triangle text-red-600 text-xl" aria-hidden="true"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-700">Excluir o departamento <strong>{{ $deletingName }}</strong>?</p>
                </div>
            </div>
        </div>
        <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 flex justify-end space-x-2">
            <x-button variant="secondary" wire:click="$set('showDeleteModal', false)">Cancelar</x-button>
            <x-button variant="danger" wire:click="delete">Sim, excluir</x-button>
        </div>
    </x-modal>
</div>
