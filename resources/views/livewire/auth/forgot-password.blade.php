<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
    <div class="max-w-md w-full space-y-8">
        <div class="text-center">
            <h1 class="text-2xl font-bold text-gray-900">Recuperar senha</h1>
            <p class="mt-2 text-sm text-gray-600">
                Informe seu email cadastrado para receber um link de recuperação.
            </p>
        </div>

        @if ($status)
            <div class="rounded-md bg-green-50 p-4 border border-green-200">
                <p class="text-sm text-green-700">{{ $status }}</p>
            </div>
        @endif

        <form wire:submit.prevent="send" class="bg-white shadow-lg rounded-lg p-8 space-y-6">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input
                    wire:model="email"
                    type="email"
                    id="email"
                    required
                    autofocus
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('email') border-red-500 @enderror"
                >
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                wire:loading.attr="disabled"
                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50"
            >
                <span wire:loading.remove>Enviar link de recuperação</span>
                <span wire:loading>Enviando...</span>
            </button>

            <p class="text-center text-sm">
                <a href="{{ route('login') }}" wire:navigate.hover class="text-indigo-600 hover:text-indigo-500">
                    ← Voltar para o login
                </a>
            </p>
        </form>
    </div>
</div>
