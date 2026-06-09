<div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full">

    <div class="sm:flex sm:items-center sm:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Veículos</h1>
            <p class="mt-1 text-sm text-gray-600">Frota da prefeitura: cadastro, documentação e responsáveis.</p>
        </div>
        <div class="mt-4 sm:mt-0 flex gap-2">
            <a href="{{ route('manutencoes.index') }}" wire:navigate.hover>
                <x-button variant="secondary" icon="tool">Manutenções</x-button>
            </a>
            @can('viewAny', App\Models\Veiculo::class)
                <x-button variant="secondary" icon="file-spreadsheet" wire:click="exportar">Exportar</x-button>
            @endcan
            @can('create', App\Models\Veiculo::class)
                <x-button wire:click="openCreate" icon="plus">Novo veículo</x-button>
            @endcan
        </div>
    </div>

    {{-- Cards de resumo --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-md bg-indigo-50 flex items-center justify-center">
                    <i class="ti ti-car text-indigo-600 text-xl" aria-hidden="true"></i>
                </div>
                <div class="ml-3">
                    <p class="text-xs text-gray-500">Frota total</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['total'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-md bg-yellow-50 flex items-center justify-center">
                    <i class="ti ti-tool text-yellow-600 text-xl" aria-hidden="true"></i>
                </div>
                <div class="ml-3">
                    <p class="text-xs text-gray-500">Em manutenção</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['em_manutencao'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-md bg-orange-50 flex items-center justify-center">
                    <i class="ti ti-license text-orange-600 text-xl" aria-hidden="true"></i>
                </div>
                <div class="ml-3">
                    <p class="text-xs text-gray-500">Licenciamento ≤ 30d</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['licenc_proximo'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-md bg-red-50 flex items-center justify-center">
                    <i class="ti ti-shield-check text-red-600 text-xl" aria-hidden="true"></i>
                </div>
                <div class="ml-3">
                    <p class="text-xs text-gray-500">Seguro ≤ 30d</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['seguro_proximo'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <x-card class="mb-4" padding="p-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-input label="Buscar" wire:model.live.debounce.400ms="search" placeholder="Marca, modelo ou placa..." />
            <x-select label="Status" wire:model.live="filterStatus">
                <option value="">Todos</option>
                <option value="disponivel">Disponível</option>
                <option value="em_uso">Em uso</option>
                <option value="em_manutencao">Em manutenção</option>
                <option value="inativo">Inativo</option>
                <option value="vendido">Vendido</option>
            </x-select>
            <x-select label="Categoria" wire:model.live="filterCategoria">
                <option value="">Todas</option>
                <option value="passeio">Passeio</option>
                <option value="utilitario">Utilitário</option>
                <option value="caminhao">Caminhão</option>
                <option value="moto">Moto</option>
                <option value="onibus">Ônibus</option>
                <option value="maquina_pesada">Máquina pesada</option>
                <option value="outro">Outro</option>
            </x-select>
        </div>

        {{-- Filtros de vencimento (item 3) --}}
        <div class="mt-4 pt-4 border-t border-gray-200">
            <div class="flex flex-wrap items-end gap-2">
                <div>
                    <p class="text-xs text-gray-500 mb-1">Filtros rápidos de vencimento:</p>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" wire:click="filtrarLicenciamentoVencendo"
                                class="px-3 py-1.5 text-xs rounded-md border
                                       {{ $filterVencimento === 'licenc_vencendo' ? 'bg-orange-100 border-orange-400 text-orange-800' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50' }}">
                            <i class="ti ti-license mr-1" aria-hidden="true"></i>
                            Licenciamento vencendo
                        </button>
                        <button type="button" wire:click="filtrarSeguroVencendo"
                                class="px-3 py-1.5 text-xs rounded-md border
                                       {{ $filterVencimento === 'seguro_vencendo' ? 'bg-orange-100 border-orange-400 text-orange-800' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50' }}">
                            <i class="ti ti-shield-check mr-1" aria-hidden="true"></i>
                            Seguro vencendo
                        </button>
                        <button type="button" wire:click="filtrarQualquerVencendo"
                                class="px-3 py-1.5 text-xs rounded-md border
                                       {{ $filterVencimento === 'qualquer_vencendo' ? 'bg-orange-100 border-orange-400 text-orange-800' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50' }}">
                            <i class="ti ti-alert-triangle mr-1" aria-hidden="true"></i>
                            Qualquer vencendo
                        </button>
                        <button type="button" wire:click="filtrarVencidos"
                                class="px-3 py-1.5 text-xs rounded-md border
                                       {{ $filterVencimento === 'qualquer_vencido' ? 'bg-red-100 border-red-400 text-red-800' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50' }}">
                            <i class="ti ti-alert-octagon mr-1" aria-hidden="true"></i>
                            Já vencidos
                        </button>
                        @if ($search || $filterStatus || $filterCategoria || $filterVencimento)
                            <button type="button" wire:click="limparFiltros"
                                    class="px-3 py-1.5 text-xs rounded-md border bg-gray-100 border-gray-300 text-gray-700 hover:bg-gray-200">
                                <i class="ti ti-x mr-1" aria-hidden="true"></i>
                                Limpar filtros
                            </button>
                        @endif
                    </div>
                </div>

                @if (in_array($filterVencimento, ['licenc_vencendo', 'seguro_vencendo', 'qualquer_vencendo'], true))
                    <div class="ml-auto">
                        <label class="block text-xs text-gray-500 mb-1">Dias até vencimento:</label>
                        <div class="flex items-center space-x-2">
                            <input type="number" min="1" max="365" wire:model.live.debounce.500ms="filterDias"
                                   class="w-20 px-2 py-1.5 text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            <span class="text-xs text-gray-500">dias</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </x-card>

    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Veículo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Placa</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Responsável</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">KM</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Documentação</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($veiculos as $v)
                        <tr wire:key="vei-{{ $v->id }}" class="hover:bg-gray-50">
                            <td class="px-6 py-3">
                                <div class="text-sm font-medium text-gray-900">{{ $v->marca }} {{ $v->modelo }}</div>
                                <div class="text-xs text-gray-500">
                                    @if ($v->ano_modelo) {{ $v->ano_fabricacao }}/{{ $v->ano_modelo }} @endif
                                    @if ($v->cor) <span class="text-gray-400">·</span> {{ $v->cor }} @endif
                                </div>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900 font-mono">{{ $v->placa_formatada }}</td>
                            <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">
                                {{ $v->responsavel?->nome ?? '—' }}
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-right text-sm text-gray-900">
                                {{ number_format($v->km_atual, 0, ',', '.') }} km
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-xs">
                                @if ($v->licenciamento_vencimento)
                                    <div class="{{ $v->licenciamento_vencido ? 'text-red-600 font-medium' : 'text-gray-600' }}">
                                        <i class="ti ti-license mr-1" aria-hidden="true"></i>
                                        Lic: {{ $v->licenciamento_vencimento->format('d/m/Y') }}
                                    </div>
                                @endif
                                @if ($v->seguro_vencimento)
                                    <div class="{{ $v->seguro_vencido ? 'text-red-600 font-medium' : 'text-gray-600' }}">
                                        <i class="ti ti-shield-check mr-1" aria-hidden="true"></i>
                                        Seg: {{ $v->seguro_vencimento->format('d/m/Y') }}
                                    </div>
                                @endif
                                @if (!$v->licenciamento_vencimento && !$v->seguro_vencimento)
                                    <span class="text-gray-400 italic">não informado</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                <x-badge :variant="$v->status_cor">{{ $v->status_label }}</x-badge>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                <a href="{{ route('manutencoes.index', ['veiculo' => $v->id]) }}" wire:navigate.hover
                                   class="text-gray-500 hover:text-gray-700" title="Histórico de manutenções">
                                    <i class="ti ti-tool" aria-hidden="true"></i>
                                </a>
                                @can('update', $v)
                                    <button wire:click="openEdit({{ $v->id }})" class="text-indigo-600 hover:text-indigo-900" title="Editar">
                                        <i class="ti ti-edit" aria-hidden="true"></i>
                                    </button>
                                @endcan
                                @can('delete', $v)
                                    <button wire:click="confirmDelete({{ $v->id }})" class="text-red-600 hover:text-red-900" title="Excluir">
                                        <i class="ti ti-trash" aria-hidden="true"></i>
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <i class="ti ti-car-off text-4xl text-gray-300 mb-2 block" aria-hidden="true"></i>
                                <p class="text-sm text-gray-500">Nenhum veículo cadastrado.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($veiculos->hasPages())
            <div class="px-6 py-3 border-t border-gray-200">{{ $veiculos->links() }}</div>
        @endif
    </x-card>

    {{-- Modal de cadastro/edição --}}
    <x-modal name="showModal" max-width="3xl" :title="$editando ? 'Editar veículo' : 'Novo veículo'">
        <form wire:submit.prevent="save">
            <div class="px-6 py-4 space-y-5 max-h-[70vh] overflow-y-auto">

                <div>
                    <h3 class="text-sm font-medium text-gray-900 mb-3">Identificação</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <x-input label="Placa" name="placa" wire:model.blur="placa" required placeholder="ABC-1234 ou ABC1D23" />
                        <x-input label="Renavam" name="renavam" wire:model="renavam" />
                        <x-input label="Chassi" name="chassi" wire:model="chassi" maxlength="17" hint="17 caracteres" />
                        <x-input label="Marca" name="marca" wire:model="marca" required />
                        <x-input label="Modelo" name="modelo" wire:model="modelo" required />
                        <x-input label="Cor" name="cor" wire:model="cor" />
                        <x-input label="Ano fabricação" name="ano_fabricacao" type="number" min="1950" wire:model="ano_fabricacao" />
                        <x-input label="Ano modelo" name="ano_modelo" type="number" min="1950" wire:model="ano_modelo" />
                        <x-select label="Categoria" name="categoria" wire:model="categoria">
                            <option value="">Selecione</option>
                            <option value="passeio">Passeio</option>
                            <option value="utilitario">Utilitário</option>
                            <option value="caminhao">Caminhão</option>
                            <option value="moto">Moto</option>
                            <option value="onibus">Ônibus</option>
                            <option value="maquina_pesada">Máquina pesada</option>
                            <option value="outro">Outro</option>
                        </x-select>
                        <x-select label="Combustível" name="combustivel" wire:model="combustivel">
                            <option value="">Selecione</option>
                            <option value="gasolina">Gasolina</option>
                            <option value="etanol">Etanol</option>
                            <option value="flex">Flex</option>
                            <option value="diesel">Diesel</option>
                            <option value="eletrico">Elétrico</option>
                            <option value="hibrido">Híbrido</option>
                            <option value="gnv">GNV</option>
                        </x-select>
                        <x-input label="Quilometragem atual" name="km_atual" type="number" min="0" wire:model="km_atual" required />
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-gray-900 mb-3">Aquisição</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-input label="Data de aquisição" name="data_aquisicao" type="date" wire:model="data_aquisicao" />
                        <x-input label="Valor de aquisição (R$)" name="valor_aquisicao" type="number" step="0.01" min="0" wire:model="valor_aquisicao" />
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-gray-900 mb-3">Operação</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-select label="Status" name="status" wire:model="status" required>
                            <option value="disponivel">Disponível</option>
                            <option value="em_uso">Em uso</option>
                            <option value="em_manutencao">Em manutenção</option>
                            <option value="inativo">Inativo</option>
                            <option value="vendido">Vendido</option>
                        </x-select>
                        <x-select label="Responsável" name="responsavel_id" wire:model="responsavel_id">
                            <option value="">Sem responsável</option>
                            @foreach ($colaboradores as $col)
                                <option value="{{ $col->id }}">{{ $col->nome }}</option>
                            @endforeach
                        </x-select>
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-gray-900 mb-3">Documentação</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-input label="Vencimento do licenciamento" name="licenciamento_vencimento" type="date" wire:model="licenciamento_vencimento" />
                        <x-input label="Vencimento do seguro" name="seguro_vencimento" type="date" wire:model="seguro_vencimento" />
                        <x-input label="Seguradora" name="seguradora" wire:model="seguradora" />
                        <x-input label="Nº da apólice" name="apolice" wire:model="apolice" />
                    </div>

                    {{-- Upload de documento PDF (CRLV) --}}
                    <div class="mt-4 border-t border-gray-200 pt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Documento do veículo (PDF do CRLV)
                        </label>

                        {{-- Estado: ja tem documento salvo --}}
                        @if ($documento_path_atual)
                            <div class="flex items-center justify-between bg-gray-50 border border-gray-200 rounded-md p-3 mb-2">
                                <div class="flex items-center min-w-0">
                                    <i class="ti ti-file-type-pdf text-red-600 text-xl mr-2 flex-shrink-0" aria-hidden="true"></i>
                                    <span class="text-sm text-gray-700 truncate">{{ $documento_nome_atual ?: 'documento.pdf' }}</span>
                                </div>
                                <div class="flex items-center space-x-2 ml-3 flex-shrink-0">
                                    @if ($editingId)
                                        <a href="{{ route('documentos.veiculo', ['id' => $editingId, 'modo' => 'view']) }}"
                                           target="_blank" rel="noopener"
                                           class="text-xs text-indigo-600 hover:text-indigo-800 inline-flex items-center">
                                            <i class="ti ti-eye mr-1" aria-hidden="true"></i> Ver
                                        </a>
                                        <a href="{{ route('documentos.veiculo', ['id' => $editingId, 'modo' => 'download']) }}"
                                           class="text-xs text-indigo-600 hover:text-indigo-800 inline-flex items-center">
                                            <i class="ti ti-download mr-1" aria-hidden="true"></i> Baixar
                                        </a>
                                        <button type="button" wire:click="removerDocumento"
                                                wire:confirm="Remover o documento atual?"
                                                class="text-xs text-red-600 hover:text-red-800 inline-flex items-center">
                                            <i class="ti ti-trash mr-1" aria-hidden="true"></i> Remover
                                        </button>
                                    @endif
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 mb-2">Anexe um novo PDF abaixo para substituir o atual.</p>
                        @endif

                        <input type="file" wire:model="documento" accept="application/pdf"
                               class="block w-full text-sm text-gray-700
                                      file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0
                                      file:text-sm file:font-medium
                                      file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                        <p class="mt-1 text-xs text-gray-500">Apenas PDF, até 5MB.</p>

                        @error('documento')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror

                        <div wire:loading wire:target="documento" class="text-xs text-gray-500 mt-1">
                            Enviando arquivo...
                        </div>
                    </div>
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
            <p class="text-sm text-gray-700">Desativar o veículo <strong>{{ $deletingName }}</strong>?</p>
            <p class="text-xs text-gray-500 mt-2">O histórico de manutenções será preservado.</p>
        </div>
        <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 flex justify-end space-x-2">
            <x-button variant="secondary" wire:click="$set('showDeleteModal', false)">Cancelar</x-button>
            <x-button variant="danger" wire:click="delete">Sim, desativar</x-button>
        </div>
    </x-modal>
</div>
