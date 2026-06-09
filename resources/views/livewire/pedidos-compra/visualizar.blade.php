<div class="p-4 sm:p-6 lg:p-8 max-w-5xl mx-auto w-full">

    {{-- Header --}}
    <div class="mb-6 flex items-start justify-between">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-gray-900 font-mono">{{ $pedido->numero }}</h1>
                <x-badge :variant="$pedido->status_cor">{{ $pedido->status_label }}</x-badge>
            </div>
            <p class="mt-1 text-sm text-gray-600">
                Fornecedor: {{ $pedido->fornecedor?->nome_fantasia ?: $pedido->fornecedor?->razao_social }}
                &middot; Solicitado por {{ $pedido->solicitante?->nome }}
            </p>
        </div>
        <a href="{{ route('pedidos-compra.index') }}" wire:navigate.hover class="text-sm text-gray-600 hover:text-gray-900">
            <i class="ti ti-arrow-left mr-1" aria-hidden="true"></i> Voltar
        </a>
    </div>

    {{-- Barra de ações de workflow --}}
    <x-card class="mb-4" padding="p-4">
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-sm text-gray-500 mr-2">Ações:</span>

            @if ($pedido->status === 'rascunho' || $pedido->status === 'rejeitado')
                @if ($podeEditar)
                    <a href="{{ route('pedidos-compra.edit', $pedido->id) }}" wire:navigate.hover>
                        <x-button variant="secondary" size="sm" icon="edit">Editar</x-button>
                    </a>
                    <x-button size="sm" icon="send" wire:click="enviarParaLiberacao"
                              wire:confirm="Enviar este pedido para liberação?">
                        Enviar para liberação
                    </x-button>
                @endif
            @endif

            @if ($pedido->status === 'aguardando_liberacao' && $podeLiberar)
                <x-button variant="success" size="sm" icon="check" wire:click="abrirAcao('liberar')">Liberar</x-button>
                <x-button variant="danger" size="sm" icon="x" wire:click="abrirAcao('rejeitar_liberacao')">Rejeitar</x-button>
            @endif

            @if ($pedido->status === 'aguardando_aprovacao' && $podeAprovar)
                <x-button variant="success" size="sm" icon="checks" wire:click="abrirAcao('aprovar')">Aprovar</x-button>
                <x-button variant="danger" size="sm" icon="x" wire:click="abrirAcao('rejeitar_aprovacao')">Rejeitar</x-button>
            @endif

            @if ($pedido->status === 'aprovado' && $podeAprovar)
                <x-button size="sm" icon="truck-delivery" wire:click="enviarAoFornecedor"
                          wire:confirm="Confirmar envio do pedido ao fornecedor?">
                    Enviar ao fornecedor
                </x-button>
            @endif

            @if (in_array($pedido->status, ['enviado_fornecedor', 'parcialmente_recebido']) && $podeReceber)
                <x-button size="sm" icon="package-import" wire:click="abrirRecebimento">Registrar recebimento</x-button>
            @endif

            @if ($podeCancelar)
                <x-button variant="ghost" size="sm" icon="ban" wire:click="abrirAcao('cancelar')">Cancelar pedido</x-button>
            @endif

            @if ($pedido->status === 'recebido')
                <span class="text-sm text-green-700 flex items-center">
                    <i class="ti ti-circle-check mr-1" aria-hidden="true"></i>
                    Pedido concluído
                </span>
            @endif
        </div>
    </x-card>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {{-- Itens --}}
        <x-card title="Itens" icon="list-details" class="lg:col-span-2" padding="p-0">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Material</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Qtd</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Preço</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Subtotal</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Recebido</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($pedido->itens as $item)
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-900">
                                    {{ $item->material?->nome ?? '—' }}
                                    <span class="text-xs text-gray-400">({{ $item->material?->unidade_medida }})</span>
                                    @if ($item->observacoes)
                                        <div class="text-xs text-gray-400">{{ $item->observacoes }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-600 text-right">
                                    {{ rtrim(rtrim(number_format((float)$item->quantidade, 4, ',', '.'), '0'), ',') }}
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-600 text-right">
                                    R$ {{ number_format((float) $item->preco_unitario, 2, ',', '.') }}
                                </td>
                                <td class="px-4 py-2 text-sm font-medium text-gray-900 text-right">
                                    R$ {{ number_format((float) $item->subtotal, 2, ',', '.') }}
                                </td>
                                <td class="px-4 py-2 text-sm text-right">
                                    @if ((float) $item->quantidade_recebida >= (float) $item->quantidade)
                                        <span class="text-green-600"><i class="ti ti-circle-check"></i></span>
                                    @elseif ((float) $item->quantidade_recebida > 0)
                                        <span class="text-amber-600 text-xs">
                                            {{ rtrim(rtrim(number_format((float)$item->quantidade_recebida, 4, ',', '.'), '0'), ',') }}
                                        </span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="3" class="px-4 py-2 text-sm text-right text-gray-600">Subtotal:</td>
                            <td class="px-4 py-2 text-sm font-medium text-right">R$ {{ number_format((float) $pedido->valor_total, 2, ',', '.') }}</td>
                            <td></td>
                        </tr>
                        @if ((float) $pedido->valor_desconto > 0)
                            <tr>
                                <td colspan="3" class="px-4 py-1 text-sm text-right text-gray-600">Desconto:</td>
                                <td class="px-4 py-1 text-sm text-red-600 text-right">- R$ {{ number_format((float) $pedido->valor_desconto, 2, ',', '.') }}</td>
                                <td></td>
                            </tr>
                        @endif
                        @if ((float) $pedido->valor_frete > 0)
                            <tr>
                                <td colspan="3" class="px-4 py-1 text-sm text-right text-gray-600">Frete:</td>
                                <td class="px-4 py-1 text-sm text-gray-900 text-right">+ R$ {{ number_format((float) $pedido->valor_frete, 2, ',', '.') }}</td>
                                <td></td>
                            </tr>
                        @endif
                        <tr class="border-t-2 border-gray-300">
                            <td colspan="3" class="px-4 py-2 text-base font-semibold text-right">Total final:</td>
                            <td class="px-4 py-2 text-base font-semibold text-indigo-700 text-right">R$ {{ number_format((float) $pedido->valor_final, 2, ',', '.') }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-card>

        {{-- Coluna lateral: dados + histórico --}}
        <div class="space-y-4">
            <x-card title="Informações" icon="info-circle" padding="p-4">
                <dl class="space-y-2 text-sm">
                    <div>
                        <dt class="text-xs text-gray-500">Data do pedido</dt>
                        <dd class="text-gray-900">{{ $pedido->data_pedido?->format('d/m/Y') }}</dd>
                    </div>
                    @if ($pedido->data_entrega_prevista)
                        <div>
                            <dt class="text-xs text-gray-500">Entrega prevista</dt>
                            <dd class="text-gray-900">{{ $pedido->data_entrega_prevista->format('d/m/Y') }}</dd>
                        </div>
                    @endif
                    @if ($pedido->forma_pagamento)
                        <div>
                            <dt class="text-xs text-gray-500">Pagamento</dt>
                            <dd class="text-gray-900">
                                {{ ucfirst(str_replace('_', ' ', $pedido->forma_pagamento)) }}
                                @if ($pedido->parcelas > 1) em {{ $pedido->parcelas }}x @endif
                            </dd>
                        </div>
                    @endif
                </dl>
                @if ($pedido->justificativa)
                    <div class="mt-3 pt-3 border-t border-gray-100">
                        <dt class="text-xs text-gray-500 mb-1">Justificativa</dt>
                        <dd class="text-sm text-gray-700">{{ $pedido->justificativa }}</dd>
                    </div>
                @endif
            </x-card>

            {{-- Histórico de aprovações --}}
            <x-card title="Histórico de aprovações" icon="history" padding="p-4">
                @forelse ($pedido->aprovacoes->sortByDesc('created_at') as $ap)
                    <div class="flex items-start gap-2 pb-3 mb-3 border-b border-gray-100 last:border-0 last:mb-0 last:pb-0">
                        <div class="flex-shrink-0 mt-0.5">
                            @if ($ap->decisao === 'aprovado')
                                <i class="ti ti-circle-check text-green-500" aria-hidden="true"></i>
                            @else
                                <i class="ti ti-circle-x text-red-500" aria-hidden="true"></i>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm text-gray-900">
                                {{ ucfirst($ap->etapa) }}: {{ $ap->decisao }}
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ $ap->user?->name }} &middot; {{ $ap->created_at?->format('d/m/Y H:i') }}
                            </p>
                            @if ($ap->comentario)
                                <p class="text-xs text-gray-600 mt-1 italic">"{{ $ap->comentario }}"</p>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400 italic">Nenhuma aprovação registrada ainda.</p>
                @endforelse
            </x-card>
        </div>
    </div>

    {{-- Modal de ação (liberar/aprovar/rejeitar/cancelar) --}}
    <x-modal name="showAcaoModal" max-width="md" :title="match($acao) {
        'liberar' => 'Liberar pedido',
        'rejeitar_liberacao', 'rejeitar_aprovacao' => 'Rejeitar pedido',
        'aprovar' => 'Aprovar pedido',
        'cancelar' => 'Cancelar pedido',
        default => 'Confirmar ação',
    }">
        <div class="px-6 py-4">
            @php $ehRejeicao = in_array($acao, ['rejeitar_liberacao', 'rejeitar_aprovacao', 'cancelar']); @endphp
            <p class="text-sm text-gray-700 mb-3">
                @switch($acao)
                    @case('liberar') Confirma a liberação deste pedido? Ele seguirá para aprovação final. @break
                    @case('aprovar') Confirma a aprovação final deste pedido? @break
                    @case('cancelar') Confirma o cancelamento deste pedido? @break
                    @default Informe o motivo da rejeição:
                @endswitch
            </p>
            <textarea wire:model="comentario" rows="3"
                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                      placeholder="{{ $ehRejeicao ? 'Motivo (obrigatório para rejeição)...' : 'Comentário (opcional)...' }}"></textarea>
            @error('comentario') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 flex justify-end space-x-2">
            <x-button variant="secondary" wire:click="$set('showAcaoModal', false)">Voltar</x-button>
            <x-button :variant="$ehRejeicao ? 'danger' : 'success'" wire:click="confirmarAcao">Confirmar</x-button>
        </div>
    </x-modal>

    {{-- Modal de recebimento --}}
    <x-modal name="showRecebimentoModal" max-width="2xl" title="Registrar recebimento">
        <div class="px-6 py-4 max-h-[60vh] overflow-y-auto">
            <p class="text-sm text-gray-600 mb-4">Informe a quantidade recebida de cada item. Recebimento parcial é permitido.</p>
            <div class="space-y-3">
                @foreach ($pedido->itens as $item)
                    <div wire:key="receb-{{ $item->id }}" class="flex items-center justify-between gap-3 border-b border-gray-100 pb-2">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm text-gray-900">{{ $item->material?->nome }}</p>
                            <p class="text-xs text-gray-500">
                                Pedido: {{ rtrim(rtrim(number_format((float)$item->quantidade, 4, ',', '.'), '0'), ',') }} {{ $item->material?->unidade_medida }}
                            </p>
                        </div>
                        <div class="w-32">
                            <input type="number" step="0.0001" min="0" max="{{ $item->quantidade }}"
                                   wire:model="quantidadesRecebidas.{{ $item->id }}"
                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm text-right">
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 flex justify-end space-x-2">
            <x-button variant="secondary" wire:click="$set('showRecebimentoModal', false)">Cancelar</x-button>
            <x-button wire:click="confirmarRecebimento">Salvar recebimento</x-button>
        </div>
    </x-modal>
</div>
