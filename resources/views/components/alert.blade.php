@props([
    'variant' => 'info',     // success, error, warning, info
    'title' => null,
    'dismissible' => false,
])

@php
    $config = [
        'success' => ['bg' => 'bg-green-50', 'border' => 'border-green-200', 'text' => 'text-green-800', 'icon' => 'circle-check', 'iconColor' => 'text-green-500'],
        'error' => ['bg' => 'bg-red-50', 'border' => 'border-red-200', 'text' => 'text-red-800', 'icon' => 'alert-circle', 'iconColor' => 'text-red-500'],
        'warning' => ['bg' => 'bg-yellow-50', 'border' => 'border-yellow-200', 'text' => 'text-yellow-800', 'icon' => 'alert-triangle', 'iconColor' => 'text-yellow-500'],
        'info' => ['bg' => 'bg-blue-50', 'border' => 'border-blue-200', 'text' => 'text-blue-800', 'icon' => 'info-circle', 'iconColor' => 'text-blue-500'],
    ];
    $c = $config[$variant] ?? $config['info'];
@endphp

<div
    @if ($dismissible) x-data="{ show: true }" x-show="show" @endif
    class="rounded-md {{ $c['bg'] }} border {{ $c['border'] }} p-4"
    role="alert"
>
    <div class="flex">
        <div class="flex-shrink-0">
            <i class="ti ti-{{ $c['icon'] }} {{ $c['iconColor'] }}" aria-hidden="true"></i>
        </div>
        <div class="ml-3 flex-1">
            @if ($title)
                <h4 class="text-sm font-medium {{ $c['text'] }}">{{ $title }}</h4>
            @endif
            <div class="text-sm {{ $c['text'] }} {{ $title ? 'mt-1' : '' }}">
                {{ $slot }}
            </div>
        </div>
        @if ($dismissible)
            <button @click="show = false" class="ml-3 flex-shrink-0 {{ $c['iconColor'] }} hover:opacity-75">
                <i class="ti ti-x" aria-hidden="true"></i>
                <span class="sr-only">Fechar</span>
            </button>
        @endif
    </div>
</div>
