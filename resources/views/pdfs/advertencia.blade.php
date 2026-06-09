@extends('pdfs.layout')

@section('conteudo')

<h1 class="doc-titulo">TERMO DE ADVERTÊNCIA</h1>

<div class="secao">
    <h2>Identificação do colaborador</h2>
    <table class="dados">
        <tr>
            <td class="rotulo">Nome:</td>
            <td class="valor">{{ $colaborador->nome }}</td>
        </tr>
        @if ($colaborador->matricula)
            <tr>
                <td class="rotulo">Matrícula:</td>
                <td class="valor">{{ $colaborador->matricula }}</td>
            </tr>
        @endif
        <tr>
            <td class="rotulo">CPF:</td>
            <td class="valor mono">{{ \App\Rules\Cpf::formatar($colaborador->cpf ?? '') }}</td>
        </tr>
        @if ($colaborador->cargo)
            <tr>
                <td class="rotulo">Cargo:</td>
                <td class="valor">{{ $colaborador->cargo->nome }}</td>
            </tr>
        @endif
        @if ($colaborador->departamento)
            <tr>
                <td class="rotulo">Departamento:</td>
                <td class="valor">{{ $colaborador->departamento->nome }}</td>
            </tr>
        @endif
    </table>
</div>

<div class="secao">
    <h2>Dados da advertência</h2>
    <table class="dados">
        <tr>
            <td class="rotulo">Tipo:</td>
            <td class="valor"><strong>{{ ucfirst($advertencia->tipo) }}</strong></td>
        </tr>
        <tr>
            <td class="rotulo">Data da ocorrência:</td>
            <td class="valor">{{ $advertencia->data_ocorrencia?->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="rotulo">Data da aplicação:</td>
            <td class="valor">{{ $advertencia->data_aplicacao?->format('d/m/Y') }}</td>
        </tr>
        @if ($advertencia->tipo === 'suspensao' && $advertencia->dias_suspensao)
            <tr>
                <td class="rotulo">Dias de suspensão:</td>
                <td class="valor">{{ $advertencia->dias_suspensao }} dia(s)</td>
            </tr>
        @endif
        @if ($aplicadoPor)
            <tr>
                <td class="rotulo">Aplicado por:</td>
                <td class="valor">{{ $aplicadoPor->nome }}</td>
            </tr>
        @endif
    </table>
</div>

<div class="secao">
    <h2>Motivo</h2>
    <div class="bloco-destaque">{{ $advertencia->motivo }}</div>
</div>

<div class="secao">
    <h2>Descrição da ocorrência</h2>
    <div class="bloco-destaque">{{ $advertencia->descricao_ocorrencia }}</div>
</div>

@if ($advertencia->observacoes)
    <div class="secao">
        <h2>Observações</h2>
        <div class="bloco-destaque">{{ $advertencia->observacoes }}</div>
    </div>
@endif

<div class="aviso">
    Por meio deste documento, o(a) colaborador(a) declara ciência da advertência aplicada
    e dos motivos que a fundamentaram. A reincidência poderá acarretar medidas disciplinares
    mais graves, conforme o regulamento interno e a legislação vigente.
</div>

<div class="assinaturas">
    <div class="linha-assinatura">
        <div class="traco">
            Colaborador<br>
            <span class="small">{{ $colaborador->nome }}</span>
        </div>
    </div>
    <div class="linha-assinatura">
        <div class="traco">
            Responsável<br>
            <span class="small">{{ $aplicadoPor?->nome ?? 'Recursos Humanos' }}</span>
        </div>
    </div>
</div>

<p class="text-center small" style="margin-top: 30px;">
    Itaú de Minas - MG, {{ $advertencia->data_aplicacao?->translatedFormat('d \d\e F \d\e Y') }}.
</p>

@endsection
