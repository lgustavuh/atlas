@php
    use App\View\Components\NavigationMenu;
    $sections = NavigationMenu::sections();
@endphp

<aside
    x-data="{ collapsed: $persist(false).as('sidebar_collapsed') }"
    :class="collapsed ? 'lg:w-16' : 'lg:w-64'"
    class="hidden lg:flex flex-col bg-gray-900 text-gray-100 transition-all duration-200"
>
    {{-- Header da sidebar --}}
    <div class="h-16 flex items-center justify-between px-4 border-b border-gray-800">
        <a href="{{ route('dashboard') }}" wire:navigate.hover
           class="flex items-center min-w-0"
           :class="collapsed ? 'justify-center w-full' : ''">
            <div class="w-8 h-8 bg-indigo-600 rounded flex-shrink-0 flex items-center justify-center">
                <i class="ti ti-world text-white text-lg" aria-hidden="true"></i>
            </div>
            <span x-show="!collapsed" x-transition.opacity class="ml-3 font-semibold text-sm truncate">
                {{ config('app.name') }}
            </span>
        </a>

        <button
            x-show="!collapsed"
            @click="collapsed = true"
            class="text-gray-400 hover:text-white"
            title="Recolher menu"
        >
            <i class="ti ti-layout-sidebar-left-collapse text-lg" aria-hidden="true"></i>
        </button>
    </div>

    {{-- Botão expandir (quando colapsado) --}}
    <div x-show="collapsed" class="px-2 py-2 border-b border-gray-800">
        <button
            @click="collapsed = false"
            class="w-full flex items-center justify-center text-gray-400 hover:text-white py-1"
            title="Expandir menu"
        >
            <i class="ti ti-layout-sidebar-right-collapse text-lg" aria-hidden="true"></i>
        </button>
    </div>

    {{-- Navegação --}}
    <nav class="flex-1 overflow-y-auto px-2 py-3 space-y-4">
        @foreach ($sections as $sectionKey => $section)
            <div>
                {{-- Título da seção --}}
                <h3 x-show="!collapsed" x-transition.opacity
                    class="px-3 mb-1 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                    {{ $section['label'] }}
                </h3>
                <div x-show="collapsed" class="border-t border-gray-800 my-1"></div>

                <ul class="space-y-0.5">
                    @foreach ($section['items'] as $item)
                        @php
                            $isActive = NavigationMenu::isActive($item['route']);
                        @endphp
                        <li>
                            <a
                                href="{{ route($item['route']) }}"
                                wire:navigate.hover
                                class="flex items-center px-3 py-2 text-sm rounded-md transition group {{ $isActive ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}"
                                :title="collapsed ? '{{ $item['label'] }}' : ''"
                            >
                                <i class="ti ti-{{ $item['icon'] }} text-lg flex-shrink-0" aria-hidden="true"></i>
                                <span x-show="!collapsed" x-transition.opacity class="ml-3 truncate">
                                    {{ $item['label'] }}
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>

    {{-- Footer com usuário --}}
    <div class="border-t border-gray-800 px-3 py-3" x-show="!collapsed" x-transition.opacity>
        <div class="flex items-center text-xs text-gray-400">
            <i class="ti ti-shield-check mr-1.5" aria-hidden="true"></i>
            Sistema seguro
        </div>
        <p class="text-xs text-gray-500 mt-1">v2.0 &middot; PostgreSQL 16</p>
    </div>
</aside>

{{-- Sidebar Mobile (drawer) --}}
<div
    x-data="{ open: false }"
    @open-mobile-menu.window="open = true"
    x-show="open"
    style="display: none;"
    class="lg:hidden fixed inset-0 z-40"
    role="dialog"
    aria-modal="true"
>
    {{-- Overlay --}}
    <div
        x-show="open"
        x-transition.opacity
        @click="open = false"
        class="fixed inset-0 bg-gray-600 bg-opacity-75"
    ></div>

    {{-- Drawer --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-in-out duration-300 transform"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in-out duration-300 transform"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        class="relative flex-1 flex flex-col max-w-xs w-full bg-gray-900 h-full"
    >
        <div class="h-16 flex items-center justify-between px-4 border-b border-gray-800">
            <a href="{{ route('dashboard') }}" wire:navigate.hover class="flex items-center">
                <div class="w-8 h-8 bg-indigo-600 rounded flex items-center justify-center">
                    <i class="ti ti-world text-white text-lg" aria-hidden="true"></i>
                </div>
                <span class="ml-3 font-semibold text-sm text-white">{{ config('app.name') }}</span>
            </a>
            <button @click="open = false" class="text-gray-400 hover:text-white">
                <i class="ti ti-x text-xl" aria-hidden="true"></i>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto px-2 py-3 space-y-4">
            @foreach ($sections as $section)
                <div>
                    <h3 class="px-3 mb-1 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                        {{ $section['label'] }}
                    </h3>
                    <ul class="space-y-0.5">
                        @foreach ($section['items'] as $item)
                            @php $isActive = NavigationMenu::isActive($item['route']); @endphp
                            <li>
                                <a href="{{ route($item['route']) }}" wire:navigate.hover
                                   @click="open = false"
                                   class="flex items-center px-3 py-2 text-sm rounded-md {{ $isActive ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                                    <i class="ti ti-{{ $item['icon'] }} text-lg mr-3" aria-hidden="true"></i>
                                    {{ $item['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </nav>
    </div>
</div>
