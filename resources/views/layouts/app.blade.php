@props([
    'title' => null,
    'breadcrumbs' => [],
    'pageTitle' => null,
])

<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name') }}</title>

    {{-- Preconnect para reduzir latência da fonte web --}}
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>

    {{-- Tabler Icons agora vem do bundle Vite (sem requisicao a CDN externo) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
</head>
<body class="h-full antialiased bg-gray-100 text-gray-900">

    <div class="flex h-full">

        {{-- Sidebar (desktop fixa, mobile drawer) --}}
        <x-sidebar />

        {{-- Área principal --}}
        <div class="flex-1 flex flex-col min-w-0 min-h-full">

            {{-- Header --}}
            <x-header :breadcrumbs="$breadcrumbs">
                {{ $pageTitle ?? '' }}
            </x-header>

            {{-- Flash messages globais (de redirects, etc) --}}
            @if (session('success') || session('error') || session('warning') || session('info'))
                <div class="px-4 sm:px-6 lg:px-8 pt-4">
                    @foreach (['success' => 'success', 'error' => 'error', 'warning' => 'warning', 'info' => 'info'] as $key => $variant)
                        @if (session($key))
                            <x-alert :variant="$variant" dismissible>
                                {{ session($key) }}
                            </x-alert>
                        @endif
                    @endforeach
                </div>
            @endif

            {{-- Conteúdo --}}
            <main class="flex-1 overflow-y-auto">
                @auth
                    <div class="px-4 sm:px-6 lg:px-8 pt-4 max-w-7xl mx-auto w-full">
                        <livewire:alertas.banner />
                    </div>
                @endauth
                {{ $slot }}
            </main>
        </div>
    </div>

    {{-- Toast container global --}}
    <x-toast-container />

    @livewireScripts

    {{--
        Listener: traduz session flash em toast também
        (assim você pode usar tanto session()->flash quanto $this->dispatch)
    --}}
    @if (session('flash_toast'))
        <script>
            document.addEventListener('livewire:init', () => {
                Livewire.dispatch('toast', @json(session('flash_toast')));
            });
        </script>
    @endif
</body>
</html>
