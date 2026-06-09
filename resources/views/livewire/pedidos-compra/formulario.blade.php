<div class="p-4 sm:p-6 lg:p-8 max-w-5xl mx-auto w-full">

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                {{ $editando ? 'Editar Pedido de Compra' : 'Novo Pedido de Compra' }}
            </h1>
            <p class="mt-1 text-sm text-gray-600">Preencha o cabeçalho e adicione os itens.</p>
        </div>
        <a href="{{ route('pedidos-compra.index') }}" wire:navigate.hover class="text-sm text-gray-600 hover:text-gray-900">
            <i class="ti ti-arrow-left mr-1" aria-hidden="true"></i> Voltar
        </a>
    </div>

    <form wire:submit.prevent="salvar" class="space-y-4">

        {{-- Cabeçalho --}}
        <x-card title="Dados do pedido" icon="file-invoice">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-select label="Fornecedor" name="fornecedor_id" wire:model="fornecedor_id" required>
                    <option value="">Selecione</option>
                    @foreach ($fornecedores as $f)
                        <option value="{{ $f->id }}">{{ $f->nome_fantasia ?: $f->razao_social }}</option>
                    @endforeach
                </x-select>

                <x-select label="Solicitante" name="solicitante_id" wire:model="solicitante_id" required>
                    <option value="">Selecione</option>
                    @foreach ($colaboradores as $col)
                        <option value="{{ $col->id }}">{{ $col->nome }}</option>
                    @endforeach
                </x-select>

                <x-input label="Data do pedido" name="data_pedido" type="date" wire:model="data_pedido" required />
                <x-input label="Entrega prevista" name="data_entrega_prevista" type="date" wire:model="data_entrega_prevista" />

                <x-select label="Forma de pagamento" name="forma_pagamento" wire:model="forma_pagamento">
                    <option value="">Selecione</option>
                    <option value="a_vista">À vista</option>
                    <option value="boleto">Boleto</option>
                    <option value="transferencia">Transferência</option>
                    <option value="cartao">Cartão</option>
                    <option value="pix">PIX</option>
                    <option value="cheque">Cheque</option>
                    <option value="parcelado">Parcelado</option>
                    <option value="outro">Outro</option>
                </x-select>

                <x-input label="Parcelas" name="parcelas" type="number" min="1" max="60" wire:model="parcelas" />
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Justificativa</label>
                <textarea wire:model="justificativa" rows="2"
                          class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                          placeholder="Por que este pedido é necessário?"></textarea>
                @error('justificativa') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </x-card>

        {{-- Itens --}}
        <x-card title="Itens do pedido" icon="list-details">
            <x-slot:actions>
                <x-button type="button" variant="secondary" size="sm" icon="plus" wire:click="adicionarItem">
                    Adicionar item
                </x-button>
            </x-slot:actions>

            @error('itens') <div class="mb-3"><x-alert variant="error">{{ $message }}</x-alert></div> @enderror

            <div class="space-y-3">
                @foreach ($itens as $index => $item)
                    <div wire:key="item-{{ $index }}" class="border border-gray-200 rounded-md p-3 bg-gray-50">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-start">
                            {{-- Material --}}
                            <div class="md:col-span-5">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Material</label>
                                <select wire:model.live="itens.{{ $index }}.material_id"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                    <option value="">Selecione</option>
                                    @foreach ($materiais as $m)
                                        <option value="{{ $m->id }}">{{ $m->nome }} ({{ $m->unidade_medida }})</option>
                                    @endforeach
                                </select>
                                @error("itens.{$index}.material_id") <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            {{-- Quantidade --}}
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Qtd.</label>
                                <input type="number" step="0.0001" min="0" wire:model.live="itens.{{ $index }}.quantidade"
                                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                @error("itens.{$index}.quantidade") <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            {{-- Preço unitário --}}
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Preço unit.</label>
                                <input type="number" step="0.0001" min="0" wire:model.live="itens.{{ $index }}.preco_unitario"
                                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                @error("itens.{$index}.preco_unitario") <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            {{-- Subtotal --}}
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Subtotal</label>
                                <div class="py-2 text-sm font-medium text-gray-900">
                                    R$ {{ number_format($this->subtotalItem($index), 2, ',', '.') }}
                                </div>
                            </div>

                            {{-- Remover --}}
                            <div class="md:col-span-1 flex items-end justify-end h-full">
                                <button type="button" wire:click="removerItem({{ $index }})"
                                        class="text-red-500 hover:text-red-700 p-2" title="Remover item">
                                    <i class="ti ti-trash" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-card>

        {{-- Totais --}}
        <x-card title="Valores" icon="calculator">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-3">
                    <x-input label="Desconto (R$)" name="valor_desconto" type="number" step="0.01" min="0" wire:model.live="valor_desconto" />
                    <x-input label="Frete (R$)" name="valor_frete" type="number" step="0.01" min="0" wire:model.live="valor_frete" />
                </div>

                <div class="bg-gray-50 rounded-md p-4 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Subtotal dos itens:</span>
                        <span class="font-medium">R$ {{ number_format($this->total_itens, 2, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Desconto:</span>
                        <span class="text-red-600">- R$ {{ number_format((float) $valor_desconto, 2, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Frete:</span>
                        <span class="text-gray-900">+ R$ {{ number_format((float) $valor_frete, 2, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-base font-semibold pt-2 border-t border-gray-300">
                        <span>Total final:</span>
                        <span class="text-indigo-700">R$ {{ number_format($this->valor_final, 2, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                <textarea wire:model="observacoes" rows="2"
                          class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
            </div>
        </x-card>

        <div class="flex items-center justify-between">
            <a href="{{ route('pedidos-compra.index') }}" wire:navigate.hover class="text-sm text-gray-600 hover:text-gray-900">
                Cancelar
            </a>
            <x-button type="submit" loading="salvar" icon="device-floppy">
                {{ $editando ? 'Salvar alterações' : 'Criar pedido (rascunho)' }}
            </x-button>
        </div>
    </form>
</div>
