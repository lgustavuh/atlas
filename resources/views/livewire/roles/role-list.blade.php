<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center sm:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Perfis e permissões</h1>
            <p class="mt-1 text-sm text-gray-600">Cada perfil concede um conjunto de permissões aos usuários.</p>
        </div>
        @can('roles.create')
            <button wire:click="openCreate" type="button"
                    class="mt-4 sm:mt-0 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
                + Novo perfil
            </button>
        @endcan
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-md bg-green-50 p-4 border border-green-200">
            <p class="text-sm text-green-700">{{ session('success') }}</p>
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-md bg-red-50 p-4 border border-red-200">
            <p class="text-sm text-red-700">{{ session('error') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach ($roles as $role)
            <div wire:key="role-{{ $role->id }}" class="bg-white rounded-lg shadow p-4">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">
                            {{ $role->display_name ?? $role->name }}
                            @if ($role->name === 'admin')
                                <span class="ml-1 text-xs text-amber-600">🔒</span>
                            @endif
                        </h3>
                        <p class="text-xs text-gray-500 font-mono">{{ $role->name }}</p>
                    </div>
                </div>
                @if ($role->description)
                    <p class="mt-2 text-sm text-gray-600">{{ $role->description }}</p>
                @endif
                <div class="mt-3 text-xs text-gray-500">
                    {{ $role->permissions_count }} permissões &middot;
                    {{ $role->users_count }} {{ Str::plural('usuário', $role->users_count) }}
                </div>
                <div class="mt-4 flex space-x-2">
                    @can('roles.update')
                        <button wire:click="openEdit({{ $role->id }})"
                                class="text-sm text-indigo-600 hover:text-indigo-900">
                            Editar
                        </button>
                    @endcan
                    @can('roles.delete')
                        @if ($role->name !== 'admin' && $role->users_count === 0)
                            <button wire:click="delete({{ $role->id }})"
                                    wire:confirm="Excluir o perfil {{ $role->display_name ?? $role->name }}?"
                                    class="text-sm text-red-600 hover:text-red-900">
                                Excluir
                            </button>
                        @endif
                    @endcan
                </div>
            </div>
        @endforeach
    </div>

    {{-- Modal de Criar/Editar --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75" wire:click="closeModal"></div>
                <div class="inline-block bg-white rounded-lg text-left overflow-hidden shadow-xl max-w-3xl w-full relative">
                    <form wire:submit.prevent="save">
                        <div class="px-6 pt-5 pb-4 max-h-[80vh] overflow-y-auto">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                {{ $editing ? 'Editar perfil' : 'Novo perfil' }}
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">
                                        Identificador <span class="text-xs text-gray-500">(snake_case)</span>
                                    </label>
                                    <input wire:model="name" type="text" required
                                           placeholder="ex: gestor_compras"
                                           {{ $editing && $name === 'admin' ? 'readonly' : '' }}
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono">
                                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Nome de exibição</label>
                                    <input wire:model="display_name" type="text" required
                                           placeholder="ex: Gestor de Compras"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    @error('display_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">Descrição</label>
                                <textarea wire:model="description" rows="2"
                                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Permissões</label>
                                <div class="space-y-3 border rounded-md p-3 max-h-96 overflow-y-auto">
                                    @foreach ($permissionsByModule as $module => $perms)
                                        <div>
                                            <h4 class="text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
                                                {{ ucfirst(str_replace('-', ' ', $module)) }}
                                            </h4>
                                            <div class="grid grid-cols-2 md:grid-cols-3 gap-1">
                                                @foreach ($perms as $perm)
                                                    <label class="flex items-center text-sm">
                                                        <input wire:model="selectedPermissions" type="checkbox"
                                                               value="{{ $perm->id }}"
                                                               class="h-4 w-4 rounded text-indigo-600 focus:ring-indigo-500">
                                                        <span class="ml-2 text-xs font-mono">
                                                            {{ explode('.', $perm->name)[1] ?? $perm->name }}
                                                        </span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-6 py-3 flex justify-end space-x-2">
                            <button type="button" wire:click="closeModal"
                                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                Cancelar
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                                Salvar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
