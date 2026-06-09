@props([
    'title' => null,
    'subtitle' => null,
    'icon' => null,
    'padding' => 'p-6',
])

<div {{ $attributes->merge(['class' => 'bg-white rounded-lg shadow-sm border border-gray-200']) }}>
    @if ($title || $subtitle)
        <div class="px-6 py-4 border-b border-gray-200 flex items-start justify-between">
            <div class="flex items-start">
                @if ($icon)
                    <div class="flex-shrink-0 mr-3">
                        <i class="ti ti-{{ $icon }} text-indigo-600 text-xl" aria-hidden="true"></i>
                    </div>
                @endif
                <div>
                    @if ($title)
                        <h3 class="text-lg font-medium text-gray-900">{{ $title }}</h3>
                    @endif
                    @if ($subtitle)
                        <p class="text-sm text-gray-500 mt-0.5">{{ $subtitle }}</p>
                    @endif
                </div>
            </div>

            @isset ($actions)
                <div class="flex items-center space-x-2">
                    {{ $actions }}
                </div>
            @endisset
        </div>
    @endif

    <div class="{{ $padding }}">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 rounded-b-lg">
            {{ $footer }}
        </div>
    @endisset
</div>
