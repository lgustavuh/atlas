<div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full">

    <div class="sm:flex sm:items-center sm:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Classificações</h1>
            <p class="mt-1 text-sm text-gray-600">Tags/categorias para organizar colaboradores.</p>
        </div>
        @can('create', App\Models\Classificacao::class)
            <div class="mt-4 sm:mt-0">
                <x-button wire:click="openCreate" icon="plus">Nova classificação</x-button>
            </div>
        @endcan
    </div>

    <x-card class="mb-4" padding="p-4">
        <x-input label="Buscar" wire:model.live.debounce.400ms="search" placeholder="Nome..." />
    </x-card>

    @if ($classificacoes->isEmpty())
        <x-card>
            <div class="text-center py-8">
                <i class="ti ti-tag-off text-4xl text-gray-300 mb-2 block" aria-hidden="true"></i>
                <p class="text-sm text-gray-500">Nenhuma classificação cadastrada.</p>
            </div>
        </x-card>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($classificacoes as $c)
                <div wire:key="c-{{ $c->id }}" class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div class="h-2" style="background: {{ $c->cor_hex ?? '#9CA3AF' }}"></div>
                    <div class="p-4">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center min-w-0">
                                <div class="w-8 h-8 rounded-full flex-shrink-0" style="background: {{ $c->cor_hex ?? '#9CA3AF' }}"></div>
                                <div class="ml-3 min-w-0">
                                    <h3 class="text-sm font-medium text-gray-900 truncate">{{ $c->nome }}</h3>
                                    <p class="text-xs text-gray-500 font-mono">{{ $c->cor_hex }}</p>
                                </div>
                            </div>
                        </div>
                        @if ($c->descricao)
                            <p class="mt-2 text-xs text-gray-600">{{ $c->descricao }}</p>
                        @endif
                        <div class="mt-3 flex items-center justify-between text-xs">
                            <x-badge variant="{{ $c->colaboradores_count > 0 ? 'indigo' : 'gray' }}">
                                {{ $c->colaboradores_count }} {{ Str::plural('colaborador', $c->colaboradores_count) }}
                            </x-badge>
                            <div class="space-x-2">
                                @can('update', $c)
                                    <button wire:click="openEdit({{ $c->id }})" class="text-indigo-600 hover:text-indigo-900">
                                        <i class="ti ti-edit" aria-hidden="true"></i>
                                    </button>
                                @endcan
                                @can('delete', $c)
                                    <button wire:click="confirmDelete({{ $c->id }})" class="text-red-600 hover:text-red-900">
                                        <i class="ti ti-trash" aria-hidden="true"></i>
                                    </button>
                                @endcan
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <x-modal name="showModal" max-width="md" :title="$editando ? 'Editar classificação' : 'Nova classificação'">
        <form wire:submit.prevent="save">
            <div class="px-6 py-4 space-y-4">
                <x-input label="Nome" name="nome" wire:model="nome" required />

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cor</label>
                    <div class="flex items-center space-x-3">
                        <input type="color" wire:model.live="cor_hex" class="w-12 h-10 rounded border border-gray-300 cursor-pointer">
                        <input type="text" wire:model.live="cor_hex" placeholder="#3B82F6"
                               class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono">
                    </div>
                    @error('cor_hex') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                    <textarea wire:model="descricao" rows="2" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                </div>

                {{-- Preview --}}
                <div class="p-3 bg-gray-50 rounded-md">
                    <p class="text-xs text-gray-500 mb-1">Preview:</p>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium text-white"
                          style="background: {{ $cor_hex }}">
                        {{ $nome ?: 'Nome da classificação' }}
                    </span>
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
            <p class="text-sm text-gray-700">Excluir a classificação <strong>{{ $deletingName }}</strong>?</p>
        </div>
        <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 flex justify-end space-x-2">
            <x-button variant="secondary" wire:click="$set('showDeleteModal', false)">Cancelar</x-button>
            <x-button variant="danger" wire:click="delete">Sim, excluir</x-button>
        </div>
    </x-modal>
</div>
