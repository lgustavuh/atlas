@extends('pdfs.layout')

@section('conteudo')

<h1 class="doc-titulo">FICHA FUNCIONAL</h1>

<div class="secao">
    <h2>Dados pessoais</h2>
    <table class="dados">
        <tr>
            <td class="rotulo">Nome:</td>
            <td class="valor"><strong>{{ $c->nome }}</strong></td>
        </tr>
        @if ($c->nome_social)
            <tr><td class="rotulo">Nome social:</td><td class="valor">{{ $c->nome_social }}</td></tr>
        @endif
        <tr>
            <td class="rotulo">CPF:</td>
            <td class="valor mono">{{ \App\Rules\Cpf::formatar($c->cpf ?? '') }}</td>
        </tr>
        @if ($c->rg)
            <tr>
                <td class="rotulo">RG:</td>
                <td class="valor">
                    {{ $c->rg }}
                    @if ($c->rg_orgao_emissor) — {{ $c->rg_orgao_emissor }} @endif
                    @if ($c->rg_data_emissao) ({{ $c->rg_data_emissao->format('d/m/Y') }}) @endif
                </td>
            </tr>
        @endif
        @if ($c->data_nascimento)
            <tr>
                <td class="rotulo">Nascimento:</td>
                <td class="valor">{{ $c->data_nascimento->format('d/m/Y') }} ({{ $c->data_nascimento->age }} anos)</td>
            </tr>
        @endif
        @if ($c->sexo)
            <tr><td class="rotulo">Sexo:</td><td class="valor">{{ ['M' => 'Masculino', 'F' => 'Feminino', 'O' => 'Outro'][$c->sexo] ?? $c->sexo }}</td></tr>
        @endif
        @if ($c->estado_civil)
            <tr><td class="rotulo">Estado civil:</td><td class="valor">{{ ucfirst(str_replace('_', ' ', $c->estado_civil)) }}</td></tr>
        @endif
        @if ($c->nome_pai)
            <tr><td class="rotulo">Filiação - Pai:</td><td class="valor">{{ $c->nome_pai }}</td></tr>
        @endif
        @if ($c->nome_mae)
            <tr><td class="rotulo">Filiação - Mãe:</td><td class="valor">{{ $c->nome_mae }}</td></tr>
        @endif
    </table>
</div>

<div class="secao">
    <h2>Documentos</h2>
    <table class="dados">
        @if ($c->pis)
            <tr><td class="rotulo">PIS/PASEP/NIT:</td><td class="valor mono">{{ $c->pis }}</td></tr>
        @endif
        @if ($c->ctps_numero)
            <tr>
                <td class="rotulo">CTPS:</td>
                <td class="valor">
                    Nº {{ $c->ctps_numero }}
                    @if ($c->ctps_serie) / Série {{ $c->ctps_serie }} @endif
                    @if ($c->ctps_uf) — {{ $c->ctps_uf }} @endif
                </td>
            </tr>
        @endif
        @if ($c->titulo_eleitor)
            <tr>
                <td class="rotulo">Título eleitor:</td>
                <td class="valor">
                    {{ $c->titulo_eleitor }}
                    @if ($c->titulo_zona) (Zona {{ $c->titulo_zona }}, Seção {{ $c->titulo_secao }}) @endif
                </td>
            </tr>
        @endif
        @if ($c->cnh)
            <tr>
                <td class="rotulo">CNH:</td>
                <td class="valor">
                    {{ $c->cnh }}
                    @if ($c->cnh_categoria) — Cat. {{ $c->cnh_categoria }} @endif
                    @if ($c->cnh_validade) (Validade {{ $c->cnh_validade->format('d/m/Y') }}) @endif
                </td>
            </tr>
        @endif
        @if ($c->reservista)
            <tr><td class="rotulo">Reservista:</td><td class="valor">{{ $c->reservista }}</td></tr>
        @endif
    </table>
</div>

<div class="secao">
    <h2>Contato</h2>
    <table class="dados">
        @if ($c->email_pessoal)
            <tr><td class="rotulo">E-mail pessoal:</td><td class="valor">{{ $c->email_pessoal }}</td></tr>
        @endif
        @if ($c->telefone_celular)
            <tr><td class="rotulo">Celular:</td><td class="valor">{{ $c->telefone_celular }}</td></tr>
        @endif
        @if ($c->telefone_residencial)
            <tr><td class="rotulo">Residencial:</td><td class="valor">{{ $c->telefone_residencial }}</td></tr>
        @endif
        @if ($endereco)
            <tr>
                <td class="rotulo">Endereço:</td>
                <td class="valor">
                    {{ $endereco->logradouro }}, {{ $endereco->numero }}
                    @if ($endereco->complemento) - {{ $endereco->complemento }} @endif<br>
                    {{ $endereco->bairro }}
                    @if ($endereco->cep) — CEP {{ $endereco->cep }} @endif
                </td>
            </tr>
        @endif
    </table>
</div>

<div class="secao">
    <h2>Dados funcionais</h2>
    <table class="dados">
        @if ($c->matricula)
            <tr><td class="rotulo">Matrícula:</td><td class="valor mono">{{ $c->matricula }}</td></tr>
        @endif
        @if ($c->cargo)
            <tr><td class="rotulo">Cargo:</td><td class="valor">{{ $c->cargo->nome }}</td></tr>
        @endif
        @if ($c->departamento)
            <tr><td class="rotulo">Departamento:</td><td class="valor">{{ $c->departamento->nome }}</td></tr>
        @endif
        @if ($c->regime_contratacao)
            <tr><td class="rotulo">Regime:</td><td class="valor">{{ strtoupper($c->regime_contratacao) }}</td></tr>
        @endif
        @if ($c->data_admissao)
            <tr><td class="rotulo">Admissão:</td><td class="valor">{{ $c->data_admissao->format('d/m/Y') }}</td></tr>
        @endif
        @if ($c->data_demissao)
            <tr><td class="rotulo">Demissão:</td><td class="valor">{{ $c->data_demissao->format('d/m/Y') }}</td></tr>
        @endif
        @if ($c->salario)
            <tr>
                <td class="rotulo">Salário:</td>
                <td class="valor">R$ {{ number_format((float) $c->salario, 2, ',', '.') }}</td>
            </tr>
        @endif
    </table>
</div>

@if ($dependentes && $dependentes->count() > 0)
    <div class="secao">
        <h2>Dependentes ({{ $dependentes->count() }})</h2>
        <table class="listada">
            <thead>
                <tr>
                    <th style="width: 40%;">Nome</th>
                    <th style="width: 20%;">Parentesco</th>
                    <th style="width: 20%;">Nascimento</th>
                    <th style="width: 20%;">CPF</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($dependentes as $d)
                    <tr>
                        <td>{{ $d->nome }}</td>
                        <td>{{ ucfirst($d->parentesco ?? '—') }}</td>
                        <td>{{ $d->data_nascimento?->format('d/m/Y') ?? '—' }}</td>
                        <td class="mono">{{ \App\Rules\Cpf::formatar($d->cpf ?? '') ?: '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

<p class="small text-right" style="margin-top: 40px;">
    Itaú de Minas - MG, {{ now()->translatedFormat('d \d\e F \d\e Y') }}.
</p>

@endsection
