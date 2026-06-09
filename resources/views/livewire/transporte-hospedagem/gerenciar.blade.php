<div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full">

    <div class="sm:flex sm:items-center sm:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Transporte e Hospedagem</h1>
            <p class="mt-1 text-sm text-gray-600">Logística de viagens a serviço.</p>
        </div>
        @can('create', App\Models\TransporteHospedagem::class)
            <div class="mt-4 sm:mt-0">
                <x-button wire:click="openCreate" icon="plus">Novo registro</x-button>
            </div>
        @endcan
    </div>

    <div class="grid grid-cols-2 gap-4 mb-4">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-md bg-green-50 flex items-center justify-center">
                    <i class="ti ti-route text-green-600 text-xl" aria-hidden="true"></i>
                </div>
                <div class="ml-3">
                    <p class="text-xs text-gray-500">Em andamento</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['em_andamento'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-md bg-blue-50 flex items-center justify-center">
                    <i class="ti ti-calendar-event text-blue-600 text-xl" aria-hidden="true"></i>
                </div>
                <div class="ml-3">
                    <p class="text-xs text-gray-500">Agendados</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['futuros'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <x-card class="mb-4" padding="p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <x-input label="Buscar" wire:model.live.debounce.400ms="search" placeholder="Origem, destino, colaborador..." />
            <x-select label="Tipo" wire:model.live="filterTipo">
                <option value="">Todos</option>
                @foreach ($tipos as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </x-select>
            <x-select label="Colaborador" wire:model.live="filterColaboradorId">
                <option value="">Todos</option>
                @foreach ($colaboradores as $c)
                    <option value="{{ $c->id }}">{{ $c->nome }}</option>
                @endforeach
            </x-select>
            <x-select label="Obra" wire:model.live="filterObraId">
                <option value="">Todas</option>
                @foreach ($obras as $o)
                    <option value="{{ $o->id }}">{{ $o->nome }}</option>
                @endforeach
            </x-select>
        </div>
    </x-card>

    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Período</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Colaborador</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Detalhes</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Obra</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Valor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($registros as $r)
                        <tr wire:key="th-{{ $r->id }}" class="hover:bg-gray-50">
                            <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900">
                                {{ $r->data_inicio?->format('d/m/Y') }}
                                @if ($r->data_fim)
                                    <div class="text-xs text-gray-400">até {{ $r->data_fim->format('d/m/Y') }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900">
                                {{ $r->colaborador?->nome ?? '—' }}
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                <x-badge :variant="$r->tipo_cor">{{ $r->tipo_label }}</x-badge>
                            </td>
                            <td class="px-6 py-3 text-xs text-gray-700">
                                @if ($r->temTransporte())
                                    <div class="flex items-center">
                                        <i class="ti ti-route text-gray-400 mr-1" aria-hidden="true"></i>
                                        {{ $r->origem }} → {{ $r->destino }}
                                        @if ($r->meio_transporte)
                                            <span class="ml-1 text-gray-400">({{ $r->meio_transporte_label }})</span>
                                        @endif
                                    </div>
                                @endif
                                @if ($r->temHospedagem())
                                    <div class="flex items-center {{ $r->temTransporte() ? 'mt-1' : '' }}">
                                        <i class="ti ti-bed text-gray-400 mr-1" aria-hidden="true"></i>
                                        {{ $r->hospedagem_local }}
                                        @if ($r->hospedagemCidade)
                                            <span class="ml-1 text-gray-400">— {{ $r->hospedagemCidade->nome }}/{{ $r->hospedagemCidade->estado?->uf ?? '?' }}</span>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-600">{{ $r->obra?->nome ?? '—' }}</td>
                            <td class="px-6 py-3 whitespace-nowrap text-right text-sm text-gray-900">
                                @if ($r->valor !== null)
                                    R$ {{ number_format((float) $r->valor, 2, ',', '.') }}
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                <x-badge :variant="$r->status_temporal_cor">{{ $r->status_temporal_label }}</x-badge>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                @can('update', $r)
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
                            <td colspan="8" class="px-6 py-12 text-center">
                                <i class="ti ti-route-off text-4xl text-gray-300 mb-2 block" aria-hidden="true"></i>
                                <p class="text-sm text-gray-500">Nenhum registro encontrado.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($registros->hasPages())
            <div class="px-6 py-3 border-t border-gray-200">{{ $registros->links() }}</div>
        @endif
    </x-card>

    {{-- Modal --}}
    <x-modal name="showModal" max-width="3xl" :title="$editando ? 'Editar registro' : 'Novo registro'">
        <form wire:submit.prevent="save">
            <div class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <x-select label="Tipo" name="tipo" wire:model.live="tipo" required>
                        @foreach ($tipos as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </x-select>
                    <x-select label="Colaborador" name="colaborador_id" wire:model="colaborador_id" required>
                        <option value="">Selecione</option>
                        @foreach ($colaboradores as $c)
                            <option value="{{ $c->id }}">{{ $c->nome }}</option>
                        @endforeach
                    </x-select>
                    <x-select label="Obra" name="obra_id" wire:model="obra_id">
                        <option value="">Sem vínculo com obra</option>
                        @foreach ($obras as $o)
                            <option value="{{ $o->id }}">{{ $o->nome }}</option>
                        @endforeach
                    </x-select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-input label="Data início" name="data_inicio" type="date" wire:model="data_inicio" required />
                    <x-input label="Data fim" name="data_fim" type="date" wire:model="data_fim" hint="Vazio = aberto/indeterminado" />
                </div>

                @if (in_array($tipo, ['transporte', 'ambos']))
                    <div class="bg-blue-50 border border-blue-200 rounded-md p-3">
                        <p class="text-xs font-medium text-blue-900 mb-2">
                            <i class="ti ti-route mr-1" aria-hidden="true"></i> Transporte
                        </p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <x-input label="Origem" name="origem" wire:model="origem" />
                            <x-input label="Destino" name="destino" wire:model="destino" />
                        </div>
                        <div class="mt-3">
                            <x-select label="Meio de transporte" name="meio_transporte" wire:model="meio_transporte">
                                <option value="">Selecione</option>
                                @foreach ($meiosTransporte as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </x-select>
                        </div>
                    </div>
                @endif

                @if (in_array($tipo, ['hospedagem', 'ambos']))
                    <div class="bg-purple-50 border border-purple-200 rounded-md p-3">
                        <p class="text-xs font-medium text-purple-900 mb-2">
                            <i class="ti ti-bed mr-1" aria-hidden="true"></i> Hospedagem
                        </p>
                        <x-input label="Local (hotel, pousada, etc)" name="hospedagem_local" wire:model="hospedagem_local" />
                        <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3">
                            <x-input label="Endereço" name="hospedagem_endereco" wire:model="hospedagem_endereco" />
                            <x-select label="Cidade" name="hospedagem_cidade_id" wire:model="hospedagem_cidade_id">
                                <option value="">— Selecionar —</option>
                                @foreach ($cidades as $cidade)
                                    <option value="{{ $cidade->id }}">{{ $cidade->nome }}/{{ $cidade->estado?->uf ?? '?' }}</option>
                                @endforeach
                            </x-select>
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-input label="Valor (R$)" name="valor" type="number" step="0.01" min="0" wire:model="valor" />
                    <x-select label="Fornecedor" name="fornecedor_id" wire:model="fornecedor_id">
                        <option value="">Sem fornecedor</option>
                        @foreach ($fornecedores as $f)
                            <option value="{{ $f->id }}">{{ $f->nome_fantasia ?: $f->razao_social }}</option>
                        @endforeach
                    </x-select>
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

    <x-modal name="showDeleteModal" max-width="md" title="Confirmar exclusão">
        <div class="px-6 py-4">
            <p class="text-sm text-gray-700">Excluir o registro <strong>{{ $deletingName }}</strong>?</p>
        </div>
        <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 flex justify-end space-x-2">
            <x-button variant="secondary" wire:click="$set('showDeleteModal', false)">Cancelar</x-button>
            <x-button variant="danger" wire:click="delete">Sim, excluir</x-button>
        </div>
    </x-modal>
</div>
