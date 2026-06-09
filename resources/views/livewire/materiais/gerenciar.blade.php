<div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full">

    <div class="sm:flex sm:items-center sm:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Materiais</h1>
            <p class="mt-1 text-sm text-gray-600">Insumos e materiais do almoxarifado.</p>
        </div>
        @can('create', App\Models\Material::class)
            <div class="mt-4 sm:mt-0">
                <x-button wire:click="openCreate" icon="plus">Novo material</x-button>
            </div>
        @endcan
    </div>

    @if ($totalAbaixoMinimo > 0)
        <div class="mb-4">
            <x-alert variant="warning" title="Atenção ao estoque">
                {{ $totalAbaixoMinimo }} {{ Str::plural('material', $totalAbaixoMinimo) }} {{ $totalAbaixoMinimo > 1 ? 'estão' : 'está' }} abaixo do estoque mínimo.
                <button wire:click="$set('filterEstoque', 'baixo')" class="underline font-medium ml-1">Ver agora</button>
            </x-alert>
        </div>
    @endif

    <x-card class="mb-4" padding="p-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-input label="Buscar" wire:model.live.debounce.400ms="search" placeholder="Nome ou código..." />
            <x-select label="Grupo" wire:model.live="filterGrupo">
                <option value="">Todos</option>
                @foreach ($grupos as $g)
                    <option value="{{ $g->id }}">{{ $g->nome }}</option>
                @endforeach
            </x-select>
            <x-select label="Estoque" wire:model.live="filterEstoque">
                <option value="">Todos</option>
                <option value="baixo">Abaixo do mínimo</option>
            </x-select>
        </div>
    </x-card>

    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Material</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Código</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Grupo</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Estoque</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Preço ref.</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($materiais as $m)
                        <tr wire:key="mat-{{ $m->id }}" class="hover:bg-gray-50">
                            <td class="px-6 py-3">
                                <div class="text-sm font-medium text-gray-900">{{ $m->nome }}</div>
                                @if ($m->localizacao_estoque)
                                    <div class="text-xs text-gray-500"><i class="ti ti-map-pin text-xs"></i> {{ $m->localizacao_estoque }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600 font-mono">{{ $m->codigo ?? '—' }}</td>
                            <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">{{ $m->grupo?->nome ?? '—' }}</td>
                            <td class="px-6 py-3 whitespace-nowrap text-right">
                                <div class="text-sm font-medium {{ $m->estoque_baixo ? 'text-red-600' : 'text-gray-900' }}">
                                    {{ $m->estoque_formatado }}
                                    @if ($m->estoque_baixo)
                                        <i class="ti ti-alert-triangle text-red-500" title="Abaixo do mínimo" aria-hidden="true"></i>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-400">mín: {{ rtrim(rtrim(number_format((float)$m->estoque_minimo, 4, ',', '.'), '0'), ',') }}</div>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-right text-sm text-gray-600">
                                @if ($m->preco_referencia)
                                    R$ {{ number_format((float) $m->preco_referencia, 2, ',', '.') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-medium space-x-3">
                                @can('update', $m)
                                    <button wire:click="openEdit({{ $m->id }})" class="text-indigo-600 hover:text-indigo-900">
                                        <i class="ti ti-edit" aria-hidden="true"></i>
                                    </button>
                                @endcan
                                @can('delete', $m)
                                    <button wire:click="confirmDelete({{ $m->id }})" class="text-red-600 hover:text-red-900">
                                        <i class="ti ti-trash" aria-hidden="true"></i>
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <i class="ti ti-package-off text-4xl text-gray-300 mb-2 block" aria-hidden="true"></i>
                                <p class="text-sm text-gray-500">Nenhum material encontrado.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($materiais->hasPages())
            <div class="px-6 py-3 border-t border-gray-200">{{ $materiais->links() }}</div>
        @endif
    </x-card>

    <x-modal name="showModal" max-width="2xl" :title="$editando ? 'Editar material' : 'Novo material'">
        <form wire:submit.prevent="save">
            <div class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <x-input label="Nome" name="nome" wire:model="nome" required />
                    </div>
                    <x-input label="Código/SKU" name="codigo" wire:model="codigo" />
                    <x-select label="Grupo" name="grupo_id" wire:model="grupo_id">
                        <option value="">Sem grupo</option>
                        @foreach ($grupos as $g)
                            <option value="{{ $g->id }}">{{ $g->nome }}</option>
                        @endforeach
                    </x-select>
                    <x-select label="Unidade de medida" name="unidade_medida" wire:model="unidade_medida" required>
                        @foreach ($unidades as $u)
                            <option value="{{ $u }}">{{ $u }}</option>
                        @endforeach
                    </x-select>
                    <x-input label="Preço referência (R$)" name="preco_referencia" type="number" step="0.0001" min="0" wire:model="preco_referencia" />
                </div>

                <div class="bg-gray-50 border border-gray-200 rounded-md p-3">
                    <p class="text-xs font-medium text-gray-700 mb-2">Controle de estoque</p>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <x-input label="Estoque atual" name="estoque_atual" type="number" step="0.0001" min="0" wire:model="estoque_atual" required />
                        <x-input label="Estoque mínimo" name="estoque_minimo" type="number" step="0.0001" min="0" wire:model="estoque_minimo" required />
                        <x-input label="Estoque máximo" name="estoque_maximo" type="number" step="0.0001" min="0" wire:model="estoque_maximo" />
                    </div>
                </div>

                <x-input label="Localização no estoque" name="localizacao_estoque" wire:model="localizacao_estoque" placeholder="Ex: Almoxarifado A - Prateleira 3" />

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                    <textarea wire:model="descricao" rows="2" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
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
            <p class="text-sm text-gray-700">Excluir o material <strong>{{ $deletingName }}</strong>?</p>
        </div>
        <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 flex justify-end space-x-2">
            <x-button variant="secondary" wire:click="$set('showDeleteModal', false)">Cancelar</x-button>
            <x-button variant="danger" wire:click="delete">Sim, excluir</x-button>
        </div>
    </x-modal>
</div>
