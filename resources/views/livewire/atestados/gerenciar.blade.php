<div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full">

    <div class="sm:flex sm:items-center sm:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Atestados</h1>
            <p class="mt-1 text-sm text-gray-600">Atestados médicos com fluxo de aprovação.</p>
        </div>
        @can('create', App\Models\Atestado::class)
            <div class="mt-4 sm:mt-0">
                <x-button wire:click="openCreate" icon="plus">Registrar atestado</x-button>
            </div>
        @endcan
    </div>

    {{-- Resumo --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-md bg-yellow-50 flex items-center justify-center">
                    <i class="ti ti-clock text-yellow-600 text-xl" aria-hidden="true"></i>
                </div>
                <div class="ml-3">
                    <p class="text-xs text-gray-500">Pendentes de análise</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['pendentes'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-md bg-green-50 flex items-center justify-center">
                    <i class="ti ti-circle-check text-green-600 text-xl" aria-hidden="true"></i>
                </div>
                <div class="ml-3">
                    <p class="text-xs text-gray-500">Aprovados este mês</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['aprovados_mes'] }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filtros --}}
    <x-card class="mb-4" padding="p-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-input label="Buscar colaborador" wire:model.live.debounce.400ms="search" placeholder="Nome ou CPF..." />
            <x-select label="Colaborador" wire:model.live="filterColaboradorId">
                <option value="">Todos</option>
                @foreach ($colaboradores as $col)
                    <option value="{{ $col->id }}">{{ $col->nome }}</option>
                @endforeach
            </x-select>
        </div>
        <div class="mt-4 flex flex-wrap gap-2">
            @php
                $statusOptions = [
                    'pendente' => ['Pendentes', 'yellow'],
                    'aprovado' => ['Aprovados', 'green'],
                    'rejeitado' => ['Rejeitados', 'red'],
                    'todos' => ['Todos', 'gray'],
                ];
            @endphp
            @foreach ($statusOptions as $key => [$label, $color])
                <button wire:click="$set('filterStatus', '{{ $key }}')"
                        class="px-3 py-1 text-xs rounded-full border {{ $filterStatus === $key ? "bg-{$color}-600 text-white border-{$color}-600" : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </x-card>

    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Colaborador</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Período</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Dias</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Médico/CID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($atestados as $at)
                        <tr wire:key="at-{{ $at->id }}" class="hover:bg-gray-50 {{ $at->status === 'pendente' ? 'bg-yellow-50/30' : '' }}">
                            <td class="px-6 py-3 whitespace-nowrap">
                                <p class="text-sm font-medium text-gray-900">{{ $at->colaborador?->nome ?? '—' }}</p>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">
                                {{ ucfirst(str_replace('_', ' ', $at->tipo)) }}
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">
                                {{ $at->data_inicio?->format('d/m/Y') }}
                                @if ($at->data_inicio?->format('Y-m-d') !== $at->data_fim?->format('Y-m-d'))
                                    a {{ $at->data_fim?->format('d/m/Y') }}
                                @endif
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-center">
                                <x-badge variant="blue">{{ $at->dias_afastamento }}</x-badge>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">
                                @if ($at->medico_nome)
                                    <div class="truncate max-w-xs">Dr. {{ $at->medico_nome }}</div>
                                @endif
                                @if ($at->cid)
                                    <div class="text-xs text-gray-400 font-mono">CID {{ $at->cid }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                @if ($at->status === 'pendente')
                                    <x-badge variant="yellow" icon="clock">Pendente</x-badge>
                                @elseif ($at->status === 'aprovado')
                                    <x-badge variant="green" icon="circle-check">Aprovado</x-badge>
                                    @if ($at->aprovadoPor)
                                        <p class="text-xs text-gray-500 mt-0.5">por {{ $at->aprovadoPor->name }}</p>
                                    @endif
                                @else
                                    <x-badge variant="red" icon="circle-x">Rejeitado</x-badge>
                                    @if ($at->motivo_rejeicao)
                                        <p class="text-xs text-gray-500 mt-0.5 max-w-xs truncate" title="{{ $at->motivo_rejeicao }}">{{ $at->motivo_rejeicao }}</p>
                                    @endif
                                @endif
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                @if ($at->arquivo_path)
                                    <a href="{{ route('documentos.atestado', $at->id) }}" target="_blank"
                                       class="text-gray-500 hover:text-gray-700" title="Ver atestado">
                                        <i class="ti ti-file-text text-lg" aria-hidden="true"></i>
                                    </a>
                                @endif
                                @if ($at->status === 'pendente')
                                    @can('approve', $at)
                                        <button wire:click="abrirAprovacao({{ $at->id }}, 'aprovar')"
                                                class="text-green-600 hover:text-green-900" title="Aprovar">
                                            <i class="ti ti-check text-lg" aria-hidden="true"></i>
                                        </button>
                                    @endcan
                                    @can('reject', $at)
                                        <button wire:click="abrirAprovacao({{ $at->id }}, 'rejeitar')"
                                                class="text-red-600 hover:text-red-900" title="Rejeitar">
                                            <i class="ti ti-x text-lg" aria-hidden="true"></i>
                                        </button>
                                    @endcan
                                    @can('update', $at)
                                        <button wire:click="openEdit({{ $at->id }})" class="text-indigo-600 hover:text-indigo-900">
                                            <i class="ti ti-edit" aria-hidden="true"></i>
                                        </button>
                                    @endcan
                                @endif
                                @can('delete', $at)
                                    <button wire:click="confirmDelete({{ $at->id }})" class="text-red-600 hover:text-red-900">
                                        <i class="ti ti-trash" aria-hidden="true"></i>
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <i class="ti ti-file-medical-off text-4xl text-gray-300 mb-2 block" aria-hidden="true"></i>
                                <p class="text-sm text-gray-500">Nenhum atestado encontrado.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($atestados->hasPages())
            <div class="px-6 py-3 border-t border-gray-200">{{ $atestados->links() }}</div>
        @endif
    </x-card>

    {{-- Modal cadastro/edição --}}
    <x-modal name="showModal" max-width="2xl" :title="$editando ? 'Editar atestado' : 'Registrar atestado'">
        <form wire:submit.prevent="save" enctype="multipart/form-data">
            <div class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-select label="Colaborador" name="colaborador_id" wire:model="colaborador_id" required>
                        <option value="">Selecione</option>
                        @foreach ($colaboradores as $col)
                            <option value="{{ $col->id }}">{{ $col->nome }}</option>
                        @endforeach
                    </x-select>
                    <x-select label="Tipo" name="tipo" wire:model="tipo" required>
                        <option value="medico">Médico</option>
                        <option value="odontologico">Odontológico</option>
                        <option value="acompanhante">Acompanhante</option>
                        <option value="declaracao_comparecimento">Declaração de comparecimento</option>
                    </x-select>
                    <x-input label="Data início" name="data_inicio" type="date" wire:model="data_inicio" required />
                    <x-input label="Data fim" name="data_fim" type="date" wire:model="data_fim" required />
                    <x-input label="CID-10" name="cid" wire:model="cid" placeholder="Ex: J11" hint="Opcional (dado sensível)" />
                    <x-input label="Nome do médico" name="medico_nome" wire:model="medico_nome" placeholder="Dr. Silva" />
                    <x-input label="CRM" name="medico_crm" wire:model="medico_crm" />
                    <x-input label="UF do CRM" name="medico_crm_uf" wire:model="medico_crm_uf" placeholder="MG" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Atestado físico (PDF/imagem) {!! $editando ? '' : '<span class="text-red-500">*</span>' !!}
                    </label>
                    <input type="file" wire:model="arquivo" accept="application/pdf,image/jpeg,image/png"
                           class="block w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700">
                    <p class="mt-1 text-xs text-gray-500">PDF, JPG ou PNG até 10MB</p>
                    @error('arquivo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    @if ($arquivo)
                        <p class="mt-1 text-xs text-green-700"><i class="ti ti-check"></i> {{ $arquivo->getClientOriginalName() }}</p>
                    @endif
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

    {{-- Modal aprovação/rejeição --}}
    <x-modal name="showApprovalModal" max-width="md" :title="$approvalAction === 'aprovar' ? 'Aprovar atestado' : 'Rejeitar atestado'">
        <div class="px-6 py-4">
            @if ($approvalAction === 'aprovar')
                <div class="flex items-start">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                        <i class="ti ti-circle-check text-green-600 text-xl" aria-hidden="true"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-700">Confirma a aprovação deste atestado?</p>
                        <p class="mt-1 text-xs text-gray-500">Após aprovação, o registro não poderá mais ser editado.</p>
                    </div>
                </div>
            @else
                <div class="flex items-start">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                        <i class="ti ti-circle-x text-red-600 text-xl" aria-hidden="true"></i>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-sm text-gray-700 mb-3">Informe o motivo da rejeição:</p>
                        <textarea wire:model="motivoRejeicao" rows="3"
                                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                  placeholder="Ex: Documento ilegível, atestado vencido, CRM inválido..."></textarea>
                        @error('motivoRejeicao') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            @endif
        </div>
        <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 flex justify-end space-x-2">
            <x-button variant="secondary" wire:click="$set('showApprovalModal', false)">Cancelar</x-button>
            <x-button :variant="$approvalAction === 'aprovar' ? 'success' : 'danger'" wire:click="confirmarAprovacao">
                {{ $approvalAction === 'aprovar' ? 'Sim, aprovar' : 'Sim, rejeitar' }}
            </x-button>
        </div>
    </x-modal>

    <x-modal name="showDeleteModal" max-width="md" title="Confirmar exclusão">
        <div class="px-6 py-4">
            <p class="text-sm text-gray-700">Excluir este atestado?</p>
        </div>
        <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 flex justify-end space-x-2">
            <x-button variant="secondary" wire:click="$set('showDeleteModal', false)">Cancelar</x-button>
            <x-button variant="danger" wire:click="delete">Sim, excluir</x-button>
        </div>
    </x-modal>
</div>
