<div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full">

    <div class="sm:flex sm:items-center sm:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Cargos</h1>
            <p class="mt-1 text-sm text-gray-600">Funções e ocupações disponíveis para os colaboradores.</p>
        </div>
        @can('create', App\Models\Cargo::class)
            <div class="mt-4 sm:mt-0">
                <x-button wire:click="openCreate" icon="plus">Novo cargo</x-button>
            </div>
        @endcan
    </div>

    <x-card class="mb-4" padding="p-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-input
                label="Buscar"
                wire:model.live.debounce.400ms="search"
                placeholder="Nome ou CBO..."
            />
            <x-select label="Departamento" wire:model.live="filterDepartamento">
                <option value="">Todos</option>
                @foreach ($departamentos as $dep)
                    <option value="{{ $dep->id }}">{{ $dep->nome }}</option>
                @endforeach
            </x-select>
        </div>
    </x-card>

    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cargo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">CBO</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Departamento</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Faixa salarial</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Colaboradores</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($cargos as $cargo)
                        <tr wire:key="cargo-{{ $cargo->id }}" class="hover:bg-gray-50">
                            <td class="px-6 py-3 whitespace-nowrap text-sm font-medium text-gray-900">{{ $cargo->nome }}</td>
                            <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600 font-mono">{{ $cargo->cbo ?? '—' }}</td>
                            <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">{{ $cargo->departamento?->nome ?? '—' }}</td>
                            <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">
                                @if ($cargo->salario_minimo && $cargo->salario_maximo)
                                    R$ {{ number_format((float) $cargo->salario_minimo, 2, ',', '.') }}
                                    -
                                    R$ {{ number_format((float) $cargo->salario_maximo, 2, ',', '.') }}
                                @elseif ($cargo->salario_minimo)
                                    A partir de R$ {{ number_format((float) $cargo->salario_minimo, 2, ',', '.') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                <x-badge variant="{{ $cargo->colaboradores_count > 0 ? 'indigo' : 'gray' }}">
                                    {{ $cargo->colaboradores_count }}
                                </x-badge>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-medium space-x-3">
                                @can('update', $cargo)
                                    <button wire:click="openEdit({{ $cargo->id }})" class="text-indigo-600 hover:text-indigo-900">
                                        <i class="ti ti-edit" aria-hidden="true"></i>
                                    </button>
                                @endcan
                                @can('delete', $cargo)
                                    <button wire:click="confirmDelete({{ $cargo->id }})" class="text-red-600 hover:text-red-900">
                                        <i class="ti ti-trash" aria-hidden="true"></i>
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <i class="ti ti-briefcase-off text-4xl text-gray-300 mb-2 block" aria-hidden="true"></i>
                                <p class="text-sm text-gray-500">Nenhum cargo encontrado.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($cargos->hasPages())
            <div class="px-6 py-3 border-t border-gray-200">{{ $cargos->links() }}</div>
        @endif
    </x-card>

    {{-- Modal criar/editar --}}
    <x-modal name="showModal" max-width="2xl" :title="$editando ? 'Editar cargo' : 'Novo cargo'">
        <form wire:submit.prevent="save">
            <div class="px-6 py-4 space-y-4 max-h-[60vh] overflow-y-auto">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <x-input label="Nome do cargo" name="nome" wire:model="nome" required />
                    </div>
                    <x-input label="CBO" name="cbo" wire:model="cbo" hint="Código Brasileiro de Ocupações" />
                    <x-select label="Departamento" name="departamento_id" wire:model="departamento_id">
                        <option value="">Nenhum</option>
                        @foreach ($departamentos as $dep)
                            <option value="{{ $dep->id }}">{{ $dep->nome }}</option>
                        @endforeach
                    </x-select>
                    <x-input label="Salário mínimo (R$)" name="salario_minimo" type="number" step="0.01" min="0" wire:model="salario_minimo" />
                    <x-input label="Salário máximo (R$)" name="salario_maximo" type="number" step="0.01" min="0" wire:model="salario_maximo" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                    <textarea wire:model="descricao" rows="2" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Atribuições</label>
                    <textarea wire:model="atribuicoes" rows="3" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Requisitos</label>
                    <textarea wire:model="requisitos" rows="3" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
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
            <div class="flex items-start">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                    <i class="ti ti-alert-triangle text-red-600 text-xl" aria-hidden="true"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-700">Deseja excluir o cargo <strong>{{ $deletingName }}</strong>?</p>
                </div>
            </div>
        </div>
        <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 flex justify-end space-x-2">
            <x-button variant="secondary" wire:click="$set('showDeleteModal', false)">Cancelar</x-button>
            <x-button variant="danger" wire:click="delete">Sim, excluir</x-button>
        </div>
    </x-modal>
</div>
