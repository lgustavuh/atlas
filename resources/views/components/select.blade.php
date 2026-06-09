@props([
    'label' => null,
    'name' => null,
    'placeholder' => null,
    'required' => false,
    'options' => [],          // ['valor' => 'Texto']
])

@php
    $id = $attributes->get('id') ?? $name;
    $hasError = $name && $errors->has($name);
    $classes = 'block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm';
    if ($hasError) {
        $classes .= ' border-red-500 focus:border-red-500 focus:ring-red-500';
    }
@endphp

<div>
    @if ($label)
        <label for="{{ $id }}" class="block text-sm font-medium text-gray-700 mb-1">
            {{ $label }}
            @if ($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <select
        id="{{ $id }}"
        @if ($name) name="{{ $name }}" @endif
        @if ($required) required @endif
        {{ $attributes->except(['id'])->merge(['class' => $classes]) }}
    >
        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif

        @if (count($options))
            @foreach ($options as $value => $text)
                <option value="{{ $value }}">{{ $text }}</option>
            @endforeach
        @else
            {{ $slot }}
        @endif
    </select>

    @if ($name)
        @error($name)
            <p class="mt-1 text-sm text-red-600 flex items-center">
                <i class="ti ti-alert-circle mr-1 text-xs" aria-hidden="true"></i>
                {{ $message }}
            </p>
        @enderror
    @endif
</div>
