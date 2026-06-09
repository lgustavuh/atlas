<div class="p-4 sm:p-6 lg:p-8 max-w-5xl mx-auto w-full">

    <div class="sm:flex sm:items-center sm:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Áreas da Biblioteca</h1>
            <p class="mt-1 text-sm text-gray-600">Categorias temáticas para organizar os documentos.</p>
        </div>
        <div class="mt-4 sm:mt-0 flex gap-2">
            <a href="{{ route('biblioteca.index') }}" wire:navigate.hover>
                <x-button variant="secondary" icon="books">Documentos</x-button>
            </a>
            @can('create', App\Models\BibliotecaArea::class)
                <x-button wire:click="openCreate" icon="plus">Nova área</x-button>
            @endcan
        </div>
    </div>

    <x-card class="mb-4" padding="p-4">
        <x-input label="Buscar" wire:model.live.debounce.400ms="search" placeholder="Nome da área..." />
    </x-card>

    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Área</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Descrição</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Documentos</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($areas as $a)
                        <tr wire:key="area-{{ $a->id }}" class="hover:bg-gray-50">
                            <td class="px-6 py-3 whitespace-nowrap text-sm font-medium text-gray-900">{{ $a->nome }}</td>
                            <td class="px-6 py-3 text-sm text-gray-600 max-w-md truncate" title="{{ $a->descricao }}">
                                {{ $a->descricao ?? '—' }}
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-center">
                                <x-badge variant="{{ $a->documentos_count > 0 ? 'indigo' : 'gray' }}">{{ $a->documentos_count }}</x-badge>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-medium space-x-3">
                                @can('update', $a)
                                    <button wire:click="openEdit({{ $a->id }})" class="text-indigo-600 hover:text-indigo-900">
                                        <i class="ti ti-edit" aria-hidden="true"></i>
                                    </button>
                                @endcan
                                @can('delete', $a)
                                    <button wire:click="confirmDelete({{ $a->id }})" class="text-red-600 hover:text-red-900">
                                        <i class="ti ti-trash" aria-hidden="true"></i>
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center">
                                <i class="ti ti-category-off text-4xl text-gray-300 mb-2 block" aria-hidden="true"></i>
                                <p class="text-sm text-gray-500">Nenhuma área cadastrada.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($areas->hasPages())
            <div class="px-6 py-3 border-t border-gray-200">{{ $areas->links() }}</div>
        @endif
    </x-card>

    <x-modal name="showModal" max-width="lg" :title="$editando ? 'Editar área' : 'Nova área'">
        <form wire:submit.prevent="save">
            <div class="px-6 py-4 space-y-4">
                <x-input label="Nome" name="nome" wire:model="nome" required />
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                    <textarea wire:model="descricao" rows="3"
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
            <p class="text-sm text-gray-700">Excluir a área <strong>{{ $deletingName }}</strong>?</p>
        </div>
        <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 flex justify-end space-x-2">
            <x-button variant="secondary" wire:click="$set('showDeleteModal', false)">Cancelar</x-button>
            <x-button variant="danger" wire:click="delete">Sim, excluir</x-button>
        </div>
    </x-modal>
</div>
