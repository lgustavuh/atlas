<div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full">

    <div class="sm:flex sm:items-center sm:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Advertências</h1>
            <p class="mt-1 text-sm text-gray-600">Histórico disciplinar dos colaboradores.</p>
        </div>
        @can('create', App\Models\Advertencia::class)
            <div class="mt-4 sm:mt-0">
                <x-button wire:click="openCreate" icon="plus">Nova advertência</x-button>
            </div>
        @endcan
    </div>

    <x-card class="mb-4" padding="p-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-input label="Buscar colaborador" wire:model.live.debounce.400ms="search" placeholder="Nome ou CPF..." />
            <x-select label="Tipo" wire:model.live="filterTipo">
                <option value="">Todos</option>
                <option value="verbal">Verbal</option>
                <option value="escrita">Escrita</option>
                <option value="suspensao">Suspensão</option>
            </x-select>
            <x-select label="Colaborador" wire:model.live="filterColaboradorId">
                <option value="">Todos</option>
                @foreach ($colaboradores as $col)
                    <option value="{{ $col->id }}">{{ $col->nome }}</option>
                @endforeach
            </x-select>
        </div>
    </x-card>

    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Colaborador</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Motivo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ciente?</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Doc.</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($advertencias as $adv)
                        <tr wire:key="adv-{{ $adv->id }}" class="hover:bg-gray-50">
                            <td class="px-6 py-3 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $adv->colaborador?->nome ?? '—' }}</div>
                                @if ($adv->aplicadoPor)
                                    <div class="text-xs text-gray-500">aplicada por {{ $adv->aplicadoPor->nome }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                @if ($adv->tipo === 'verbal')
                                    <x-badge variant="yellow">Verbal</x-badge>
                                @elseif ($adv->tipo === 'escrita')
                                    <x-badge variant="indigo">Escrita</x-badge>
                                @else
                                    <x-badge variant="red">
                                        Suspensão ({{ $adv->dias_suspensao }} dias)
                                    </x-badge>
                                @endif
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">
                                <div>{{ $adv->data_ocorrencia?->format('d/m/Y') }}</div>
                                @if ($adv->data_aplicacao && $adv->data_aplicacao->format('Y-m-d') !== $adv->data_ocorrencia?->format('Y-m-d'))
                                    <div class="text-xs text-gray-400">aplicada {{ $adv->data_aplicacao->format('d/m/Y') }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-600 max-w-xs">
                                <div class="truncate" title="{{ $adv->motivo }}">{{ $adv->motivo }}</div>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                @if ($adv->ciente_colaborador)
                                    <x-badge variant="green" icon="circle-check">Sim</x-badge>
                                @else
                                    <x-badge variant="gray">Pendente</x-badge>
                                @endif
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-center">
                                @if ($adv->documento_path)
                                    <a href="{{ route('documentos.advertencia', $adv->id) }}" target="_blank"
                                       class="text-indigo-600 hover:text-indigo-900" title="Ver documento">
                                        <i class="ti ti-file-text text-lg" aria-hidden="true"></i>
                                    </a>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-medium space-x-3">
                                @can('view', $adv)
                                    <a href="{{ route('pdf.advertencia', $adv) }}" target="_blank"
                                       class="text-gray-600 hover:text-gray-900" title="Gerar PDF">
                                        <i class="ti ti-file-type-pdf" aria-hidden="true"></i>
                                    </a>
                                @endcan
                                @can('update', $adv)
                                    <button wire:click="openEdit({{ $adv->id }})" class="text-indigo-600 hover:text-indigo-900">
                                        <i class="ti ti-edit" aria-hidden="true"></i>
                                    </button>
                                @endcan
                                @can('delete', $adv)
                                    <button wire:click="confirmDelete({{ $adv->id }})" class="text-red-600 hover:text-red-900">
                                        <i class="ti ti-trash" aria-hidden="true"></i>
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <i class="ti ti-alert-octagon-off text-4xl text-gray-300 mb-2 block" aria-hidden="true"></i>
                                <p class="text-sm text-gray-500">Nenhuma advertência registrada.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($advertencias->hasPages())
            <div class="px-6 py-3 border-t border-gray-200">{{ $advertencias->links() }}</div>
        @endif
    </x-card>

    {{-- Modal de criar/editar --}}
    <x-modal name="showModal" max-width="3xl" :title="$editando ? 'Editar advertência' : 'Nova advertência'">
        <form wire:submit.prevent="save" enctype="multipart/form-data">
            <div class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-select label="Colaborador" name="colaborador_id" wire:model="colaborador_id" required>
                        <option value="">Selecione</option>
                        @foreach ($colaboradores as $col)
                            <option value="{{ $col->id }}">{{ $col->nome }}</option>
                        @endforeach
                    </x-select>

                    <x-select label="Tipo de advertência" name="tipo" wire:model.live="tipo" required>
                        <option value="verbal">Verbal</option>
                        <option value="escrita">Escrita</option>
                        <option value="suspensao">Suspensão</option>
                    </x-select>

                    <x-input label="Data da ocorrência" name="data_ocorrencia" type="date" wire:model="data_ocorrencia" required />
                    <x-input label="Data de aplicação" name="data_aplicacao" type="date" wire:model="data_aplicacao" required />

                    @if ($tipo === 'suspensao')
                        <x-input label="Dias de suspensão" name="dias_suspensao" type="number" min="1" max="30" wire:model="dias_suspensao" required />
                    @endif

                    <x-select label="Aplicada por" name="aplicado_por_id" wire:model="aplicado_por_id">
                        <option value="">— Selecione —</option>
                        @foreach ($colaboradores as $col)
                            <option value="{{ $col->id }}">{{ $col->nome }}</option>
                        @endforeach
                    </x-select>
                </div>

                <x-input label="Motivo" name="motivo" wire:model="motivo" required placeholder="Resumo breve da advertência" />

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descrição da ocorrência <span class="text-red-500">*</span></label>
                    <textarea wire:model="descricao_ocorrencia" rows="4" required
                              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                              placeholder="Descrição detalhada do ocorrido..."></textarea>
                    @error('descricao_ocorrencia') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Documento (PDF/imagem - opcional)</label>
                    <input type="file" wire:model="documento" accept="application/pdf,image/jpeg,image/png"
                           class="block w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700">
                    <p class="mt-1 text-xs text-gray-500">PDF, JPG ou PNG até 10MB</p>
                    @error('documento') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    @if ($documento)
                        <p class="mt-1 text-xs text-green-700"><i class="ti ti-check"></i> {{ $documento->getClientOriginalName() }}</p>
                    @endif
                </div>

                <label class="flex items-center text-sm cursor-pointer">
                    <input type="checkbox" wire:model="ciente_colaborador" class="h-4 w-4 rounded text-indigo-600 focus:ring-indigo-500">
                    <span class="ml-2">Colaborador deu ciência</span>
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
            <p class="text-sm text-gray-700">Excluir esta advertência? A ação pode ser revertida posteriormente.</p>
        </div>
        <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 flex justify-end space-x-2">
            <x-button variant="secondary" wire:click="$set('showDeleteModal', false)">Cancelar</x-button>
            <x-button variant="danger" wire:click="delete">Sim, excluir</x-button>
        </div>
    </x-modal>
</div>
