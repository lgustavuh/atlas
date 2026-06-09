@props([
    'label' => null,
    'name' => null,
    'type' => 'text',
    'hint' => null,
    'required' => false,
    'error' => null,
])

@php
    $id = $attributes->get('id') ?? $name;
    $hasError = $error || ($name && $errors->has($name));
    $inputClasses = 'block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm disabled:bg-gray-50 disabled:text-gray-500';
    if ($hasError) {
        $inputClasses .= ' border-red-500 focus:border-red-500 focus:ring-red-500';
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

    <input
        type="{{ $type }}"
        id="{{ $id }}"
        @if ($name) name="{{ $name }}" @endif
        @if ($required) required @endif
        {{ $attributes->except(['id'])->merge(['class' => $inputClasses]) }}
    >

    @if ($hint && !$hasError)
        <p class="mt-1 text-xs text-gray-500">{{ $hint }}</p>
    @endif

    @if ($name)
        @error($name)
            <p class="mt-1 text-sm text-red-600 flex items-center">
                <i class="ti ti-alert-circle mr-1 text-xs" aria-hidden="true"></i>
                {{ $message }}
            </p>
        @enderror
    @endif

    @if ($error)
        <p class="mt-1 text-sm text-red-600 flex items-center">
            <i class="ti ti-alert-circle mr-1 text-xs" aria-hidden="true"></i>
            {{ $error }}
        </p>
    @endif
</div>
