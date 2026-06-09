<div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full">

    <div class="sm:flex sm:items-center sm:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Auditoria</h1>
            <p class="mt-1 text-sm text-gray-600">Registro de todas as atividades do sistema.</p>
        </div>
        <div class="mt-4 sm:mt-0 flex gap-2">
            <x-button variant="secondary" icon="file-spreadsheet" wire:click="exportar">Exportar</x-button>
            @if ($search !== '' || $filterLogName !== '' || $filterEvent !== '' || $filterCauserId || $filterDataDe || $filterDataAte)
                <x-button variant="secondary" wire:click="limparFiltros" icon="filter-off">Limpar filtros</x-button>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-3 gap-4 mb-4">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <p class="text-xs text-gray-500">Total registrado</p>
            <p class="text-2xl font-semibold text-gray-900">{{ number_format($stats['total'], 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <p class="text-xs text-gray-500">Hoje</p>
            <p class="text-2xl font-semibold text-gray-900">{{ $stats['hoje'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <p class="text-xs text-gray-500">Última hora</p>
            <p class="text-2xl font-semibold text-gray-900">{{ $stats['ultima_hora'] }}</p>
        </div>
    </div>

    <x-card class="mb-4" padding="p-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-3">
            <x-input label="Buscar descrição" wire:model.live.debounce.400ms="search" placeholder="Termos da ação..." />
            <x-select label="Módulo" wire:model.live="filterLogName">
                <option value="">Todos</option>
                @foreach ($logNames as $ln)
                    <option value="{{ $ln }}">{{ $ln }}</option>
                @endforeach
            </x-select>
            <x-select label="Evento" wire:model.live="filterEvent">
                <option value="">Todos</option>
                @foreach ($eventos as $ev)
                    <option value="{{ $ev }}">{{ $ev }}</option>
                @endforeach
            </x-select>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-select label="Usuário" wire:model.live="filterCauserId">
                <option value="">Todos</option>
                @foreach ($usuarios as $u)
                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                @endforeach
            </x-select>
            <x-input label="Data de" type="date" wire:model.live="filterDataDe" />
            <x-input label="Data até" type="date" wire:model.live="filterDataAte" />
        </div>
    </x-card>

    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quando</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quem</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Módulo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Evento</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Descrição</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($atividades as $a)
                        <tr wire:key="act-{{ $a->id }}" class="hover:bg-gray-50">
                            <td class="px-6 py-3 whitespace-nowrap text-xs text-gray-600">
                                <div>{{ $a->created_at?->format('d/m/Y') }}</div>
                                <div class="text-gray-400">{{ $a->created_at?->format('H:i:s') }}</div>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-sm">
                                @if ($a->causer)
                                    <div class="text-gray-900">{{ $a->causer->name }}</div>
                                    <div class="text-xs text-gray-400">{{ $a->causer->email }}</div>
                                @else
                                    <span class="text-gray-400 italic">sistema</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                @if ($a->log_name)
                                    <x-badge variant="gray">{{ $a->log_name }}</x-badge>
                                @endif
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                @php
                                    $eventCor = match ($a->event) {
                                        'created' => 'green',
                                        'updated' => 'blue',
                                        'deleted' => 'red',
                                        default => 'gray',
                                    };
                                @endphp
                                @if ($a->event)
                                    <x-badge :variant="$eventCor">{{ $a->event }}</x-badge>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-700 max-w-md truncate" title="{{ $a->description }}">
                                {{ $a->description }}
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-xs text-gray-500 font-mono">
                                {{ $a->ip_address ?? '—' }}
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-right">
                                <button wire:click="abrirDetalhe({{ $a->id }})" class="text-indigo-600 hover:text-indigo-900 text-sm">
                                    <i class="ti ti-search" aria-hidden="true"></i> Detalhes
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <i class="ti ti-history-off text-4xl text-gray-300 mb-2 block" aria-hidden="true"></i>
                                <p class="text-sm text-gray-500">Nenhuma atividade encontrada com esses filtros.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($atividades->hasPages())
            <div class="px-6 py-3 border-t border-gray-200">{{ $atividades->links() }}</div>
        @endif
    </x-card>

    {{-- Modal de detalhe --}}
    <x-modal name="showDetalhe" max-width="3xl" title="Detalhes da atividade">
        @if ($detalhe)
            <div class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Data/hora</p>
                        <p class="text-gray-900">{{ $detalhe->created_at?->format('d/m/Y H:i:s') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Usuário</p>
                        <p class="text-gray-900">
                            {{ $detalhe->causer?->name ?? 'Sistema' }}
                            @if ($detalhe->causer)
                                <span class="text-gray-400 text-xs block">{{ $detalhe->causer->email }}</span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Módulo</p>
                        <p class="text-gray-900 font-mono">{{ $detalhe->log_name ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Evento</p>
                        <p class="text-gray-900">{{ $detalhe->event ?? '—' }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-xs text-gray-500 uppercase">Recurso afetado</p>
                        <p class="text-gray-900 font-mono text-xs">
                            {{ $detalhe->subject_type ? class_basename($detalhe->subject_type) : '—' }}
                            @if ($detalhe->subject_id) #{{ $detalhe->subject_id }} @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">IP</p>
                        <p class="text-gray-900 font-mono text-xs">{{ $detalhe->ip_address ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">User-agent</p>
                        <p class="text-gray-900 text-xs truncate" title="{{ $detalhe->user_agent }}">{{ $detalhe->user_agent ?? '—' }}</p>
                    </div>
                </div>

                <div>
                    <p class="text-xs text-gray-500 uppercase mb-1">Descrição</p>
                    <p class="text-sm text-gray-900 bg-gray-50 rounded-md p-3">{{ $detalhe->description }}</p>
                </div>

                @php
                    $props = $detalhe->properties;
                    if ($props instanceof \Illuminate\Support\Collection) {
                        $props = $props->toArray();
                    }
                    $attrs = $props['attributes'] ?? null;
                    $old = $props['old'] ?? null;
                @endphp

                @if ($detalhe->event === 'updated' && $old && $attrs)
                    <div>
                        <p class="text-xs text-gray-500 uppercase mb-2">Alterações</p>
                        <div class="border border-gray-200 rounded-md overflow-hidden">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Campo</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Antes</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Depois</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($attrs as $campo => $novo)
                                        <tr>
                                            <td class="px-3 py-2 font-mono text-xs text-gray-700">{{ $campo }}</td>
                                            <td class="px-3 py-2 text-xs text-red-700 line-through">
                                                {{ is_scalar($old[$campo] ?? null) ? ($old[$campo] ?? 'null') : json_encode($old[$campo] ?? null) }}
                                            </td>
                                            <td class="px-3 py-2 text-xs text-green-700">
                                                {{ is_scalar($novo) ? $novo : json_encode($novo) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @elseif ($attrs)
                    <div>
                        <p class="text-xs text-gray-500 uppercase mb-2">Valores</p>
                        <div class="border border-gray-200 rounded-md overflow-hidden">
                            <table class="min-w-full text-sm">
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($attrs as $campo => $valor)
                                        <tr>
                                            <td class="px-3 py-2 font-mono text-xs text-gray-700 w-1/3">{{ $campo }}</td>
                                            <td class="px-3 py-2 text-xs text-gray-900">
                                                {{ is_scalar($valor) ? $valor : json_encode($valor) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
            <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 flex justify-end">
                <x-button variant="secondary" wire:click="fecharDetalhe">Fechar</x-button>
            </div>
        @endif
    </x-modal>
</div>
