<div class="p-4 sm:p-6 lg:p-8 max-w-3xl mx-auto w-full">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Meu Perfil</h1>
        <p class="mt-1 text-sm text-gray-600">Visualize e edite seus dados pessoais.</p>
    </div>

    {{-- Card: Dados editáveis --}}
    <x-card title="Informações pessoais" icon="user" class="mb-6">
        <form wire:submit.prevent="update" class="space-y-4">
            <x-input
                label="Nome completo"
                name="name"
                wire:model="name"
                required
            />

            <x-input
                label="Email"
                type="email"
                :value="auth()->user()->email"
                disabled
                hint="Para alterar o email, contate o administrador."
            />

            <div class="flex justify-end pt-2">
                <x-button type="submit" icon="check" loading="update">
                    Salvar alterações
                </x-button>
            </div>
        </form>
    </x-card>

    {{-- Card: Informações da conta (read-only) --}}
    <x-card title="Informações da conta" icon="info-circle" class="mb-6">
        <dl class="divide-y divide-gray-200">
            <div class="py-3 grid grid-cols-3">
                <dt class="text-sm font-medium text-gray-500">Conta criada em</dt>
                <dd class="text-sm text-gray-900 col-span-2">
                    {{ auth()->user()->created_at?->format('d/m/Y H:i') ?? '—' }}
                </dd>
            </div>
            <div class="py-3 grid grid-cols-3">
                <dt class="text-sm font-medium text-gray-500">Último acesso</dt>
                <dd class="text-sm text-gray-900 col-span-2">
                    {{ auth()->user()->last_login_at?->format('d/m/Y H:i') ?? 'primeiro acesso' }}
                    @if (auth()->user()->last_login_ip)
                        <span class="text-xs text-gray-500">({{ auth()->user()->last_login_ip }})</span>
                    @endif
                </dd>
            </div>
            <div class="py-3 grid grid-cols-3">
                <dt class="text-sm font-medium text-gray-500">Perfis ativos</dt>
                <dd class="text-sm text-gray-900 col-span-2">
                    @forelse (auth()->user()->roles as $role)
                        <x-badge variant="indigo" icon="shield-check">
                            {{ $role->display_name ?? $role->name }}
                        </x-badge>
                    @empty
                        <span class="text-gray-400 italic">Nenhum perfil atribuído</span>
                    @endforelse
                </dd>
            </div>
            <div class="py-3 grid grid-cols-3">
                <dt class="text-sm font-medium text-gray-500">Status</dt>
                <dd class="text-sm text-gray-900 col-span-2">
                    @if (auth()->user()->active)
                        <x-badge variant="green" icon="circle-check">Ativa</x-badge>
                    @else
                        <x-badge variant="gray">Inativa</x-badge>
                    @endif
                </dd>
            </div>
        </dl>
    </x-card>

    {{-- Card: Segurança --}}
    <x-card title="Segurança" icon="shield-lock">
        <div class="space-y-3">
            <a href="{{ route('password.change') }}" wire:navigate.hover
               class="flex items-center justify-between p-3 -mx-3 rounded-md hover:bg-gray-50">
                <div class="flex items-center">
                    <i class="ti ti-key text-amber-600 text-xl mr-3" aria-hidden="true"></i>
                    <div>
                        <p class="text-sm font-medium text-gray-900">Trocar senha</p>
                        <p class="text-xs text-gray-500">Recomendado a cada 90 dias</p>
                    </div>
                </div>
                <i class="ti ti-chevron-right text-gray-400" aria-hidden="true"></i>
            </a>
        </div>
    </x-card>
</div>
