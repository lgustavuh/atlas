<div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full">

    <div class="sm:flex sm:items-center sm:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Candidatos</h1>
            <p class="mt-1 text-sm text-gray-600">
                @if ($vagaFiltrada)
                    Candidatos da vaga <strong>{{ $vagaFiltrada->titulo }}</strong>
                @else
                    Gestão de candidatos ao processo seletivo.
                @endif
            </p>
        </div>
        <div class="mt-4 sm:mt-0 flex gap-2">
            <a href="{{ route('recrutamento.vagas') }}" wire:navigate.hover>
                <x-button variant="secondary" icon="briefcase">Vagas</x-button>
            </a>
            @can('create', App\Models\Candidato::class)
                <x-button wire:click="openCreate" icon="plus">Novo candidato</x-button>
            @endcan
        </div>
    </div>

    <x-card class="mb-4" padding="p-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-input label="Buscar" wire:model.live.debounce.400ms="search" placeholder="Nome, e-mail ou CPF..." />
            <x-select label="Vaga" wire:model.live="filterVagaId">
                <option value="">Todas</option>
                @foreach ($vagas as $v)
                    <option value="{{ $v->id }}">{{ $v->titulo }}</option>
                @endforeach
            </x-select>
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Candidato</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vaga</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contato</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Pontuação</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($candidatos as $c)
                        <tr wire:key="cand-{{ $c->id }}" class="hover:bg-gray-50">
                            <td class="px-6 py-3">
                                <div class="text-sm font-medium text-gray-900">{{ $c->nome }}</div>
                                @if ($c->cpf)
                                    <div class="text-xs text-gray-500 font-mono">{{ $c->cpf_formatado }}</div>
                                @endif
                                @if ($c->curriculo_path)
                                    <div class="text-xs text-indigo-600 mt-1">
                                        <i class="ti ti-file-text mr-1" aria-hidden="true"></i> Currículo anexado
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-600">
                                <a href="{{ route('recrutamento.vagas') }}" wire:navigate.hover
                                   class="text-indigo-600 hover:underline">{{ $c->vaga?->titulo }}</a>
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-600">
                                <div class="text-xs">{{ $c->email }}</div>
                                @if ($c->telefone)
                                    <div class="text-xs text-gray-400">{{ $c->telefone }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-center text-sm">
                                @if ($c->pontuacao !== null)
                                    <span @class([
                                        'font-medium',
                                        'text-green-600' => $c->pontuacao >= 70,
                                        'text-yellow-600' => $c->pontuacao >= 50 && $c->pontuacao < 70,
                                        'text-red-600' => $c->pontuacao < 50,
                                    ])>{{ $c->pontuacao }}/100</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                <x-badge :variant="$c->status_cor">{{ $c->status_label }}</x-badge>
                                @if (count($c->transicoesPossiveis()) > 0)
                                    <div class="mt-1 flex flex-wrap gap-1">
                                        @foreach ($c->transicoesPossiveis() as $proximo)
                                            <button wire:click="alterarStatus({{ $c->id }}, '{{ $proximo }}')"
                                                    wire:confirm="Mover para {{ $proximo }}?"
                                                    class="text-[10px] px-2 py-0.5 rounded bg-gray-100 hover:bg-gray-200 text-gray-700">
                                                → {{ ucfirst($proximo) }}
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                @can('update', $c)
                                    <button wire:click="openEdit({{ $c->id }})" class="text-indigo-600 hover:text-indigo-900">
                                        <i class="ti ti-edit" aria-hidden="true"></i>
                                    </button>
                                @endcan
                                @can('delete', $c)
                                    <button wire:click="confirmDelete({{ $c->id }})" class="text-red-600 hover:text-red-900">
                                        <i class="ti ti-trash" aria-hidden="true"></i>
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <i class="ti ti-user-off text-4xl text-gray-300 mb-2 block" aria-hidden="true"></i>
                                <p class="text-sm text-gray-500">Nenhum candidato cadastrado.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($candidatos->hasPages())
            <div class="px-6 py-3 border-t border-gray-200">{{ $candidatos->links() }}</div>
        @endif
    </x-card>

    <x-modal name="showModal" max-width="3xl" :title="$editando ? 'Editar candidato' : 'Novo candidato'">
        <form wire:submit.prevent="save">
            <div class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">

                <x-select label="Vaga" name="vaga_id" wire:model="vaga_id" required>
                    <option value="">Selecione</option>
                    @foreach ($vagas as $v)
                        <option value="{{ $v->id }}">{{ $v->titulo }} ({{ $v->status }})</option>
                    @endforeach
                </x-select>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-input label="Nome" name="nome" wire:model="nome" required />
                    <x-input label="CPF" name="cpf" wire:model="cpf" mask="000.000.000-00" placeholder="000.000.000-00" />
                    <x-input label="E-mail" name="email" type="email" wire:model="email" required />
                    <x-input label="Telefone" name="telefone" wire:model="telefone" placeholder="(00) 00000-0000" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Experiência profissional</label>
                    <textarea wire:model="experiencia" rows="3"
                              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                              placeholder="Empresas anteriores, funções, períodos..."></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-select label="Status" name="status" wire:model="status" required>
                        @foreach ($statuses as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </x-select>
                    <x-input label="Pontuação (0–100)" name="pontuacao" type="number" min="0" max="100" wire:model="pontuacao" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observações internas</label>
                    <textarea wire:model="observacoes" rows="2"
                              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Currículo (PDF, DOC, DOCX)</label>
                    <input type="file" wire:model="curriculo" accept=".pdf,.doc,.docx"
                           class="block w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    <p class="mt-1 text-xs text-gray-500">Até 5 MB</p>
                    @error('curriculo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
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
            <p class="text-sm text-gray-700">Excluir o candidato <strong>{{ $deletingName }}</strong>?</p>
        </div>
        <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 flex justify-end space-x-2">
            <x-button variant="secondary" wire:click="$set('showDeleteModal', false)">Cancelar</x-button>
            <x-button variant="danger" wire:click="delete">Sim, excluir</x-button>
        </div>
    </x-modal>
</div>
