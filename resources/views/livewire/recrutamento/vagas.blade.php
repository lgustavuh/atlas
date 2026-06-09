<div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full">

    <div class="sm:flex sm:items-center sm:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Vagas de Emprego</h1>
            <p class="mt-1 text-sm text-gray-600">Processo seletivo: vagas abertas e candidatos.</p>
        </div>
        <div class="mt-4 sm:mt-0 flex gap-2">
            <a href="{{ route('recrutamento.candidatos') }}" wire:navigate.hover>
                <x-button variant="secondary" icon="users">Candidatos</x-button>
            </a>
            @can('create', App\Models\Vaga::class)
                <x-button wire:click="openCreate" icon="plus">Nova vaga</x-button>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-md bg-green-50 flex items-center justify-center">
                    <i class="ti ti-briefcase text-green-600 text-xl" aria-hidden="true"></i>
                </div>
                <div class="ml-3">
                    <p class="text-xs text-gray-500">Vagas abertas</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['abertas'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-md bg-blue-50 flex items-center justify-center">
                    <i class="ti ti-user-search text-blue-600 text-xl" aria-hidden="true"></i>
                </div>
                <div class="ml-3">
                    <p class="text-xs text-gray-500">Em seleção</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['em_selecao'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-md bg-indigo-50 flex items-center justify-center">
                    <i class="ti ti-users text-indigo-600 text-xl" aria-hidden="true"></i>
                </div>
                <div class="ml-3">
                    <p class="text-xs text-gray-500">Candidatos novos</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['candidatos_inscritos'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <x-card class="mb-4" padding="p-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-input label="Buscar" wire:model.live.debounce.400ms="search" placeholder="Título, descrição..." />
            <x-select label="Status" wire:model.live="filterStatus">
                <option value="">Todos</option>
                @foreach ($statuses as $key => $label)
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vaga</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cargo/Departamento</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Vagas</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Candidatos</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Salário</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($vagas as $v)
                        <tr wire:key="vaga-{{ $v->id }}" class="hover:bg-gray-50 {{ $v->expirada ? 'bg-red-50/30' : '' }}">
                            <td class="px-6 py-3">
                                <div class="text-sm font-medium text-gray-900">{{ $v->titulo }}</div>
                                @if ($v->data_fechamento)
                                    <div class="text-xs {{ $v->expirada ? 'text-red-600 font-medium' : 'text-gray-500' }}">
                                        Fecha em {{ $v->data_fechamento->format('d/m/Y') }}
                                        @if ($v->expirada) (expirada) @endif
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-600">
                                <div>{{ $v->cargo?->nome ?? '—' }}</div>
                                <div class="text-xs text-gray-400">{{ $v->departamento?->nome ?? '—' }}</div>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-right text-sm text-gray-900">{{ $v->quantidade_vagas }}</td>
                            <td class="px-6 py-3 whitespace-nowrap text-right text-sm">
                                <a href="{{ route('recrutamento.candidatos', ['vaga' => $v->id]) }}" wire:navigate.hover
                                   class="text-indigo-600 hover:text-indigo-900 font-medium">
                                    {{ $v->candidatos_count }}
                                </a>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">{{ $v->faixa_salarial }}</td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                <x-badge :variant="$v->status_cor">{{ $v->status_label }}</x-badge>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                @if ($v->status === 'rascunho')
                                    @can('update', $v)
                                        <button wire:click="publicar({{ $v->id }})"
                                                wire:confirm="Publicar esta vaga? Ela ficará visível e poderá receber candidatos."
                                                class="text-green-600 hover:text-green-900" title="Publicar">
                                            <i class="ti ti-send" aria-hidden="true"></i>
                                        </button>
                                    @endcan
                                @endif
                                @can('update', $v)
                                    <button wire:click="openEdit({{ $v->id }})" class="text-indigo-600 hover:text-indigo-900">
                                        <i class="ti ti-edit" aria-hidden="true"></i>
                                    </button>
                                @endcan
                                @can('delete', $v)
                                    <button wire:click="confirmDelete({{ $v->id }})" class="text-red-600 hover:text-red-900">
                                        <i class="ti ti-trash" aria-hidden="true"></i>
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <i class="ti ti-briefcase-off text-4xl text-gray-300 mb-2 block" aria-hidden="true"></i>
                                <p class="text-sm text-gray-500">Nenhuma vaga cadastrada.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($vagas->hasPages())
            <div class="px-6 py-3 border-t border-gray-200">{{ $vagas->links() }}</div>
        @endif
    </x-card>

    <x-modal name="showModal" max-width="3xl" :title="$editando ? 'Editar vaga' : 'Nova vaga'">
        <form wire:submit.prevent="save">
            <div class="px-6 py-4 space-y-5 max-h-[70vh] overflow-y-auto">

                <div>
                    <h3 class="text-sm font-medium text-gray-900 mb-3">Informações básicas</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <x-input label="Título da vaga" name="titulo" wire:model="titulo" required />
                        </div>
                        <x-select label="Cargo" name="cargo_id" wire:model="cargo_id">
                            <option value="">Selecione</option>
                            @foreach ($cargos as $c)
                                <option value="{{ $c->id }}">{{ $c->nome }}</option>
                            @endforeach
                        </x-select>
                        <x-select label="Departamento" name="departamento_id" wire:model="departamento_id">
                            <option value="">Selecione</option>
                            @foreach ($departamentos as $d)
                                <option value="{{ $d->id }}">{{ $d->nome }}</option>
                            @endforeach
                        </x-select>
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-gray-900 mb-3">Descrição da vaga</h3>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Descrição *</label>
                            <textarea wire:model="descricao" rows="3" required
                                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                      placeholder="Atividades, responsabilidades, missão do cargo..."></textarea>
                            @error('descricao') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Requisitos</label>
                            <textarea wire:model="requisitos" rows="3"
                                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                      placeholder="Formação, experiência, conhecimentos..."></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Benefícios</label>
                            <textarea wire:model="beneficios" rows="2"
                                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                      placeholder="Vale-refeição, plano de saúde, etc..."></textarea>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-gray-900 mb-3">Salário e quantidade</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <x-input label="Salário de (R$)" name="salario_de" type="number" step="0.01" min="0" wire:model="salario_de" />
                        <x-input label="Salário até (R$)" name="salario_ate" type="number" step="0.01" min="0" wire:model="salario_ate" />
                        <x-input label="Quantidade de vagas" name="quantidade_vagas" type="number" min="1" max="999" wire:model="quantidade_vagas" required />
                    </div>
                    <div class="mt-2">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" wire:model="salario_publicar" class="h-4 w-4 rounded text-indigo-600 focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-700">Publicar faixa salarial na vaga</span>
                        </label>
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-gray-900 mb-3">Datas e status</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <x-input label="Data de abertura" name="data_abertura" type="date" wire:model="data_abertura" />
                        <x-input label="Data de fechamento" name="data_fechamento" type="date" wire:model="data_fechamento" />
                        <x-select label="Status" name="status" wire:model="status" required>
                            @foreach ($statuses as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </x-select>
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
            <p class="text-sm text-gray-700">Excluir a vaga <strong>{{ $deletingName }}</strong>?</p>
        </div>
        <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 flex justify-end space-x-2">
            <x-button variant="secondary" wire:click="$set('showDeleteModal', false)">Cancelar</x-button>
            <x-button variant="danger" wire:click="delete">Sim, excluir</x-button>
        </div>
    </x-modal>
</div>
