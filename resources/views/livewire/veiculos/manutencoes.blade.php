<div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full">

    <div class="sm:flex sm:items-center sm:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Manutenções de Veículos</h1>
            <p class="mt-1 text-sm text-gray-600">
                @if ($veiculoFiltrado)
                    Histórico de <strong>{{ $veiculoFiltrado->marca }} {{ $veiculoFiltrado->modelo }}</strong>
                    <span class="font-mono text-gray-500">({{ $veiculoFiltrado->placa }})</span>
                @else
                    Histórico de manutenções de toda a frota.
                @endif
            </p>
        </div>
        <div class="mt-4 sm:mt-0 flex gap-2">
            <a href="{{ route('veiculos.index') }}" wire:navigate.hover>
                <x-button variant="secondary" icon="car">Veículos</x-button>
            </a>
            @can('viewAny', App\Models\VeiculoManutencao::class)
                <x-button variant="secondary" icon="file-spreadsheet" wire:click="exportar">Exportar</x-button>
            @endcan
            @can('create', App\Models\VeiculoManutencao::class)
                <x-button wire:click="openCreate" icon="plus">Registrar manutenção</x-button>
            @endcan
        </div>
    </div>

    <x-card class="mb-4" padding="p-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-input label="Buscar na descrição" wire:model.live.debounce.400ms="search" placeholder="O que foi feito..." />
            <x-select label="Veículo" wire:model.live="filterVeiculoId">
                <option value="">Todos</option>
                @foreach ($veiculos as $v)
                    <option value="{{ $v->id }}">{{ $v->placa }} — {{ $v->marca }} {{ $v->modelo }}</option>
                @endforeach
            </x-select>
            <x-select label="Tipo" wire:model.live="filterTipo">
                <option value="">Todos</option>
                @foreach ($tipos as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </x-select>
        </div>
    </x-card>

    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Veículo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Descrição</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Oficina</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Valor</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($manutencoes as $m)
                        <tr wire:key="man-{{ $m->id }}" class="hover:bg-gray-50">
                            <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900">
                                {{ $m->data_manutencao?->format('d/m/Y') }}
                                @if ($m->km_no_momento)
                                    <div class="text-xs text-gray-400">{{ number_format($m->km_no_momento, 0, ',', '.') }} km</div>
                                @endif
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-sm">
                                <div class="text-gray-900 font-mono text-xs">{{ $m->veiculo?->placa }}</div>
                                <div class="text-gray-500 text-xs">{{ $m->veiculo?->marca }} {{ $m->veiculo?->modelo }}</div>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                <x-badge variant="indigo">{{ $m->tipo_label }}</x-badge>
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-700">
                                <div class="max-w-xs truncate" title="{{ $m->descricao }}">{{ $m->descricao }}</div>
                                @if ($m->proxima_manutencao_data || $m->proxima_manutencao_km)
                                    <div class="text-xs text-blue-600 mt-1">
                                        <i class="ti ti-calendar-event mr-1" aria-hidden="true"></i>
                                        Próxima:
                                        @if ($m->proxima_manutencao_data) {{ $m->proxima_manutencao_data->format('d/m/Y') }} @endif
                                        @if ($m->proxima_manutencao_km) ou {{ number_format($m->proxima_manutencao_km, 0, ',', '.') }} km @endif
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">
                                {{ $m->fornecedor?->nome_fantasia ?: $m->fornecedor?->razao_social ?? '—' }}
                                @if ($m->nota_fiscal)
                                    <div class="text-xs text-gray-400">NF {{ $m->nota_fiscal }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-right text-sm text-gray-900">
                                @if ($m->valor !== null)
                                    R$ {{ number_format((float) $m->valor, 2, ',', '.') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                @if ($m->comprovante_path)
                                    <a href="{{ route('documentos.manutencao', ['id' => $m->id, 'modo' => 'view']) }}"
                                       target="_blank" rel="noopener"
                                       class="text-blue-600 hover:text-blue-900" title="Ver comprovante">
                                        <i class="ti ti-paperclip" aria-hidden="true"></i>
                                    </a>
                                @endif
                                @can('update', $m)
                                    <button wire:click="openEdit({{ $m->id }})" class="text-indigo-600 hover:text-indigo-900" title="Editar">
                                        <i class="ti ti-edit" aria-hidden="true"></i>
                                    </button>
                                @endcan
                                @can('delete', $m)
                                    <button wire:click="confirmDelete({{ $m->id }})" class="text-red-600 hover:text-red-900" title="Excluir">
                                        <i class="ti ti-trash" aria-hidden="true"></i>
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <i class="ti ti-tool-off text-4xl text-gray-300 mb-2 block" aria-hidden="true"></i>
                                <p class="text-sm text-gray-500">Nenhuma manutenção registrada.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($manutencoes->hasPages())
            <div class="px-6 py-3 border-t border-gray-200">{{ $manutencoes->links() }}</div>
        @endif
    </x-card>

    <x-modal name="showModal" max-width="3xl" :title="$editando ? 'Editar manutenção' : 'Registrar manutenção'">
        <form wire:submit.prevent="save">
            <div class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-select label="Veículo" name="veiculo_id" wire:model.live="veiculo_id" required>
                        <option value="">Selecione</option>
                        @foreach ($veiculos as $v)
                            <option value="{{ $v->id }}">{{ $v->placa }} — {{ $v->marca }} {{ $v->modelo }}</option>
                        @endforeach
                    </x-select>
                    <x-select label="Tipo" name="tipo" wire:model="tipo" required>
                        <option value="">Selecione</option>
                        @foreach ($tipos as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </x-select>
                    <x-input label="Data da manutenção" name="data_manutencao" type="date" wire:model="data_manutencao" required />
                    <x-input label="KM no momento" name="km_no_momento" type="number" min="0" wire:model="km_no_momento"
                             hint="Atualizará o KM do veículo se for maior que o atual" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descrição da manutenção *</label>
                    <textarea wire:model="descricao" rows="2" required
                              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                              placeholder="O que motivou esta manutenção"></textarea>
                    @error('descricao') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Serviços realizados</label>
                    <textarea wire:model="servicos_realizados" rows="3"
                              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                              placeholder="Liste o que foi feito (peças trocadas, ajustes, etc)"></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <x-select label="Oficina (fornecedor)" name="fornecedor_id" wire:model="fornecedor_id">
                        <option value="">Sem fornecedor</option>
                        @foreach ($fornecedores as $f)
                            <option value="{{ $f->id }}">{{ $f->nome_fantasia ?: $f->razao_social }}</option>
                        @endforeach
                    </x-select>
                    <x-input label="Valor (R$)" name="valor" type="number" step="0.01" min="0" wire:model="valor" />
                    <x-input label="Nota fiscal" name="nota_fiscal" wire:model="nota_fiscal" />
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-md p-3">
                    <p class="text-xs font-medium text-blue-900 mb-2">
                        <i class="ti ti-calendar-event mr-1" aria-hidden="true"></i>
                        Próxima manutenção prevista (opcional)
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <x-input label="Data" name="proxima_manutencao_data" type="date" wire:model="proxima_manutencao_data" />
                        <x-input label="Ou na KM" name="proxima_manutencao_km" type="number" min="0" wire:model="proxima_manutencao_km" />
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Comprovante (NF, recibo)</label>

                    {{-- Comprovante ja anexado (so em modo edicao) --}}
                    @if ($editando && $editingId)
                        @php
                            $manutencaoAtual = \App\Models\VeiculoManutencao::find($editingId);
                        @endphp
                        @if ($manutencaoAtual && $manutencaoAtual->comprovante_path)
                            <div class="flex items-center justify-between bg-gray-50 border border-gray-200 rounded-md p-3 mb-2">
                                <div class="flex items-center min-w-0">
                                    <i class="ti ti-paperclip text-blue-600 text-xl mr-2 flex-shrink-0" aria-hidden="true"></i>
                                    <span class="text-sm text-gray-700 truncate">{{ $manutencaoAtual->comprovante_nome_original ?: 'comprovante anexado' }}</span>
                                </div>
                                <div class="flex items-center space-x-2 ml-3 flex-shrink-0">
                                    <a href="{{ route('documentos.manutencao', ['id' => $editingId, 'modo' => 'view']) }}"
                                       target="_blank" rel="noopener"
                                       class="text-xs text-indigo-600 hover:text-indigo-800 inline-flex items-center">
                                        <i class="ti ti-eye mr-1" aria-hidden="true"></i> Ver
                                    </a>
                                    <a href="{{ route('documentos.manutencao', ['id' => $editingId, 'modo' => 'download']) }}"
                                       class="text-xs text-indigo-600 hover:text-indigo-800 inline-flex items-center">
                                        <i class="ti ti-download mr-1" aria-hidden="true"></i> Baixar
                                    </a>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 mb-2">Anexe um novo arquivo abaixo para substituir o atual.</p>
                        @endif
                    @endif

                    <input type="file" wire:model="comprovante" accept=".pdf,.jpg,.jpeg,.png"
                           class="block w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    <p class="mt-1 text-xs text-gray-500">PDF, JPG ou PNG, até 5 MB</p>
                    @error('comprovante') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
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
            <p class="text-sm text-gray-700">Excluir este registro de manutenção?</p>
        </div>
        <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 flex justify-end space-x-2">
            <x-button variant="secondary" wire:click="$set('showDeleteModal', false)">Cancelar</x-button>
            <x-button variant="danger" wire:click="delete">Sim, excluir</x-button>
        </div>
    </x-modal>
</div>
