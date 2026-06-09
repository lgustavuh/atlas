<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">

        {{-- Logo + Título --}}
        <div class="text-center">
            <div class="mx-auto w-14 h-14 bg-indigo-600 rounded-xl flex items-center justify-center mb-4 shadow-lg">
                <i class="ti ti-world text-white text-2xl" aria-hidden="true"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">
                {{ config('app.name') }}
            </h1>
            <p class="mt-2 text-sm text-gray-600">
                Entre com suas credenciais para acessar
            </p>
        </div>

        {{-- Flash de status (após logout, troca de senha, etc) --}}
        @if (session('status'))
            <x-alert variant="success" dismissible>
                {{ session('status') }}
            </x-alert>
        @endif

        @if (session('error'))
            <x-alert variant="error" dismissible>
                {{ session('error') }}
            </x-alert>
        @endif

        {{-- Formulário --}}
        <form wire:submit.prevent="login" class="bg-white shadow-lg rounded-lg p-8 space-y-5">

            <x-input
                label="Email"
                type="email"
                name="email"
                wire:model="email"
                required
                autofocus
                autocomplete="username"
                placeholder="seu.email@empresa.com"
            />

            <x-input
                label="Senha"
                type="password"
                name="password"
                wire:model="password"
                required
                autocomplete="current-password"
                placeholder="••••••••"
            />

            <div class="flex items-center justify-between">
                <label class="flex items-center text-sm cursor-pointer">
                    <input
                        wire:model="remember"
                        type="checkbox"
                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                    >
                    <span class="ml-2 text-gray-700">Lembrar-me</span>
                </label>

                <a href="{{ route('password.request') }}" wire:navigate.hover
                   class="text-sm text-indigo-600 hover:text-indigo-500 font-medium">
                    Esqueci a senha
                </a>
            </div>

            <x-button
                type="submit"
                variant="primary"
                size="md"
                loading="login"
                class="w-full"
            >
                Entrar
            </x-button>
        </form>

        <p class="text-center text-xs text-gray-500">
            <i class="ti ti-shield-check mr-1" aria-hidden="true"></i>
            Conexão segura &middot; Sistema Atlas v2.0
        </p>
    </div>
</div>
