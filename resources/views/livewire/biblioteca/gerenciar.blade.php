<div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full">

    <div class="sm:flex sm:items-center sm:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Biblioteca de Documentos</h1>
            <p class="mt-1 text-sm text-gray-600">Manuais, normas, modelos e documentos padronizados.</p>
        </div>
        <div class="mt-4 sm:mt-0 flex gap-2">
            <a href="{{ route('biblioteca.areas') }}" wire:navigate.hover>
                <x-button variant="secondary" icon="category">Áreas</x-button>
            </a>
            @can('create', App\Models\BibliotecaDocumento::class)
                <x-button wire:click="openCreate" icon="upload">Novo documento</x-button>
            @endcan
        </div>
    </div>

    <x-card class="mb-4" padding="p-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-input label="Buscar" wire:model.live.debounce.400ms="search" placeholder="Título ou descrição..." />
            <x-select label="Área" wire:model.live="filterAreaId">
                <option value="">Todas as áreas</option>
                @foreach ($areas as $area)
                    <option value="{{ $area->id }}">{{ $area->nome }}</option>
                @endforeach
            </x-select>
        </div>
    </x-card>

    {{-- Grade de documentos --}}
    @if ($documentos->isEmpty())
        <x-card>
            <div class="py-12 text-center">
                <i class="ti ti-file-off text-4xl text-gray-300 mb-2 block" aria-hidden="true"></i>
                <p class="text-sm text-gray-500">Nenhum documento na biblioteca.</p>
            </div>
        </x-card>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($documentos as $doc)
                <div wire:key="doc-{{ $doc->id }}" class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 flex flex-col hover:shadow-md transition-shadow">
                    <div class="flex items-start gap-3">
                        <div class="w-12 h-12 rounded-md bg-indigo-50 flex items-center justify-center flex-shrink-0">
                            <i class="ti ti-{{ $doc->icone }} text-indigo-600 text-2xl" aria-hidden="true"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-sm font-medium text-gray-900 truncate" title="{{ $doc->titulo }}">{{ $doc->titulo }}</h3>
                            @if ($doc->versao)
                                <div class="text-xs text-gray-500">v{{ $doc->versao }}</div>
                            @endif
                            <div class="text-xs text-gray-400 mt-1">{{ $doc->tamanho_legivel }} · {{ $doc->downloads_count }} downloads</div>
                        </div>
                    </div>

                    @if ($doc->descricao)
                        <p class="mt-3 text-xs text-gray-600 line-clamp-2">{{ $doc->descricao }}</p>
                    @endif

                    @if ($doc->areas->isNotEmpty())
                        <div class="mt-3 flex flex-wrap gap-1">
                            @foreach ($doc->areas as $area)
                                <x-badge variant="blue">{{ $area->nome }}</x-badge>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between">
                        <div class="flex gap-2">
                            @can('view', $doc)
                                <a href="{{ route('biblioteca.visualizar', $doc->id) }}" target="_blank"
                                   class="text-xs text-gray-600 hover:text-gray-900 inline-flex items-center">
                                    <i class="ti ti-eye mr-1" aria-hidden="true"></i> Ver
                                </a>
                            @endcan
                            @can('download', $doc)
                                <a href="{{ route('biblioteca.download', $doc->id) }}"
                                   class="text-xs text-indigo-600 hover:text-indigo-900 inline-flex items-center">
                                    <i class="ti ti-download mr-1" aria-hidden="true"></i> Baixar
                                </a>
                            @endcan
                        </div>
                        <div class="flex gap-2">
                            @can('update', $doc)
                                <button wire:click="openEdit({{ $doc->id }})" class="text-gray-500 hover:text-indigo-600" title="Editar">
                                    <i class="ti ti-edit" aria-hidden="true"></i>
                                </button>
                            @endcan
                            @can('delete', $doc)
                                <button wire:click="confirmDelete({{ $doc->id }})" class="text-gray-500 hover:text-red-600" title="Excluir">
                                    <i class="ti ti-trash" aria-hidden="true"></i>
                                </button>
                            @endcan
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        @if ($documentos->hasPages())
            <div class="mt-4">{{ $documentos->links() }}</div>
        @endif
    @endif

    {{-- Modal --}}
    <x-modal name="showModal" max-width="2xl" :title="$editando ? 'Editar documento' : 'Novo documento'">
        <form wire:submit.prevent="save">
            <div class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
                <x-input label="Título" name="titulo" wire:model="titulo" required />
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                    <textarea wire:model="descricao" rows="3"
                              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                              placeholder="Para que serve este documento, quando usar..."></textarea>
                </div>
                <x-input label="Versão (opcional)" name="versao" wire:model="versao" placeholder="ex: 1.0, 2.3" />

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Áreas</label>
                    @if ($areas->isEmpty())
                        <p class="text-sm text-gray-500 italic">
                            Nenhuma área cadastrada. <a href="{{ route('biblioteca.areas') }}" class="text-indigo-600 hover:underline">Criar primeira área</a>.
                        </p>
                    @else
                        <div class="flex flex-wrap gap-2">
                            @foreach ($areas as $area)
                                <label class="inline-flex items-center text-sm cursor-pointer bg-gray-50 border border-gray-200 rounded-md px-2 py-1 hover:bg-gray-100">
                                    <input type="checkbox" wire:model="selectedAreas" value="{{ $area->id }}"
                                           class="h-4 w-4 rounded text-indigo-600 focus:ring-indigo-500">
                                    <span class="ml-2">{{ $area->nome }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Arquivo {{ $editando ? '(opcional, deixe vazio para manter)' : '*' }}
                    </label>
                    <input type="file" wire:model="arquivo"
                           accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.zip,.txt"
                           class="block w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    <p class="mt-1 text-xs text-gray-500">PDF, DOC, XLS, PPT, JPG, PNG, ZIP, TXT — até 20 MB</p>
                    @error('arquivo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
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
            <p class="text-sm text-gray-700">Excluir o documento <strong>{{ $deletingName }}</strong> da biblioteca?</p>
        </div>
        <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 flex justify-end space-x-2">
            <x-button variant="secondary" wire:click="$set('showDeleteModal', false)">Cancelar</x-button>
            <x-button variant="danger" wire:click="delete">Sim, excluir</x-button>
        </div>
    </x-modal>
</div>
