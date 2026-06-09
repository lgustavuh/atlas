<div class="p-4 sm:p-6 lg:p-8 max-w-5xl mx-auto w-full">

    {{-- Header --}}
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                {{ $editando ? 'Editar Colaborador' : 'Novo Colaborador' }}
            </h1>
            <p class="mt-1 text-sm text-gray-600">
                Preencha os dados em todas as abas e clique em salvar.
            </p>
        </div>
        <a href="{{ route('colaboradores.index') }}" wire:navigate.hover class="text-sm text-gray-600 hover:text-gray-900">
            <i class="ti ti-arrow-left mr-1" aria-hidden="true"></i> Voltar
        </a>
    </div>

    <form wire:submit.prevent="salvar" enctype="multipart/form-data">
        <x-card padding="p-0">
            {{-- Abas --}}
            <div class="border-b border-gray-200">
                <nav class="flex -mb-px overflow-x-auto" aria-label="Abas do formulário">
                    @php
                        $abas = [
                            'pessoal' => ['label' => 'Pessoal', 'icon' => 'user'],
                            'contato' => ['label' => 'Contato e Endereço', 'icon' => 'map-pin'],
                            'profissional' => ['label' => 'Profissional', 'icon' => 'briefcase'],
                            'bancario' => ['label' => 'Bancário', 'icon' => 'credit-card'],
                        ];
                    @endphp
                    @foreach ($abas as $key => $aba)
                        <button
                            type="button"
                            wire:click="trocarAba('{{ $key }}')"
                            class="whitespace-nowrap py-4 px-6 border-b-2 text-sm font-medium flex items-center {{ $abaAtiva === $key ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                        >
                            <i class="ti ti-{{ $aba['icon'] }} mr-2" aria-hidden="true"></i>
                            {{ $aba['label'] }}
                        </button>
                    @endforeach
                </nav>
            </div>

            {{-- Conteúdo das abas --}}
            <div class="p-6">

                {{-- ABA 1: PESSOAL --}}
                <div x-show="$wire.abaAtiva === 'pessoal'">
                    {{-- Foto --}}
                    <div class="mb-6 pb-6 border-b border-gray-200">
                        <h3 class="text-sm font-medium text-gray-900 mb-3">Foto</h3>
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                @if ($foto)
                                    <img src="{{ $foto->temporaryUrl() }}" alt="Preview" class="w-24 h-24 rounded-full object-cover">
                                @elseif ($editando && $colaboradorId && !$removerFoto)
                                    @php $col = App\Models\Colaborador::find($colaboradorId); @endphp
                                    @if ($col && $col->foto_path)
                                        <img src="{{ $col->foto_url }}" alt="Foto atual" class="w-24 h-24 rounded-full object-cover">
                                    @else
                                        <div class="w-24 h-24 rounded-full bg-gray-100 flex items-center justify-center">
                                            <i class="ti ti-user text-4xl text-gray-400" aria-hidden="true"></i>
                                        </div>
                                    @endif
                                @else
                                    <div class="w-24 h-24 rounded-full bg-gray-100 flex items-center justify-center">
                                        <i class="ti ti-user text-4xl text-gray-400" aria-hidden="true"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-1">
                                <input type="file" wire:model="foto" accept="image/jpeg,image/png,image/webp"
                                       class="block w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                <p class="mt-1 text-xs text-gray-500">JPG, PNG ou WebP até 5MB. Será redimensionada para 600x600.</p>
                                @error('foto') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                @if ($editando && $colaboradorId && !$removerFoto)
                                    @php $col = App\Models\Colaborador::find($colaboradorId); @endphp
                                    @if ($col && $col->foto_path)
                                        <button type="button" wire:click="removerFotoAtual"
                                                class="mt-2 text-xs text-red-600 hover:text-red-700">
                                            <i class="ti ti-trash mr-1" aria-hidden="true"></i> Remover foto atual
                                        </button>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>

                    <h3 class="text-sm font-medium text-gray-900 mb-3">Dados pessoais</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <x-input label="Nome completo" name="nome" wire:model="nome" required />
                        </div>
                        <x-input label="Nome social" name="nome_social" wire:model="nome_social"
                                 hint="Como prefere ser chamado(a)" />

                        <x-input label="CPF" name="cpf" wire:model.blur="cpf" required placeholder="000.000.000-00" />

                        <x-input label="Data de nascimento" name="data_nascimento" type="date" wire:model="data_nascimento" />

                        <x-select label="Sexo" name="sexo" wire:model="sexo">
                            <option value="">Selecione</option>
                            <option value="M">Masculino</option>
                            <option value="F">Feminino</option>
                            <option value="O">Outro</option>
                        </x-select>

                        <x-select label="Estado civil" name="estado_civil" wire:model="estado_civil">
                            <option value="">Selecione</option>
                            <option value="solteiro">Solteiro(a)</option>
                            <option value="casado">Casado(a)</option>
                            <option value="divorciado">Divorciado(a)</option>
                            <option value="viuvo">Viúvo(a)</option>
                            <option value="uniao_estavel">União estável</option>
                            <option value="separado">Separado(a)</option>
                        </x-select>

                        <x-input label="Nacionalidade" name="nacionalidade" wire:model="nacionalidade" />

                        <x-input label="Nome do pai" name="nome_pai" wire:model="nome_pai" />
                        <x-input label="Nome da mãe" name="nome_mae" wire:model="nome_mae" />

                        <x-select label="Escolaridade" name="escolaridade" wire:model="escolaridade">
                            <option value="">Selecione</option>
                            <option value="fundamental_incompleto">Fundamental incompleto</option>
                            <option value="fundamental">Fundamental completo</option>
                            <option value="medio_incompleto">Médio incompleto</option>
                            <option value="medio">Médio completo</option>
                            <option value="tecnico">Técnico</option>
                            <option value="superior_incompleto">Superior incompleto</option>
                            <option value="superior">Superior completo</option>
                            <option value="pos">Pós-graduação</option>
                            <option value="mestrado">Mestrado</option>
                            <option value="doutorado">Doutorado</option>
                        </x-select>

                        <x-select label="Raça/Cor" name="raca_cor" wire:model="raca_cor">
                            <option value="">Selecione</option>
                            <option value="branca">Branca</option>
                            <option value="preta">Preta</option>
                            <option value="parda">Parda</option>
                            <option value="amarela">Amarela</option>
                            <option value="indigena">Indígena</option>
                            <option value="nao_declarar">Prefiro não declarar</option>
                        </x-select>

                        <x-select label="Tipo sanguíneo" name="tipo_sanguineo" wire:model="tipo_sanguineo">
                            <option value="">Selecione</option>
                            @foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $t)
                                <option value="{{ $t }}">{{ $t }}</option>
                            @endforeach
                        </x-select>

                        <div class="md:col-span-2 flex items-center space-x-6">
                            <label class="flex items-center text-sm cursor-pointer">
                                <input type="checkbox" wire:model="doador_orgaos" class="h-4 w-4 rounded text-indigo-600 focus:ring-indigo-500">
                                <span class="ml-2">Doador de órgãos</span>
                            </label>
                            <label class="flex items-center text-sm cursor-pointer">
                                <input type="checkbox" wire:model.live="pcd" class="h-4 w-4 rounded text-indigo-600 focus:ring-indigo-500">
                                <span class="ml-2">Pessoa com Deficiência (PCD)</span>
                            </label>
                        </div>

                        @if ($pcd)
                            <div class="md:col-span-2">
                                <x-input label="Descrição da deficiência" name="pcd_descricao" wire:model="pcd_descricao" required />
                            </div>
                        @endif
                    </div>

                    <h3 class="text-sm font-medium text-gray-900 mt-8 mb-3">Naturalidade</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-select label="Estado" wire:model.live="estadoNaturalidadeFiltro">
                            <option value="">Selecione</option>
                            @foreach ($estados as $estado)
                                <option value="{{ $estado->id }}">{{ $estado->nome }} ({{ $estado->uf }})</option>
                            @endforeach
                        </x-select>
                        <x-select label="Cidade" name="naturalidade_cidade_id" wire:model="naturalidade_cidade_id" :disabled="!$estadoNaturalidadeFiltro">
                            <option value="">Selecione</option>
                            @foreach ($cidadesNaturalidade as $cidade)
                                <option value="{{ $cidade->id }}">{{ $cidade->nome }}</option>
                            @endforeach
                        </x-select>
                    </div>

                    <h3 class="text-sm font-medium text-gray-900 mt-8 mb-3">Documentos</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <x-input label="RG" name="rg" wire:model="rg" />
                        <x-input label="Órgão emissor" name="rg_orgao_emissor" wire:model="rg_orgao_emissor" placeholder="SSP/MG" />
                        <x-input label="Data de emissão" name="rg_data_emissao" type="date" wire:model="rg_data_emissao" />

                        <x-input label="PIS/PASEP" name="pis" wire:model="pis" />
                        <x-input label="Título de eleitor" name="titulo_eleitor" wire:model="titulo_eleitor" />
                        <x-input label="Reservista" name="reservista" wire:model="reservista" />

                        <x-input label="CTPS - Número" name="ctps_numero" wire:model="ctps_numero" />
                        <x-input label="CTPS - Série" name="ctps_serie" wire:model="ctps_serie" />
                        <x-input label="CTPS - UF" name="ctps_uf" wire:model="ctps_uf" placeholder="MG" />

                        <x-input label="CNH" name="cnh" wire:model="cnh" />
                        <x-input label="CNH - Categoria" name="cnh_categoria" wire:model="cnh_categoria" placeholder="AB" />
                        <x-input label="CNH - Validade" name="cnh_validade" type="date" wire:model="cnh_validade" />
                    </div>
                </div>

                {{-- ABA 2: CONTATO E ENDEREÇO --}}
                <div x-show="$wire.abaAtiva === 'contato'">
                    <h3 class="text-sm font-medium text-gray-900 mb-3">Contato</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-input label="Telefone residencial" name="telefone_residencial" wire:model="telefone_residencial" placeholder="(00) 0000-0000" />
                        <x-input label="Celular" name="telefone_celular" wire:model="telefone_celular" placeholder="(00) 90000-0000" />
                        <x-input label="Email corporativo" name="email" type="email" wire:model="email" />
                        <x-input label="Email pessoal" name="email_pessoal" type="email" wire:model="email_pessoal" />
                    </div>

                    <h3 class="text-sm font-medium text-gray-900 mt-8 mb-3">Endereço residencial</h3>
                    <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                        <div class="md:col-span-2">
                            <x-input label="CEP" name="endereco_cep" wire:model="endereco_cep" placeholder="00000-000" />
                        </div>
                        <div class="md:col-span-3">
                            <x-input label="Logradouro" name="endereco_logradouro" wire:model="endereco_logradouro" />
                        </div>
                        <div>
                            <x-input label="Número" name="endereco_numero" wire:model="endereco_numero" />
                        </div>
                        <div class="md:col-span-3">
                            <x-input label="Complemento" name="endereco_complemento" wire:model="endereco_complemento" />
                        </div>
                        <div class="md:col-span-3">
                            <x-input label="Bairro" name="endereco_bairro" wire:model="endereco_bairro" />
                        </div>
                        <div class="md:col-span-3">
                            <x-select label="Estado" wire:model.live="estadoEnderecoFiltro">
                                <option value="">Selecione</option>
                                @foreach ($estados as $estado)
                                    <option value="{{ $estado->id }}">{{ $estado->nome }} ({{ $estado->uf }})</option>
                                @endforeach
                            </x-select>
                        </div>
                        <div class="md:col-span-3">
                            <x-select label="Cidade" name="endereco_cidade_id" wire:model="endereco_cidade_id" :disabled="!$estadoEnderecoFiltro">
                                <option value="">Selecione</option>
                                @foreach ($cidadesEndereco as $cidade)
                                    <option value="{{ $cidade->id }}">{{ $cidade->nome }}</option>
                                @endforeach
                            </x-select>
                        </div>
                    </div>
                </div>

                {{-- ABA 3: PROFISSIONAL --}}
                <div x-show="$wire.abaAtiva === 'profissional'">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-input label="Matrícula" name="matricula" wire:model="matricula" hint="Identificador interno (opcional)" />

                        <x-select label="Cargo" name="cargo_id" wire:model="cargo_id">
                            <option value="">Selecione</option>
                            @foreach ($cargos as $cargo)
                                <option value="{{ $cargo->id }}">{{ $cargo->nome }}</option>
                            @endforeach
                        </x-select>

                        <x-select label="Departamento" name="departamento_id" wire:model="departamento_id">
                            <option value="">Selecione</option>
                            @foreach ($departamentos as $dep)
                                <option value="{{ $dep->id }}">{{ $dep->nome }}</option>
                            @endforeach
                        </x-select>

                        <x-select label="Classificação" name="classificacao_id" wire:model="classificacao_id">
                            <option value="">Selecione</option>
                            @foreach ($classificacoes as $c)
                                <option value="{{ $c->id }}">{{ $c->nome }}</option>
                            @endforeach
                        </x-select>

                        <x-input label="Data de admissão" name="data_admissao" type="date" wire:model="data_admissao" />
                        <x-input label="Data de demissão" name="data_demissao" type="date" wire:model="data_demissao"
                                 hint="Deixe em branco se ainda está na empresa" />

                        <x-select label="Regime de contratação" name="regime_contratacao" wire:model="regime_contratacao">
                            <option value="">Selecione</option>
                            <option value="clt">CLT</option>
                            <option value="pj">Pessoa Jurídica</option>
                            <option value="estagio">Estágio</option>
                            <option value="temporario">Temporário</option>
                            <option value="autonomo">Autônomo</option>
                            <option value="terceirizado">Terceirizado</option>
                        </x-select>

                        @can('colaboradores.view-salary')
                            <x-input label="Salário (R$)" name="salario" type="number" step="0.01" min="0" wire:model="salario" />
                        @endcan

                        <x-select label="Jornada" name="jornada" wire:model="jornada">
                            <option value="">Selecione</option>
                            <option value="integral">Integral</option>
                            <option value="meio_periodo">Meio período</option>
                            <option value="turno_revezamento">Turno de revezamento</option>
                            <option value="home_office">Home office</option>
                            <option value="hibrido">Híbrido</option>
                        </x-select>

                        <x-input label="Horário de entrada" name="horario_entrada" type="time" wire:model="horario_entrada" />
                        <x-input label="Horário de saída" name="horario_saida" type="time" wire:model="horario_saida" />
                    </div>

                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                        <textarea wire:model="observacoes" rows="3"
                                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                    </div>
                </div>

                {{-- ABA 4: BANCÁRIO --}}
                <div x-show="$wire.abaAtiva === 'bancario'">
                    <x-alert variant="info" class="mb-4">
                        Os dados bancários são confidenciais e ficam visíveis apenas para usuários autorizados.
                    </x-alert>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-input label="Código do banco" name="banco_codigo" wire:model="banco_codigo" placeholder="001, 237, 341..." />
                        <x-input label="Nome do banco" name="banco_nome" wire:model="banco_nome" />
                        <x-input label="Agência" name="banco_agencia" wire:model="banco_agencia" />
                        <x-input label="Conta" name="banco_conta" wire:model="banco_conta" />

                        <x-select label="Tipo de conta" name="banco_tipo_conta" wire:model="banco_tipo_conta">
                            <option value="">Selecione</option>
                            <option value="corrente">Corrente</option>
                            <option value="poupanca">Poupança</option>
                            <option value="salario">Conta-salário</option>
                        </x-select>

                        <x-input label="Chave PIX" name="pix_chave" wire:model="pix_chave" hint="CPF, email, telefone ou aleatória" />
                    </div>
                </div>
            </div>

            {{-- Resumo de erros (se houver) --}}
            @if ($errors->any())
                <div class="px-6 pb-6">
                    <x-alert variant="error" title="Existem erros no formulário">
                        Verifique todas as abas. Total: {{ $errors->count() }} {{ Str::plural('erro', $errors->count()) }}.
                    </x-alert>
                </div>
            @endif

            {{-- Footer com botões --}}
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                <a href="{{ route('colaboradores.index') }}" wire:navigate.hover
                   class="text-sm text-gray-600 hover:text-gray-900">
                    Cancelar
                </a>

                <div class="flex items-center space-x-2">
                    <x-button type="submit" loading="salvar" icon="device-floppy">
                        {{ $editando ? 'Salvar alterações' : 'Cadastrar colaborador' }}
                    </x-button>
                </div>
            </div>
        </x-card>
    </form>
</div>
