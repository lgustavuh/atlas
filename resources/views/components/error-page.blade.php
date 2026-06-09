@props([
    'code',
    'title',
    'message',
    'icon' => 'mood-confuzed',
])

<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="UTF-8">
    <title>{{ $code }} — {{ $title }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.5.0/dist/tabler-icons.min.css">
    @vite(['resources/css/app.css'])
</head>
<body class="h-full antialiased bg-gradient-to-br from-gray-50 to-gray-100">
    <div class="min-h-full flex items-center justify-center px-4">
        <div class="text-center max-w-md">
            <i class="ti ti-{{ $icon }} text-7xl text-gray-400 mb-4" aria-hidden="true"></i>

            <p class="text-sm font-medium text-indigo-600 uppercase tracking-wide">Erro {{ $code }}</p>
            <h1 class="mt-2 text-3xl font-bold text-gray-900">{{ $title }}</h1>
            <p class="mt-3 text-base text-gray-600">{{ $message }}</p>

            <div class="mt-8 flex justify-center space-x-3">
                <a href="{{ url()->previous() }}"
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <i class="ti ti-arrow-left mr-1.5" aria-hidden="true"></i>
                    Voltar
                </a>
                <a href="{{ url('/') }}"
                   class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                    <i class="ti ti-home mr-1.5" aria-hidden="true"></i>
                    Ir para a página inicial
                </a>
            </div>
        </div>
    </div>
</body>
</html>
