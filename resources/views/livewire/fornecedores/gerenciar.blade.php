<div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full">

    <div class="sm:flex sm:items-center sm:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Fornecedores</h1>
            <p class="mt-1 text-sm text-gray-600">Cadastro de fornecedores e prestadores de serviço.</p>
        </div>
        @can('create', App\Models\Fornecedor::class)
            <div class="mt-4 sm:mt-0">
                <x-button wire:click="openCreate" icon="plus">Novo fornecedor</x-button>
            </div>
        @endcan
    </div>

    <x-card class="mb-4" padding="p-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-input label="Buscar" wire:model.live.debounce.400ms="search" placeholder="Razão social, fantasia ou CNPJ/CPF..." />
            <x-select label="Tipo" wire:model.live="filterTipo">
                <option value="">Todos</option>
                <option value="juridica">Pessoa Jurídica</option>
                <option value="fisica">Pessoa Física</option>
            </x-select>
            <x-select label="Homologação" wire:model.live="filterHomologado">
                <option value="">Todos</option>
                <option value="sim">Homologados</option>
                <option value="nao">Não homologados</option>
            </x-select>
        </div>
    </x-card>

    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fornecedor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">CNPJ/CPF</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contato</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Avaliação</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Pedidos</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($fornecedores as $f)
                        <tr wire:key="forn-{{ $f->id }}" class="hover:bg-gray-50">
                            <td class="px-6 py-3">
                                <div class="text-sm font-medium text-gray-900">{{ $f->nome_exibicao }}</div>
                                @if ($f->nome_fantasia && $f->nome_fantasia !== $f->razao_social)
                                    <div class="text-xs text-gray-500">{{ $f->razao_social }}</div>
                                @endif
                                <x-badge variant="{{ $f->tipo_pessoa === 'juridica' ? 'blue' : 'purple' }}">
                                    {{ $f->tipo_pessoa === 'juridica' ? 'PJ' : 'PF' }}
                                </x-badge>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600 font-mono">
                                {{ $f->documento_formatado }}
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-600">
                                @if ($f->celular || $f->telefone)
                                    <div>{{ $f->celular ?: $f->telefone }}</div>
                                @endif
                                @if ($f->email)
                                    <div class="text-xs text-gray-400 truncate max-w-[180px]">{{ $f->email }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-center">
                                @if ($f->avaliacao)
                                    <div class="flex items-center justify-center text-amber-500">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <i class="ti ti-star{{ $i <= $f->avaliacao ? '-filled' : '' }} text-xs" aria-hidden="true"></i>
                                        @endfor
                                    </div>
                                @else
                                    <span class="text-gray-300 text-xs">sem avaliação</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-center">
                                <x-badge variant="{{ $f->pedidos_compra_count > 0 ? 'indigo' : 'gray' }}">
                                    {{ $f->pedidos_compra_count }}
                                </x-badge>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-center">
                                @can('update', $f)
                                    <button wire:click="toggleHomologacao({{ $f->id }})" title="Clique para alternar">
                                        @if ($f->homologado)
                                            <x-badge variant="green" icon="circle-check">Homologado</x-badge>
                                        @else
                                            <x-badge variant="gray">Pendente</x-badge>
                                        @endif
                                    </button>
                                @else
                                    @if ($f->homologado)
                                        <x-badge variant="green" icon="circle-check">Homologado</x-badge>
                                    @else
                                        <x-badge variant="gray">Pendente</x-badge>
                                    @endif
                                @endcan
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-medium space-x-3">
                                @can('update', $f)
                                    <button wire:click="openEdit({{ $f->id }})" class="text-indigo-600 hover:text-indigo-900">
                                        <i class="ti ti-edit" aria-hidden="true"></i>
                                    </button>
                                @endcan
                                @can('delete', $f)
                                    <button wire:click="confirmDelete({{ $f->id }})" class="text-red-600 hover:text-red-900">
                                        <i class="ti ti-trash" aria-hidden="true"></i>
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <i class="ti ti-truck-off text-4xl text-gray-300 mb-2 block" aria-hidden="true"></i>
                                <p class="text-sm text-gray-500">Nenhum fornecedor encontrado.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($fornecedores->hasPages())
            <div class="px-6 py-3 border-t border-gray-200">{{ $fornecedores->links() }}</div>
        @endif
    </x-card>

    {{-- Modal criar/editar --}}
    <x-modal name="showModal" max-width="3xl" :title="$editando ? 'Editar fornecedor' : 'Novo fornecedor'">
        <form wire:submit.prevent="save">
            <div class="px-6 py-4 space-y-5 max-h-[70vh] overflow-y-auto">

                {{-- Identificação --}}
                <div>
                    <h3 class="text-sm font-medium text-gray-900 mb-3">Identificação</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-select label="Tipo de pessoa" name="tipo_pessoa" wire:model.live="tipo_pessoa" required>
                            <option value="juridica">Pessoa Jurídica</option>
                            <option value="fisica">Pessoa Física</option>
                        </x-select>
                        <x-input label="{{ $tipo_pessoa === 'fisica' ? 'CPF' : 'CNPJ' }}" name="cnpj_cpf"
                                 wire:model.blur="cnpj_cpf" required
                                 placeholder="{{ $tipo_pessoa === 'fisica' ? '000.000.000-00' : '00.000.000/0000-00' }}" />
                        <div class="md:col-span-2">
                            <x-input label="{{ $tipo_pessoa === 'fisica' ? 'Nome completo' : 'Razão social' }}"
                                     name="razao_social" wire:model="razao_social" required />
                        </div>
                        @if ($tipo_pessoa === 'juridica')
                            <x-input label="Nome fantasia" name="nome_fantasia" wire:model="nome_fantasia" />
                            <x-input label="Inscrição estadual" name="inscricao_estadual" wire:model="inscricao_estadual" />
                            <x-input label="Inscrição municipal" name="inscricao_municipal" wire:model="inscricao_municipal" />
                        @endif
                    </div>
                </div>

                {{-- Contato --}}
                <div>
                    <h3 class="text-sm font-medium text-gray-900 mb-3">Contato</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-input label="Telefone" name="telefone" wire:model="telefone" />
                        <x-input label="Celular" name="celular" wire:model="celular" />
                        <x-input label="Email" name="email" type="email" wire:model="email" />
                        <x-input label="Site" name="site" wire:model="site" placeholder="https://" />
                        <x-input label="Pessoa de contato" name="contato_nome" wire:model="contato_nome" />
                        <x-input label="Cargo do contato" name="contato_cargo" wire:model="contato_cargo" />
                    </div>
                </div>

                {{-- Endereço --}}
                <div>
                    <h3 class="text-sm font-medium text-gray-900 mb-3">Endereço</h3>
                    <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                        <div class="md:col-span-2"><x-input label="CEP" name="cep" wire:model="cep" /></div>
                        <div class="md:col-span-3"><x-input label="Logradouro" name="logradouro" wire:model="logradouro" /></div>
                        <div><x-input label="Número" name="numero" wire:model="numero" /></div>
                        <div class="md:col-span-3"><x-input label="Bairro" name="bairro" wire:model="bairro" /></div>
                        <div class="md:col-span-3"><x-input label="Complemento" name="complemento" wire:model="complemento" /></div>
                        <div class="md:col-span-3">
                            <x-select label="Estado" wire:model.live="estadoFiltro">
                                <option value="">Selecione</option>
                                @foreach ($estados as $e)
                                    <option value="{{ $e->id }}">{{ $e->nome }} ({{ $e->uf }})</option>
                                @endforeach
                            </x-select>
                        </div>
                        <div class="md:col-span-3">
                            <x-select label="Cidade" name="cidade_id" wire:model="cidade_id" :disabled="!$estadoFiltro">
                                <option value="">Selecione</option>
                                @foreach ($cidadesDisponiveis as $c)
                                    <option value="{{ $c->id }}">{{ $c->nome }}</option>
                                @endforeach
                            </x-select>
                        </div>
                    </div>
                </div>

                {{-- Dados bancários --}}
                <div>
                    <h3 class="text-sm font-medium text-gray-900 mb-3">Dados bancários</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-input label="Código do banco" name="banco_codigo" wire:model="banco_codigo" />
                        <x-input label="Agência" name="banco_agencia" wire:model="banco_agencia" />
                        <x-input label="Conta" name="banco_conta" wire:model="banco_conta" />
                        <x-input label="Chave PIX" name="pix_chave" wire:model="pix_chave" />
                    </div>
                </div>

                {{-- Avaliação e homologação --}}
                <div>
                    <h3 class="text-sm font-medium text-gray-900 mb-3">Avaliação</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-select label="Avaliação (1-5)" name="avaliacao" wire:model="avaliacao">
                            <option value="">Sem avaliação</option>
                            <option value="1">1 - Ruim</option>
                            <option value="2">2 - Regular</option>
                            <option value="3">3 - Bom</option>
                            <option value="4">4 - Muito bom</option>
                            <option value="5">5 - Excelente</option>
                        </x-select>
                        <div class="flex items-end pb-2">
                            <label class="flex items-center text-sm cursor-pointer">
                                <input type="checkbox" wire:model="homologado" class="h-4 w-4 rounded text-indigo-600 focus:ring-indigo-500">
                                <span class="ml-2">Fornecedor homologado</span>
                            </label>
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                        <textarea wire:model="observacoes" rows="2"
                                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                    </div>
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
            <p class="text-sm text-gray-700">Desativar o fornecedor <strong>{{ $deletingName }}</strong>?</p>
        </div>
        <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 flex justify-end space-x-2">
            <x-button variant="secondary" wire:click="$set('showDeleteModal', false)">Cancelar</x-button>
            <x-button variant="danger" wire:click="delete">Sim, desativar</x-button>
        </div>
    </x-modal>
</div>
