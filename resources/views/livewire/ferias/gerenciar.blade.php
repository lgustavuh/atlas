<div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full">

    <div class="sm:flex sm:items-center sm:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Férias</h1>
            <p class="mt-1 text-sm text-gray-600">Programação, aprovação e controle de períodos de gozo.</p>
        </div>
        @can('create', App\Models\Ferias::class)
            <div class="mt-4 sm:mt-0">
                <x-button wire:click="openCreate" icon="plus">Programar férias</x-button>
            </div>
        @endcan
    </div>

    {{-- Cards de resumo --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-md bg-yellow-50 flex items-center justify-center">
                    <i class="ti ti-clock text-yellow-600 text-xl" aria-hidden="true"></i>
                </div>
                <div class="ml-3">
                    <p class="text-xs text-gray-500">Aguardando aprovação</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['aguardando'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-md bg-blue-50 flex items-center justify-center">
                    <i class="ti ti-beach text-blue-600 text-xl" aria-hidden="true"></i>
                </div>
                <div class="ml-3">
                    <p class="text-xs text-gray-500">Em gozo agora</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['em_gozo'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-md bg-green-50 flex items-center justify-center">
                    <i class="ti ti-calendar-event text-green-600 text-xl" aria-hidden="true"></i>
                </div>
                <div class="ml-3">
                    <p class="text-xs text-gray-500">Próximos 60 dias</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['proximas'] }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filtros --}}
    <x-card class="mb-4" padding="p-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-input label="Buscar colaborador" wire:model.live.debounce.400ms="search" placeholder="Nome ou CPF..." />
            <x-select label="Status" wire:model.live="filterStatus">
                <option value="">Todos</option>
                <option value="programada">Programadas (aguardando aprovação)</option>
                <option value="aprovada">Aprovadas</option>
                <option value="em_gozo">Em gozo</option>
                <option value="concluida">Concluídas</option>
                <option value="cancelada">Canceladas</option>
            </x-select>
        </div>
    </x-card>

    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Colaborador</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Período aquisitivo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Gozo</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Dias</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($ferias as $f)
                        <tr wire:key="f-{{ $f->id }}" class="hover:bg-gray-50 {{ $f->status === 'programada' ? 'bg-yellow-50/30' : '' }}">
                            <td class="px-6 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $f->colaborador?->nome ?? '—' }}
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">
                                {{ $f->periodo_aquisitivo_inicio?->format('d/m/Y') }}
                                <span class="text-gray-400">a</span>
                                {{ $f->periodo_aquisitivo_fim?->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">
                                @if ($f->data_inicio_gozo)
                                    {{ $f->data_inicio_gozo->format('d/m/Y') }}
                                    <span class="text-gray-400">a</span>
                                    {{ $f->data_fim_gozo?->format('d/m/Y') }}
                                @else
                                    <span class="text-gray-400 italic">não programado</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-center">
                                <div class="text-sm text-gray-900">{{ $f->dias_gozo ?? 0 }}d gozo</div>
                                @if ($f->dias_abono > 0)
                                    <div class="text-xs text-amber-600">+{{ $f->dias_abono }}d abono</div>
                                @endif
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                @if ($f->status === 'programada')
                                    <x-badge variant="yellow" icon="clock">Aguardando</x-badge>
                                @elseif ($f->status === 'aprovada')
                                    <x-badge variant="green" icon="circle-check">Aprovada</x-badge>
                                @elseif ($f->status === 'em_gozo')
                                    <x-badge variant="blue" icon="beach">Em gozo</x-badge>
                                @elseif ($f->status === 'concluida')
                                    <x-badge variant="gray" icon="check">Concluída</x-badge>
                                @else
                                    <x-badge variant="red" icon="circle-x">Cancelada</x-badge>
                                @endif
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                @if ($f->status === 'programada')
                                    @can('approve', $f)
                                        <button wire:click="abrirAprovacao({{ $f->id }}, 'aprovar')"
                                                class="text-green-600 hover:text-green-900" title="Aprovar">
                                            <i class="ti ti-check text-lg" aria-hidden="true"></i>
                                        </button>
                                    @endcan
                                    @can('reject', $f)
                                        <button wire:click="abrirAprovacao({{ $f->id }}, 'rejeitar')"
                                                class="text-red-600 hover:text-red-900" title="Rejeitar">
                                            <i class="ti ti-x text-lg" aria-hidden="true"></i>
                                        </button>
                                    @endcan
                                @elseif ($f->status === 'aprovada')
                                    @can('update', $f)
                                        <button wire:click="iniciarGozo({{ $f->id }})"
                                                wire:confirm="Confirmar início do gozo de férias?"
                                                class="text-blue-600 hover:text-blue-900" title="Iniciar gozo">
                                            <i class="ti ti-player-play text-lg" aria-hidden="true"></i>
                                        </button>
                                    @endcan
                                @elseif ($f->status === 'em_gozo')
                                    @can('update', $f)
                                        <button wire:click="concluir({{ $f->id }})"
                                                wire:confirm="Marcar férias como concluídas (colaborador retornou)?"
                                                class="text-gray-600 hover:text-gray-900" title="Concluir">
                                            <i class="ti ti-flag-check text-lg" aria-hidden="true"></i>
                                        </button>
                                    @endcan
                                @endif

                                @can('update', $f)
                                    <button wire:click="openEdit({{ $f->id }})"
                                            class="text-indigo-600 hover:text-indigo-900" title="Editar">
                                        <i class="ti ti-edit" aria-hidden="true"></i>
                                    </button>
                                @endcan
                                @can('delete', $f)
                                    <button wire:click="confirmDelete({{ $f->id }})"
                                            class="text-red-600 hover:text-red-900" title="Excluir">
                                        <i class="ti ti-trash" aria-hidden="true"></i>
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <i class="ti ti-beach-off text-4xl text-gray-300 mb-2 block" aria-hidden="true"></i>
                                <p class="text-sm text-gray-500">Nenhuma programação de férias encontrada.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($ferias->hasPages())
            <div class="px-6 py-3 border-t border-gray-200">{{ $ferias->links() }}</div>
        @endif
    </x-card>

    {{-- Modal cadastrar/editar --}}
    <x-modal name="showModal" max-width="2xl" :title="$editando ? 'Editar férias' : 'Programar férias'">
        <form wire:submit.prevent="save">
            <div class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
                <x-select label="Colaborador" name="colaborador_id" wire:model.live="colaborador_id" required>
                    <option value="">Selecione</option>
                    @foreach ($colaboradores as $col)
                        <option value="{{ $col->id }}">{{ $col->nome }}</option>
                    @endforeach
                </x-select>

                <div class="bg-blue-50 border border-blue-200 rounded-md p-3">
                    <p class="text-xs font-medium text-blue-900 mb-2">Período aquisitivo (12 meses de trabalho)</p>
                    <div class="grid grid-cols-2 gap-3">
                        <x-input label="Início" name="periodo_aquisitivo_inicio" type="date" wire:model="periodo_aquisitivo_inicio" required />
                        <x-input label="Fim" name="periodo_aquisitivo_fim" type="date" wire:model="periodo_aquisitivo_fim" required />
                    </div>
                </div>

                <div class="bg-green-50 border border-green-200 rounded-md p-3">
                    <p class="text-xs font-medium text-green-900 mb-2">Período de gozo (saída efetiva)</p>
                    <div class="grid grid-cols-2 gap-3">
                        <x-input label="Início do gozo" name="data_inicio_gozo" type="date" wire:model.live="data_inicio_gozo" required />
                        <x-input label="Fim do gozo" name="data_fim_gozo" type="date" wire:model="data_fim_gozo" required />
                    </div>
                    <div class="mt-3">
                        <x-input label="Dias de gozo" name="dias_gozo" type="number" min="5" max="30" wire:model.live="dias_gozo" required
                                 hint="Mínimo 5 (regra CLT), máximo 30" />
                    </div>
                </div>

                <div class="bg-amber-50 border border-amber-200 rounded-md p-3 space-y-3">
                    <p class="text-xs font-medium text-amber-900">Opções adicionais</p>

                    <label class="flex items-start cursor-pointer">
                        <input type="checkbox" wire:model.live="abono_pecuniario" class="mt-0.5 h-4 w-4 rounded text-indigo-600 focus:ring-indigo-500">
                        <div class="ml-2">
                            <p class="text-sm text-gray-900">Abono pecuniário (vender dias)</p>
                            <p class="text-xs text-gray-500">Receber em dinheiro até 10 dias (1/3 das férias)</p>
                        </div>
                    </label>

                    @if ($abono_pecuniario)
                        <x-input label="Dias de abono" name="dias_abono" type="number" min="1" max="10" wire:model="dias_abono" />
                    @endif

                    <label class="flex items-start cursor-pointer">
                        <input type="checkbox" wire:model="adiantar_13_salario" class="mt-0.5 h-4 w-4 rounded text-indigo-600 focus:ring-indigo-500">
                        <div class="ml-2">
                            <p class="text-sm text-gray-900">Adiantar 1ª parcela do 13º junto</p>
                            <p class="text-xs text-gray-500">Solicitação até novembro do ano anterior (CLT art. 4º)</p>
                        </div>
                    </label>
                </div>

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

    {{-- Modal aprovação --}}
    <x-modal name="showApprovalModal" max-width="md" :title="$approvalAction === 'aprovar' ? 'Aprovar férias' : 'Rejeitar férias'">
        <div class="px-6 py-4">
            @if ($approvalAction === 'aprovar')
                <p class="text-sm text-gray-700 mb-3">Confirma a aprovação destas férias?</p>
            @else
                <p class="text-sm text-gray-700 mb-3">Informe o motivo da rejeição:</p>
            @endif
            <textarea wire:model="approvalObs" rows="3"
                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                      placeholder="{{ $approvalAction === 'aprovar' ? 'Observações (opcional)...' : 'Motivo obrigatório...' }}"></textarea>
            @error('approvalObs') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 flex justify-end space-x-2">
            <x-button variant="secondary" wire:click="$set('showApprovalModal', false)">Cancelar</x-button>
            <x-button :variant="$approvalAction === 'aprovar' ? 'success' : 'danger'" wire:click="confirmarAprovacao">
                {{ $approvalAction === 'aprovar' ? 'Aprovar' : 'Rejeitar' }}
            </x-button>
        </div>
    </x-modal>

    <x-modal name="showDeleteModal" max-width="md" title="Confirmar exclusão">
        <div class="px-6 py-4">
            <p class="text-sm text-gray-700">Excluir este registro de férias?</p>
        </div>
        <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 flex justify-end space-x-2">
            <x-button variant="secondary" wire:click="$set('showDeleteModal', false)">Cancelar</x-button>
            <x-button variant="danger" wire:click="delete">Sim, excluir</x-button>
        </div>
    </x-modal>
</div>
