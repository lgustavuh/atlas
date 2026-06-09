<div class="p-4 sm:p-6 lg:p-8 max-w-5xl mx-auto w-full">

    {{-- Header --}}
    <div class="mb-6 flex items-start justify-between">
        <div class="flex items-center min-w-0">
            @if ($colaborador->foto_path)
                <img src="{{ $colaborador->foto_url }}" alt="" class="w-16 h-16 rounded-full object-cover flex-shrink-0">
            @else
                <div class="w-16 h-16 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-medium text-lg flex-shrink-0">
                    {{ $colaborador->iniciais }}
                </div>
            @endif
            <div class="ml-4 min-w-0">
                <h1 class="text-2xl font-bold text-gray-900 truncate">{{ $colaborador->nome }}</h1>
                <p class="text-sm text-gray-600">
                    {{ $colaborador->cargo?->nome ?? 'Sem cargo' }}
                    @if ($colaborador->departamento)
                        &middot; {{ $colaborador->departamento->nome }}
                    @endif
                </p>
                <div class="mt-1 flex flex-wrap items-center gap-2">
                    @if ($colaborador->trashed())
                        <x-badge variant="gray">Desativado</x-badge>
                    @elseif ($colaborador->data_demissao)
                        <x-badge variant="red">Demitido em {{ $colaborador->data_demissao->format('d/m/Y') }}</x-badge>
                    @else
                        <x-badge variant="green" icon="circle-check">Ativo</x-badge>
                    @endif

                    @if ($colaborador->matricula)
                        <x-badge variant="blue">Mat. {{ $colaborador->matricula }}</x-badge>
                    @endif
                    @if ($colaborador->pcd)
                        <x-badge variant="purple" icon="accessible">PCD</x-badge>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex items-center space-x-2 flex-shrink-0">
            <a href="{{ route('colaboradores.index') }}" wire:navigate.hover
               class="text-sm text-gray-600 hover:text-gray-900">
                <i class="ti ti-arrow-left mr-1" aria-hidden="true"></i> Voltar
            </a>
            <a href="{{ route('pdf.colaborador.ficha', $colaborador) }}" target="_blank">
                <x-button variant="secondary" icon="file-type-pdf">Imprimir ficha</x-button>
            </a>
            @if ($podeEditar && !$colaborador->trashed())
                <x-button variant="primary" icon="edit">
                    <a href="{{ route('colaboradores.edit', $colaborador->id) }}" wire:navigate.hover>Editar</a>
                </x-button>
            @endif
        </div>
    </div>

    {{-- Grid de informações --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- COLUNA 1: Dados pessoais --}}
        <x-card title="Dados pessoais" icon="user" class="lg:col-span-2">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                @php
                    $campo = function (string $label, $valor, ?string $hint = null) {
                        return [$label, $valor, $hint];
                    };
                    $campos = [
                        $campo('CPF', $colaborador->cpf_formatado),
                        $campo('Data de nascimento', $colaborador->data_nascimento?->format('d/m/Y'), $colaborador->idade ? "{$colaborador->idade} anos" : null),
                        $campo('Sexo', match($colaborador->sexo) { 'M' => 'Masculino', 'F' => 'Feminino', 'O' => 'Outro', default => null }),
                        $campo('Estado civil', ucfirst(str_replace('_', ' ', $colaborador->estado_civil ?? ''))),
                        $campo('Nacionalidade', $colaborador->nacionalidade),
                        $campo('Naturalidade', $colaborador->naturalidadeCidade ? "{$colaborador->naturalidadeCidade->nome}/{$colaborador->naturalidadeCidade->estado?->uf}" : null),
                        $campo('Nome do pai', $colaborador->nome_pai),
                        $campo('Nome da mãe', $colaborador->nome_mae),
                        $campo('Escolaridade', ucfirst(str_replace('_', ' ', $colaborador->escolaridade ?? ''))),
                        $campo('Tipo sanguíneo', $colaborador->tipo_sanguineo),
                    ];
                @endphp

                @foreach ($campos as [$label, $valor, $hint])
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $label }}</dt>
                        <dd class="mt-0.5 text-gray-900">
                            {{ $valor ?: '—' }}
                            @if ($hint)
                                <span class="text-xs text-gray-500">({{ $hint }})</span>
                            @endif
                        </dd>
                    </div>
                @endforeach
            </dl>

            @if ($colaborador->pcd && $colaborador->pcd_descricao)
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <dt class="text-xs font-medium text-gray-500 uppercase">PCD - Descrição</dt>
                    <dd class="mt-0.5 text-gray-900 text-sm">{{ $colaborador->pcd_descricao }}</dd>
                </div>
            @endif
        </x-card>

        {{-- COLUNA 2: Contato --}}
        <x-card title="Contato" icon="phone">
            <dl class="space-y-3 text-sm">
                @if ($colaborador->telefone_celular)
                    <div class="flex items-center">
                        <i class="ti ti-device-mobile text-gray-400 mr-2" aria-hidden="true"></i>
                        <span>{{ $colaborador->telefone_celular }}</span>
                    </div>
                @endif
                @if ($colaborador->telefone_residencial)
                    <div class="flex items-center">
                        <i class="ti ti-phone text-gray-400 mr-2" aria-hidden="true"></i>
                        <span>{{ $colaborador->telefone_residencial }}</span>
                    </div>
                @endif
                @if ($colaborador->email)
                    <div class="flex items-center min-w-0">
                        <i class="ti ti-mail text-gray-400 mr-2 flex-shrink-0" aria-hidden="true"></i>
                        <a href="mailto:{{ $colaborador->email }}" class="text-indigo-600 hover:underline truncate">
                            {{ $colaborador->email }}
                        </a>
                    </div>
                @endif
                @if ($colaborador->email_pessoal)
                    <div class="flex items-center min-w-0">
                        <i class="ti ti-mail-opened text-gray-400 mr-2 flex-shrink-0" aria-hidden="true"></i>
                        <span class="text-gray-700 truncate">{{ $colaborador->email_pessoal }}</span>
                    </div>
                @endif

                @if (!$colaborador->telefone_celular && !$colaborador->telefone_residencial && !$colaborador->email)
                    <p class="text-gray-400 italic text-xs">Nenhum contato cadastrado.</p>
                @endif
            </dl>
        </x-card>

        {{-- COLUNA 3: Endereço --}}
        @if ($endereco = $colaborador->enderecoResidencial)
            <x-card title="Endereço" icon="map-pin" class="lg:col-span-2">
                <div class="text-sm">
                    <p class="text-gray-900">
                        {{ $endereco->logradouro }}{{ $endereco->numero ? ', ' . $endereco->numero : '' }}
                        @if ($endereco->complemento) - {{ $endereco->complemento }} @endif
                    </p>
                    @if ($endereco->bairro)
                        <p class="text-gray-600">{{ $endereco->bairro }}</p>
                    @endif
                    <p class="text-gray-600">
                        @if ($endereco->cidade)
                            {{ $endereco->cidade->nome }}/{{ $endereco->cidade->estado?->uf }}
                        @endif
                        @if ($endereco->cep) — CEP {{ $endereco->cep }} @endif
                    </p>
                </div>
            </x-card>
        @endif

        {{-- Documentos --}}
        <x-card title="Documentos" icon="id">
            <dl class="space-y-2 text-sm">
                @php
                    $documentos = array_filter([
                        'RG' => $colaborador->rg . ($colaborador->rg_orgao_emissor ? " {$colaborador->rg_orgao_emissor}" : ''),
                        'PIS/PASEP' => $colaborador->pis,
                        'CTPS' => $colaborador->ctps_numero ? "{$colaborador->ctps_numero}/{$colaborador->ctps_serie} {$colaborador->ctps_uf}" : null,
                        'Título eleitor' => $colaborador->titulo_eleitor,
                        'CNH' => $colaborador->cnh ? "{$colaborador->cnh} ({$colaborador->cnh_categoria})" : null,
                        'Reservista' => $colaborador->reservista,
                    ]);
                @endphp
                @forelse ($documentos as $label => $valor)
                    <div>
                        <dt class="text-xs font-medium text-gray-500">{{ $label }}</dt>
                        <dd class="text-gray-900 font-mono text-sm">{{ trim($valor) }}</dd>
                    </div>
                @empty
                    <p class="text-gray-400 italic text-xs">Nenhum documento cadastrado.</p>
                @endforelse
            </dl>
        </x-card>

        {{-- Dados Profissionais --}}
        <x-card title="Dados profissionais" icon="briefcase" class="lg:col-span-2">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase">Cargo</dt>
                    <dd class="text-gray-900">{{ $colaborador->cargo?->nome ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase">Departamento</dt>
                    <dd class="text-gray-900">{{ $colaborador->departamento?->nome ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase">Admissão</dt>
                    <dd class="text-gray-900">
                        {{ $colaborador->data_admissao?->format('d/m/Y') ?? '—' }}
                        @if ($colaborador->tempo_empresa_meses)
                            <span class="text-xs text-gray-500">
                                ({{ floor($colaborador->tempo_empresa_meses / 12) }}a {{ $colaborador->tempo_empresa_meses % 12 }}m)
                            </span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase">Regime</dt>
                    <dd class="text-gray-900">{{ strtoupper($colaborador->regime_contratacao ?? '—') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase">Jornada</dt>
                    <dd class="text-gray-900">{{ ucfirst(str_replace('_', ' ', $colaborador->jornada ?? '—')) }}</dd>
                </div>
                @if ($colaborador->horario_entrada || $colaborador->horario_saida)
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase">Horário</dt>
                        <dd class="text-gray-900">
                            {{ $colaborador->horario_entrada?->format('H:i') ?? '—' }}
                            às
                            {{ $colaborador->horario_saida?->format('H:i') ?? '—' }}
                        </dd>
                    </div>
                @endif
                @if ($podeVerSalario && $colaborador->salario)
                    <div class="sm:col-span-2 bg-amber-50 border border-amber-200 rounded-md p-3 -mx-2">
                        <dt class="text-xs font-medium text-amber-700 uppercase flex items-center">
                            <i class="ti ti-lock mr-1" aria-hidden="true"></i> Salário (confidencial)
                        </dt>
                        <dd class="text-amber-900 font-semibold text-lg">
                            R$ {{ number_format((float) $colaborador->salario, 2, ',', '.') }}
                        </dd>
                    </div>
                @endif
            </dl>
        </x-card>

        {{-- Dados Bancários --}}
        @if ($colaborador->banco_codigo || $colaborador->pix_chave)
            <x-card title="Dados bancários" icon="credit-card">
                <dl class="space-y-2 text-sm">
                    @if ($colaborador->banco_nome)
                        <div>
                            <dt class="text-xs font-medium text-gray-500">Banco</dt>
                            <dd class="text-gray-900">{{ $colaborador->banco_codigo }} - {{ $colaborador->banco_nome }}</dd>
                        </div>
                    @endif
                    @if ($colaborador->banco_agencia)
                        <div>
                            <dt class="text-xs font-medium text-gray-500">Agência / Conta</dt>
                            <dd class="text-gray-900 font-mono">
                                {{ $colaborador->banco_agencia }} / {{ $colaborador->banco_conta }}
                                ({{ ucfirst($colaborador->banco_tipo_conta ?? 'corrente') }})
                            </dd>
                        </div>
                    @endif
                    @if ($colaborador->pix_chave)
                        <div>
                            <dt class="text-xs font-medium text-gray-500">PIX</dt>
                            <dd class="text-gray-900 font-mono text-xs">{{ $colaborador->pix_chave }}</dd>
                        </div>
                    @endif
                </dl>
            </x-card>
        @endif

        {{-- Observações --}}
        @if ($colaborador->observacoes)
            <x-card title="Observações" icon="note" class="lg:col-span-3">
                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $colaborador->observacoes }}</p>
            </x-card>
        @endif

        {{-- Metadata --}}
        <x-card title="Auditoria" icon="history" class="lg:col-span-3" padding="p-4">
            <div class="text-xs text-gray-500 grid grid-cols-1 sm:grid-cols-2 gap-2">
                <p>
                    <i class="ti ti-calendar-plus mr-1" aria-hidden="true"></i>
                    Cadastrado em {{ $colaborador->created_at?->format('d/m/Y H:i') }}
                </p>
                <p>
                    <i class="ti ti-refresh mr-1" aria-hidden="true"></i>
                    Última atualização: {{ $colaborador->updated_at?->diffForHumans() }}
                </p>
                @if ($colaborador->trashed())
                    <p class="text-red-600">
                        <i class="ti ti-trash mr-1" aria-hidden="true"></i>
                        Desativado em {{ $colaborador->deleted_at?->format('d/m/Y H:i') }}
                    </p>
                @endif
            </div>
        </x-card>
    </div>
</div>
