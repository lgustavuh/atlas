<div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full">

    <div class="sm:flex sm:items-center sm:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Alertas Administrativos</h1>
            <p class="mt-1 text-sm text-gray-600">Avisos exibidos para colaboradores no topo das páginas.</p>
        </div>
        @can('create', App\Models\AlertaAdm::class)
            <div class="mt-4 sm:mt-0">
                <x-button wire:click="openCreate" icon="plus">Novo alerta</x-button>
            </div>
        @endcan
    </div>

    <x-card class="mb-4" padding="p-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-input label="Buscar" wire:model.live.debounce.400ms="search" placeholder="Título ou mensagem..." />
            <x-select label="Prioridade" wire:model.live="filterPrioridade">
                <option value="">Todas</option>
                @foreach ($prioridades as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </x-select>
            <x-select label="Status" wire:model.live="filterStatus">
                <option value="">Todos</option>
                <option value="ativos">Ativos</option>
                <option value="inativos">Inativos</option>
            </x-select>
        </div>
    </x-card>

    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Alerta</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prioridade</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vigência</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Destinatários</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($alertas as $a)
                        <tr wire:key="al-{{ $a->id }}" class="hover:bg-gray-50 {{ $a->prioridade === 'critica' && $a->vigente ? 'bg-red-50/30' : '' }}">
                            <td class="px-6 py-3">
                                <div class="text-sm font-medium text-gray-900">{{ $a->titulo }}</div>
                                <div class="text-xs text-gray-500 line-clamp-1">{{ Str::limit($a->mensagem, 120) }}</div>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                <x-badge :variant="$a->prioridade_cor">{{ $a->prioridade_label }}</x-badge>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-xs text-gray-600">
                                @if ($a->data_inicio || $a->data_fim)
                                    @if ($a->data_inicio) {{ $a->data_inicio->format('d/m/Y') }} @else <span class="text-gray-400">sem início</span> @endif
                                    <span class="text-gray-400 mx-1">→</span>
                                    @if ($a->data_fim) {{ $a->data_fim->format('d/m/Y') }} @else <span class="text-gray-400">sem fim</span> @endif
                                @else
                                    <span class="text-gray-400 italic">permanente</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-center">
                                <div class="text-sm text-gray-900">
                                    {{ $a->visualizados_count }} / {{ $a->destinatarios_count }}
                                </div>
                                <div class="text-xs text-gray-400">visualizados</div>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-center">
                                @if (!$a->ativo)
                                    <x-badge variant="gray">Inativo</x-badge>
                                @elseif ($a->vigente)
                                    <x-badge variant="green" icon="circle-check">No ar</x-badge>
                                @else
                                    <x-badge variant="yellow">Fora da vigência</x-badge>
                                @endif
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                @can('update', $a)
                                    <button wire:click="toggleAtivo({{ $a->id }})"
                                            class="{{ $a->ativo ? 'text-yellow-600 hover:text-yellow-900' : 'text-green-600 hover:text-green-900' }}"
                                            title="{{ $a->ativo ? 'Desativar' : 'Ativar' }}">
                                        <i class="ti ti-{{ $a->ativo ? 'player-pause' : 'player-play' }}" aria-hidden="true"></i>
                                    </button>
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
                            <td colspan="6" class="px-6 py-12 text-center">
                                <i class="ti ti-bell-off text-4xl text-gray-300 mb-2 block" aria-hidden="true"></i>
                                <p class="text-sm text-gray-500">Nenhum alerta cadastrado.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($alertas->hasPages())
            <div class="px-6 py-3 border-t border-gray-200">{{ $alertas->links() }}</div>
        @endif
    </x-card>

    {{-- Modal --}}
    <x-modal name="showModal" max-width="3xl" :title="$editando ? 'Editar alerta' : 'Novo alerta'">
        <form wire:submit.prevent="save">
            <div class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">

                <x-input label="Título" name="titulo" wire:model="titulo" required />

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mensagem <span class="text-red-500">*</span></label>
                    <textarea wire:model="mensagem" rows="4" required
                              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                              placeholder="Conteúdo do aviso..."></textarea>
                    @error('mensagem') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <x-select label="Prioridade" name="prioridade" wire:model="prioridade" required>
                        @foreach ($prioridades as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </x-select>
                    <x-input label="Vigência início" name="data_inicio" type="date" wire:model="data_inicio" hint="Vazio = imediato" />
                    <x-input label="Vigência fim" name="data_fim" type="date" wire:model="data_fim" hint="Vazio = permanente" />
                </div>

                <div class="bg-gray-50 border border-gray-200 rounded-md p-3">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" wire:model="ativo" class="h-4 w-4 rounded text-indigo-600 focus:ring-indigo-500">
                        <span class="ml-2 text-sm text-gray-900">Alerta ativo (visível para destinatários)</span>
                    </label>
                </div>

                {{-- Destinatários --}}
                <div>
                    <h3 class="text-sm font-medium text-gray-900 mb-2">Destinatários</h3>
                    <div class="bg-blue-50 border border-blue-200 rounded-md p-3 mb-3">
                        <label class="flex items-start cursor-pointer">
                            <input type="checkbox" wire:model="enviarParaTodos" class="mt-0.5 h-4 w-4 rounded text-indigo-600 focus:ring-indigo-500">
                            <div class="ml-2">
                                <p class="text-sm text-gray-900">Enviar para todos os colaboradores ativos</p>
                                <p class="text-xs text-gray-600">{{ $totalColaboradoresAtivos }} colaboradores receberão o alerta</p>
                            </div>
                        </label>
                    </div>

                    @if (!$enviarParaTodos)
                        <x-input label="Filtrar colaboradores" wire:model.live.debounce.400ms="searchColaborador"
                                 placeholder="Nome ou CPF..." />
                        <div class="mt-2 max-h-60 overflow-y-auto border border-gray-200 rounded-md">
                            @if ($colaboradores->isEmpty())
                                <p class="text-xs text-gray-500 italic p-3">Nenhum colaborador encontrado.</p>
                            @else
                                <div class="divide-y divide-gray-100">
                                    @foreach ($colaboradores as $col)
                                        <label class="flex items-center px-3 py-2 hover:bg-gray-50 cursor-pointer text-sm">
                                            <input type="checkbox" wire:model.live="colaboradorIds" value="{{ $col->id }}"
                                                   class="h-4 w-4 rounded text-indigo-600 focus:ring-indigo-500">
                                            <span class="ml-2">{{ $col->nome }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <p class="mt-1 text-xs text-gray-500">
                            {{ count($colaboradorIds) }} colaborador(es) selecionado(s).
                        </p>
                    @endif
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
            <p class="text-sm text-gray-700">Excluir o alerta <strong>{{ $deletingName }}</strong>?</p>
        </div>
        <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 flex justify-end space-x-2">
            <x-button variant="secondary" wire:click="$set('showDeleteModal', false)">Cancelar</x-button>
            <x-button variant="danger" wire:click="delete">Sim, excluir</x-button>
        </div>
    </x-modal>
</div>
