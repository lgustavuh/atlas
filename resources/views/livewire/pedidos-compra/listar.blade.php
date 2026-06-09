<div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full">

    <div class="sm:flex sm:items-center sm:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Pedidos de Compra</h1>
            <p class="mt-1 text-sm text-gray-600">Requisições de compra com fluxo de liberação e aprovação.</p>
        </div>
        <div class="mt-4 sm:mt-0 flex gap-2">
            @can('viewAny', App\Models\PedidoCompra::class)
                <x-button variant="secondary" icon="file-spreadsheet" wire:click="exportar">Exportar</x-button>
            @endcan
            @can('create', App\Models\PedidoCompra::class)
                <a href="{{ route('pedidos-compra.create') }}" wire:navigate.hover>
                    <x-button icon="plus">Novo pedido</x-button>
                </a>
            @endcan
        </div>
    </div>

    {{-- Resumo --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-md bg-yellow-50 flex items-center justify-center">
                    <i class="ti ti-clock text-yellow-600 text-xl" aria-hidden="true"></i>
                </div>
                <div class="ml-3">
                    <p class="text-xs text-gray-500">Pendentes de liberação/aprovação</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['pendentes'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-md bg-gray-100 flex items-center justify-center">
                    <i class="ti ti-file-pencil text-gray-600 text-xl" aria-hidden="true"></i>
                </div>
                <div class="ml-3">
                    <p class="text-xs text-gray-500">Rascunhos</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['rascunhos'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <x-card class="mb-4" padding="p-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-input label="Buscar" wire:model.live.debounce.400ms="search" placeholder="Número do pedido ou fornecedor..." />
            <x-select label="Status" wire:model.live="filterStatus">
                <option value="">Todos</option>
                <option value="rascunho">Rascunho</option>
                <option value="aguardando_liberacao">Aguardando liberação</option>
                <option value="liberado">Liberado</option>
                <option value="aguardando_aprovacao">Aguardando aprovação</option>
                <option value="aprovado">Aprovado</option>
                <option value="enviado_fornecedor">Enviado ao fornecedor</option>
                <option value="parcialmente_recebido">Parcialmente recebido</option>
                <option value="recebido">Recebido</option>
                <option value="cancelado">Cancelado</option>
                <option value="rejeitado">Rejeitado</option>
            </x-select>
        </div>
    </x-card>

    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Número</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fornecedor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Itens</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Valor final</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($pedidos as $pedido)
                        <tr wire:key="ped-{{ $pedido->id }}" class="hover:bg-gray-50 {{ in_array($pedido->status, ['aguardando_liberacao', 'aguardando_aprovacao']) ? 'bg-yellow-50/30' : '' }}">
                            <td class="px-6 py-3 whitespace-nowrap text-sm font-medium text-gray-900 font-mono">{{ $pedido->numero }}</td>
                            <td class="px-6 py-3 text-sm text-gray-600">
                                {{ $pedido->fornecedor?->nome_fantasia ?: $pedido->fornecedor?->razao_social ?? '—' }}
                                @if ($pedido->solicitante)
                                    <div class="text-xs text-gray-400">por {{ $pedido->solicitante->nome }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">{{ $pedido->data_pedido?->format('d/m/Y') }}</td>
                            <td class="px-6 py-3 whitespace-nowrap text-center">
                                <x-badge variant="gray">{{ $pedido->itens_count }}</x-badge>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-medium text-gray-900">
                                R$ {{ number_format((float) $pedido->valor_final, 2, ',', '.') }}
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                <x-badge :variant="$pedido->status_cor">{{ $pedido->status_label }}</x-badge>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                <a href="{{ route('pedidos-compra.show', $pedido->id) }}" wire:navigate.hover
                                   class="text-gray-500 hover:text-gray-700" title="Ver detalhes">
                                    <i class="ti ti-eye text-lg" aria-hidden="true"></i>
                                </a>
                                <a href="{{ route('pdf.pedido-compra', $pedido) }}" target="_blank"
                                   class="text-gray-600 hover:text-gray-900" title="Gerar PDF">
                                    <i class="ti ti-file-type-pdf text-lg" aria-hidden="true"></i>
                                </a>
                                @can('update', $pedido)
                                    @if ($pedido->podeEditar())
                                        <a href="{{ route('pedidos-compra.edit', $pedido->id) }}" wire:navigate.hover
                                           class="text-indigo-600 hover:text-indigo-900" title="Editar">
                                            <i class="ti ti-edit text-lg" aria-hidden="true"></i>
                                        </a>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <i class="ti ti-shopping-cart-off text-4xl text-gray-300 mb-2 block" aria-hidden="true"></i>
                                <p class="text-sm text-gray-500">Nenhum pedido de compra encontrado.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($pedidos->hasPages())
            <div class="px-6 py-3 border-t border-gray-200">{{ $pedidos->links() }}</div>
        @endif
    </x-card>
</div>
