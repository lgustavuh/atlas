@props([
    'breadcrumbs' => [],
])

<header class="bg-white shadow-sm border-b border-gray-200">
    <div class="px-4 sm:px-6 lg:px-8">
        <div class="h-16 flex items-center justify-between">

            {{-- Lado esquerdo: hamburger mobile + breadcrumbs --}}
            <div class="flex items-center min-w-0">
                {{-- Hamburger (só mobile) --}}
                <button
                    @click="$dispatch('open-mobile-menu')"
                    class="lg:hidden -ml-1 mr-3 p-2 rounded-md text-gray-500 hover:bg-gray-100 focus:outline-none"
                >
                    <i class="ti ti-menu-2 text-xl" aria-hidden="true"></i>
                    <span class="sr-only">Abrir menu</span>
                </button>

                {{-- Breadcrumbs --}}
                @if (!empty($breadcrumbs))
                    <nav class="hidden sm:flex" aria-label="Breadcrumb">
                        <ol class="flex items-center space-x-2 text-sm">
                            <li>
                                <a href="{{ route('dashboard') }}" wire:navigate.hover class="text-gray-500 hover:text-gray-700">
                                    <i class="ti ti-home" aria-hidden="true"></i>
                                    <span class="sr-only">Início</span>
                                </a>
                            </li>
                            @foreach ($breadcrumbs as $crumb)
                                <li class="flex items-center">
                                    <i class="ti ti-chevron-right text-gray-400 text-xs mx-1" aria-hidden="true"></i>
                                    @if (is_array($crumb) && isset($crumb['url']))
                                        <a href="{{ $crumb['url'] }}" wire:navigate.hover class="text-gray-500 hover:text-gray-700">
                                            {{ $crumb['label'] }}
                                        </a>
                                    @else
                                        <span class="text-gray-900 font-medium">{{ is_array($crumb) ? $crumb['label'] : $crumb }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </nav>
                @else
                    <h1 class="text-lg font-medium text-gray-900 truncate">
                        {{ $slot ?? 'Dashboard' }}
                    </h1>
                @endif
            </div>

            {{-- Lado direito: user menu --}}
            <div class="flex items-center space-x-2">
                {{-- User dropdown --}}
                <div x-data="{ open: false }" class="relative">
                    <button
                        @click="open = !open"
                        @click.away="open = false"
                        class="flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-medium">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <span class="hidden md:flex md:items-center ml-2">
                            <span class="text-sm font-medium text-gray-700">
                                {{ auth()->user()->first_name }}
                            </span>
                            <i class="ti ti-chevron-down text-xs text-gray-500 ml-1" aria-hidden="true"></i>
                        </span>
                    </button>

                    {{-- Dropdown --}}
                    <div
                        x-show="open"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                        style="display: none;"
                        class="absolute right-0 mt-2 w-64 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 py-1 z-50"
                    >
                        {{-- Cabeçalho do dropdown --}}
                        <div class="px-4 py-3 border-b border-gray-100">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ auth()->user()->email }}</p>
                            @php
                                $roles = auth()->user()->roles->pluck('display_name')->filter()->take(2)->implode(', ');
                            @endphp
                            @if ($roles)
                                <p class="text-xs text-indigo-600 mt-1">{{ $roles }}</p>
                            @endif
                        </div>

                        <a href="{{ route('profile.edit') }}" wire:navigate.hover
                           class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="ti ti-user mr-2 text-base" aria-hidden="true"></i>
                            Meu Perfil
                        </a>
                        <a href="{{ route('password.change') }}" wire:navigate.hover
                           class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="ti ti-key mr-2 text-base" aria-hidden="true"></i>
                            Trocar senha
                        </a>

                        <div class="border-t border-gray-100 my-1"></div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                <i class="ti ti-logout mr-2 text-base" aria-hidden="true"></i>
                                Sair do sistema
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
