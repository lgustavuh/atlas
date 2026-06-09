<div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full">

    {{-- Header da página --}}
    <div class="sm:flex sm:items-center sm:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Usuários</h1>
            <p class="mt-1 text-sm text-gray-600">Gerencie contas, perfis e permissões.</p>
        </div>
        @can('create', App\Models\User::class)
            <div class="mt-4 sm:mt-0">
                <x-button wire:click="openCreate" icon="plus">Novo usuário</x-button>
            </div>
        @endcan
    </div>

    {{-- Flash messages --}}
    @if (session('success'))
        <div class="mb-4">
            <x-alert variant="success" dismissible>{{ session('success') }}</x-alert>
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4">
            <x-alert variant="error" dismissible>{{ session('error') }}</x-alert>
        </div>
    @endif

    {{-- Filtros --}}
    <x-card class="mb-4" padding="p-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-input
                label="Buscar"
                wire:model.live.debounce.300ms="search"
                placeholder="Nome ou email..."
            />
            <x-select label="Status" wire:model.live="filterStatus">
                <option value="todos">Todos</option>
                <option value="ativos">Ativos</option>
                <option value="inativos">Inativos</option>
                <option value="bloqueados">Bloqueados</option>
            </x-select>
        </div>
    </x-card>

    {{-- Tabela --}}
    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Perfis</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Último login</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($users as $user)
                        <tr wire:key="user-{{ $user->id }}" class="hover:bg-gray-50">
                            <td class="px-6 py-3 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-medium text-xs flex-shrink-0">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <span class="ml-2 text-sm font-medium text-gray-900">{{ $user->name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">{{ $user->email }}</td>
                            <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">
                                @forelse ($user->roles as $role)
                                    <x-badge variant="indigo">{{ $role->display_name ?? $role->name }}</x-badge>
                                @empty
                                    <span class="text-gray-400 italic text-xs">sem perfil</span>
                                @endforelse
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                @if ($user->is_locked)
                                    <x-badge variant="red" icon="lock">Bloqueado</x-badge>
                                @elseif ($user->active)
                                    <x-badge variant="green" icon="circle-check">Ativo</x-badge>
                                @else
                                    <x-badge variant="gray">Inativo</x-badge>
                                @endif
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">
                                {{ $user->last_login_at?->diffForHumans() ?? 'nunca' }}
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-medium space-x-3">
                                @if ($user->is_locked)
                                    <button wire:click="unlock({{ $user->id }})" class="text-amber-600 hover:text-amber-900">
                                        <i class="ti ti-lock-open" aria-hidden="true"></i> Desbloquear
                                    </button>
                                @endif
                                @can('update', $user)
                                    <button wire:click="openEdit({{ $user->id }})" class="text-indigo-600 hover:text-indigo-900">
                                        Editar
                                    </button>
                                @endcan
                                @can('delete', $user)
                                    @if ($user->id !== auth()->id())
                                        <button wire:click="confirmDelete({{ $user->id }})" class="text-red-600 hover:text-red-900">
                                            Excluir
                                        </button>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <i class="ti ti-users-off text-4xl text-gray-300 mb-2 block" aria-hidden="true"></i>
                                <p class="text-sm text-gray-500">Nenhum usuário encontrado.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="px-6 py-3 border-t border-gray-200">
                {{ $users->links() }}
            </div>
        @endif
    </x-card>

    {{-- Modal de criar/editar --}}
    <x-modal name="showModal" max-width="2xl" :title="$editing ? 'Editar usuário' : 'Novo usuário'">
        <form wire:submit.prevent="save">
            <div class="px-6 py-4 space-y-4 max-h-[60vh] overflow-y-auto">

                {{-- Select de colaborador: só aparece na criação --}}
                @if (! $editing)
                    <div>
                        <label for="colaborador_id" class="block text-sm font-medium text-gray-700 mb-1">
                            Colaborador <span class="text-red-500">*</span>
                        </label>
                        <select id="colaborador_id" wire:model.live="colaborador_id"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">— Selecione um colaborador —</option>
                            @foreach ($colaboradores as $colab)
                                <option value="{{ $colab->id }}">{{ $colab->nome }}</option>
                            @endforeach
                        </select>
                        @error('colaborador_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">
                            Só aparecem colaboradores ativos que ainda não têm usuário cadastrado.
                        </p>
                    </div>

                    @if ($departamento_label !== '')
                        <div class="rounded-md bg-blue-50 p-3 text-sm text-blue-800">
                            <i class="ti ti-building inline-block mr-1" aria-hidden="true"></i>
                            Departamento: <strong>{{ $departamento_label }}</strong> — perfil sugerido marcado abaixo (pode alterar).
                        </div>
                    @endif
                @endif

                <x-input label="Nome completo" name="name" wire:model="name" required
                         :hint="$editing ? null : 'Pré-preenchido a partir do colaborador. Pode editar.'" />
                <x-input label="Email" name="email" type="email" wire:model="email" required
                         :hint="$editing ? null : 'Pré-preenchido a partir do colaborador. Pode editar.'" />

                <x-input
                    label="Senha {{ $editing ? '(deixe em branco para manter)' : '' }}"
                    name="password"
                    type="password"
                    wire:model="password"
                    hint="12+ caracteres com maiúscula, minúscula, número e símbolo."
                />

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Perfis <span class="text-red-500">*</span>
                    </label>
                    <div class="border border-gray-300 rounded-md p-3 max-h-48 overflow-y-auto space-y-2">
                        @foreach ($roles as $role)
                            <label class="flex items-start cursor-pointer">
                                <input wire:model="selectedRoles" type="checkbox" value="{{ $role->id }}"
                                       class="mt-0.5 h-4 w-4 rounded text-indigo-600 focus:ring-indigo-500">
                                <div class="ml-2">
                                    <p class="text-sm font-medium text-gray-900">{{ $role->display_name ?? $role->name }}</p>
                                    @if ($role->description)
                                        <p class="text-xs text-gray-500">{{ $role->description }}</p>
                                    @endif
                                </div>
                            </label>
                        @endforeach
                    </div>
                    @error('selectedRoles')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <label class="flex items-center cursor-pointer">
                    <input wire:model="active" type="checkbox" class="h-4 w-4 rounded text-indigo-600 focus:ring-indigo-500">
                    <span class="ml-2 text-sm text-gray-700">Conta ativa</span>
                </label>
            </div>

            <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 flex justify-end space-x-2">
                <x-button type="button" variant="secondary" wire:click="closeModal">Cancelar</x-button>
                <x-button type="submit" loading="save">Salvar</x-button>
            </div>
        </form>
    </x-modal>

    {{-- Modal confirmação exclusão --}}
    <x-modal name="showDeleteModal" max-width="md" title="Confirmar exclusão">
        <div class="px-6 py-4">
            <div class="flex items-start">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                    <i class="ti ti-alert-triangle text-red-600 text-xl" aria-hidden="true"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-700">
                        Deseja desativar o usuário <strong>{{ $deletingName }}</strong>?
                    </p>
                    <p class="mt-1 text-xs text-gray-500">
                        A conta poderá ser reativada depois — os dados não serão apagados.
                    </p>
                </div>
            </div>
        </div>
        <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 flex justify-end space-x-2">
            <x-button type="button" variant="secondary" wire:click="$set('showDeleteModal', false)">Cancelar</x-button>
            <x-button type="button" variant="danger" wire:click="delete">Sim, desativar</x-button>
        </div>
    </x-modal>
</div>
