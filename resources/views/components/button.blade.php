@props([
    'variant' => 'primary', // primary, secondary, danger, ghost, success
    'size' => 'md',          // sm, md, lg
    'icon' => null,          // Tabler icon name (sem o "ti-")
    'loading' => null,       // wire:loading target
])

@php
    $base = 'inline-flex items-center justify-center font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 transition disabled:opacity-50 disabled:cursor-not-allowed';

    $sizes = [
        'sm' => 'px-3 py-1.5 text-xs',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'px-6 py-3 text-base',
    ];

    $variants = [
        'primary' => 'border border-transparent text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500',
        'secondary' => 'border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 focus:ring-indigo-500',
        'danger' => 'border border-transparent text-white bg-red-600 hover:bg-red-700 focus:ring-red-500',
        'success' => 'border border-transparent text-white bg-green-600 hover:bg-green-700 focus:ring-green-500',
        'ghost' => 'text-gray-700 hover:bg-gray-100 focus:ring-indigo-500 shadow-none',
    ];

    $classes = $base . ' ' . $sizes[$size] . ' ' . $variants[$variant];
@endphp

<button {{ $attributes->merge(['type' => 'button', 'class' => $classes]) }}>
    @if ($loading)
        <span wire:loading.remove wire:target="{{ $loading }}" class="inline-flex items-center">
            @if ($icon)
                <i class="ti ti-{{ $icon }} mr-1.5" aria-hidden="true"></i>
            @endif
            {{ $slot }}
        </span>
        <span wire:loading wire:target="{{ $loading }}" class="inline-flex items-center">
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Aguarde...
        </span>
    @else
        @if ($icon)
            <i class="ti ti-{{ $icon }} mr-1.5" aria-hidden="true"></i>
        @endif
        {{ $slot }}
    @endif
</button>
