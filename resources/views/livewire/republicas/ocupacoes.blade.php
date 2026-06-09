<div class="p-4 sm:p-6 lg:p-8 max-w-5xl mx-auto w-full">

    <div class="mb-6">
        <a href="{{ route('republicas.index') }}" wire:navigate.hover class="text-sm text-indigo-600 hover:underline inline-flex items-center mb-2">
            <i class="ti ti-arrow-left mr-1" aria-hidden="true"></i> Voltar para Repúblicas
        </a>
        <h1 class="text-2xl font-bold text-gray-900">{{ $republica->nome }}</h1>
        <p class="mt-1 text-sm text-gray-600">{{ $republica->endereco }}@if ($republica->cidade) — {{ $republica->cidade->nome }} @endif</p>
    </div>

    {{-- Card de ocupação --}}
    <x-card class="mb-4">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div>
                <p class="text-xs text-gray-500">Capacidade</p>
                <p class="text-xl font-semibold text-gray-900">{{ $republica->capacidade_total }} vagas</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Ocupantes</p>
                <p class="text-xl font-semibold text-gray-900">{{ $republica->ocupantes_atuais_count }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Vagas livres</p>
                <p class="text-xl font-semibold {{ $republica->vagas_disponiveis === 0 ? 'text-red-600' : 'text-green-600' }}">
                    {{ $republica->vagas_disponiveis }}
                </p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Aluguel</p>
                <p class="text-xl font-semibold text-gray-900">
                    @if ($republica->aluguel_mensal)
                        R$ {{ number_format((float) $republica->aluguel_mensal, 2, ',', '.') }}
                    @else
                        —
                    @endif
                </p>
            </div>
        </div>
        @if ($republica->responsavel_externo_nome)
            <div class="mt-4 pt-3 border-t border-gray-100 text-sm text-gray-600">
                <i class="ti ti-user mr-1" aria-hidden="true"></i>
                Locador: <strong>{{ $republica->responsavel_externo_nome }}</strong>
                @if ($republica->responsavel_externo_telefone)
                    · {{ $republica->responsavel_externo_telefone }}
                @endif
            </div>
        @endif
    </x-card>

    {{-- Ocupantes atuais --}}
    <x-card class="mb-4" padding="p-0">
        <div class="px-6 py-3 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-sm font-medium text-gray-900">Ocupantes atuais ({{ $ocupacoesAtuais->count() }})</h2>
            @can('update', $republica)
                @if ($republica->vagas_disponiveis > 0)
                    <x-button wire:click="openAlocar" icon="user-plus" size="sm">Alocar colaborador</x-button>
                @else
                    <span class="text-xs text-red-600 font-medium">República lotada</span>
                @endif
            @endcan
        </div>
        @if ($ocupacoesAtuais->isEmpty())
            <div class="p-12 text-center">
                <i class="ti ti-bed-off text-4xl text-gray-300 mb-2 block" aria-hidden="true"></i>
                <p class="text-sm text-gray-500">Nenhum ocupante no momento.</p>
            </div>
        @else
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Colaborador</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cargo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Entrada</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Dias</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($ocupacoesAtuais as $o)
                        <tr wire:key="ocup-{{ $o->id }}" class="hover:bg-gray-50">
                            <td class="px-6 py-3 text-sm text-gray-900 font-medium">{{ $o->colaborador?->nome }}</td>
                            <td class="px-6 py-3 text-sm text-gray-600">{{ $o->colaborador?->cargo?->nome ?? '—' }}</td>
                            <td class="px-6 py-3 text-sm text-gray-600">{{ $o->data_entrada->format('d/m/Y') }}</td>
                            <td class="px-6 py-3 text-sm text-gray-600 text-center">{{ $o->dias_de_ocupacao }}</td>
                            <td class="px-6 py-3 text-right">
                                @can('update', $republica)
                                    <button wire:click="openSaida({{ $o->id }})"
                                            class="text-yellow-600 hover:text-yellow-900 text-sm inline-flex items-center">
                                        <i class="ti ti-door-exit mr-1" aria-hidden="true"></i> Dar saída
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-card>

    {{-- Histórico --}}
    @if ($historico->isNotEmpty())
        <x-card padding="p-0">
            <div class="px-6 py-3 border-b border-gray-200">
                <h2 class="text-sm font-medium text-gray-900">Histórico (últimas {{ $historico->count() }} saídas)</h2>
            </div>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Colaborador</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Período</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Duração</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($historico as $h)
                        <tr wire:key="hist-{{ $h->id }}">
                            <td class="px-6 py-3 text-sm text-gray-900">{{ $h->colaborador?->nome }}</td>
                            <td class="px-6 py-3 text-sm text-gray-600">
                                {{ $h->data_entrada->format('d/m/Y') }} — {{ $h->data_saida->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-600 text-center">{{ $h->dias_de_ocupacao }} dias</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-card>
    @endif

    {{-- Modal alocar --}}
    <x-modal name="showAlocarModal" max-width="lg" title="Alocar colaborador">
        <form wire:submit.prevent="alocar">
            <div class="px-6 py-4 space-y-4">
                <x-select label="Colaborador" name="colaborador_id" wire:model="colaborador_id" required>
                    <option value="">Selecione</option>
                    @forelse ($colaboradoresDisponiveis as $c)
                        <option value="{{ $c->id }}">{{ $c->nome }}</option>
                    @empty
                        <option disabled>Nenhum colaborador disponível</option>
                    @endforelse
                </x-select>
                <x-input label="Data de entrada" name="data_entrada" type="date" wire:model="data_entrada" required />
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                    <textarea wire:model="observacoes" rows="2"
                              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                </div>
            </div>
            <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 flex justify-end space-x-2">
                <x-button variant="secondary" type="button" wire:click="closeAlocar">Cancelar</x-button>
                <x-button type="submit" loading="alocar">Alocar</x-button>
            </div>
        </form>
    </x-modal>

    {{-- Modal saída --}}
    <x-modal name="showSaidaModal" max-width="md" title="Registrar saída">
        <form wire:submit.prevent="darSaida">
            <div class="px-6 py-4 space-y-3">
                <p class="text-sm text-gray-700">Registrar saída de <strong>{{ $saindoNome }}</strong>:</p>
                <x-input label="Data de saída" name="data_saida" type="date" wire:model="data_saida" required />
            </div>
            <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 flex justify-end space-x-2">
                <x-button variant="secondary" type="button" wire:click="closeSaida">Cancelar</x-button>
                <x-button type="submit" loading="darSaida">Confirmar saída</x-button>
            </div>
        </form>
    </x-modal>
</div>
