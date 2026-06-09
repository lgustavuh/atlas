<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name') }}</title>

    {{-- Preconnect para reduzir latência da fonte web --}}
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>

    {{-- Tabler Icons agora vêm do bundle compilado (Vite), não mais via CDN externo --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
</head>
<body class="h-full antialiased bg-gradient-to-br from-gray-50 to-gray-100">
    {{ $slot }}

    <x-toast-container />

    @livewireScripts
</body>
</html>
