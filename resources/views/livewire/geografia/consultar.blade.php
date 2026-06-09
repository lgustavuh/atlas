<div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Geografia</h1>
        <p class="mt-1 text-sm text-gray-600">
            Consulta de estados e cidades brasileiras.
            @if ($totalCidades < 100)
                <span class="text-amber-700">
                    <i class="ti ti-alert-triangle ml-1" aria-hidden="true"></i>
                    Apenas {{ $totalCidades }} cidades carregadas. Execute
                    <code class="bg-gray-100 px-1 rounded text-xs">php artisan etc:importar-cidades-ibge</code>
                    para popular todas as ~5.570.
                </span>
            @else
                {{ number_format($totalCidades, 0, ',', '.') }} cidades cadastradas.
            @endif
        </p>
    </div>

    <x-card class="mb-4" padding="p-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-input label="Buscar cidade" wire:model.live.debounce.400ms="search" placeholder="Nome da cidade..." />
            <x-select label="Estado (UF)" wire:model.live="filterEstado">
                <option value="">Todos os estados</option>
                @foreach ($estados as $e)
                    <option value="{{ $e->id }}">{{ $e->nome }} ({{ $e->uf }})</option>
                @endforeach
            </x-select>
        </div>
    </x-card>

    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cidade</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">UF</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Código IBGE</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Capital</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($cidades as $cidade)
                        <tr wire:key="cidade-{{ $cidade->id }}" class="hover:bg-gray-50">
                            <td class="px-6 py-3 whitespace-nowrap text-sm font-medium text-gray-900">{{ $cidade->nome }}</td>
                            <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600 font-mono">{{ $cidade->estado?->uf }}</td>
                            <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">{{ $cidade->estado?->nome }}</td>
                            <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600 font-mono">{{ $cidade->codigo_ibge ?? '—' }}</td>
                            <td class="px-6 py-3 whitespace-nowrap text-center">
                                @if ($cidade->capital)
                                    <x-badge variant="indigo" icon="star">Capital</x-badge>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <i class="ti ti-map-off text-4xl text-gray-300 mb-2 block" aria-hidden="true"></i>
                                <p class="text-sm text-gray-500">Nenhuma cidade encontrada.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($cidades->hasPages())
            <div class="px-6 py-3 border-t border-gray-200">{{ $cidades->links() }}</div>
        @endif
    </x-card>
</div>
