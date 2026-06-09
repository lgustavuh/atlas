@extends('pdfs.layout')

@section('conteudo')

<h1 class="doc-titulo">PEDIDO DE COMPRA Nº {{ $pedido->numero ?? '#'.$pedido->id }}</h1>

<table class="dados" style="margin-bottom: 15px;">
    <tr>
        <td class="rotulo">Data de emissão:</td>
        <td class="valor">{{ $pedido->created_at?->format('d/m/Y H:i') }}</td>
        <td class="rotulo">Status:</td>
        <td class="valor"><strong>{{ ucfirst(str_replace('_', ' ', (string) $pedido->status)) }}</strong></td>
    </tr>
</table>

<div class="secao">
    <h2>Fornecedor</h2>
    @if ($pedido->fornecedor)
        <table class="dados">
            <tr>
                <td class="rotulo">Razão social:</td>
                <td class="valor">{{ $pedido->fornecedor->razao_social }}</td>
            </tr>
            @if ($pedido->fornecedor->nome_fantasia)
                <tr>
                    <td class="rotulo">Nome fantasia:</td>
                    <td class="valor">{{ $pedido->fornecedor->nome_fantasia }}</td>
                </tr>
            @endif
            <tr>
                <td class="rotulo">CNPJ:</td>
                <td class="valor mono">{{ \App\Rules\Cnpj::formatar($pedido->fornecedor->cnpj ?? '') }}</td>
            </tr>
            @if ($pedido->fornecedor->email)
                <tr>
                    <td class="rotulo">E-mail:</td>
                    <td class="valor">{{ $pedido->fornecedor->email }}</td>
                </tr>
            @endif
            @if ($pedido->fornecedor->telefone)
                <tr>
                    <td class="rotulo">Telefone:</td>
                    <td class="valor">{{ $pedido->fornecedor->telefone }}</td>
                </tr>
            @endif
        </table>
    @else
        <p class="small">(Fornecedor não informado)</p>
    @endif
</div>

<div class="secao">
    <h2>Solicitante</h2>
    @if ($pedido->solicitante)
        <table class="dados">
            <tr>
                <td class="rotulo">Nome:</td>
                <td class="valor">{{ $pedido->solicitante->nome }}</td>
            </tr>
            @if ($pedido->solicitante->matricula)
                <tr>
                    <td class="rotulo">Matrícula:</td>
                    <td class="valor">{{ $pedido->solicitante->matricula }}</td>
                </tr>
            @endif
        </table>
    @endif
</div>

@if ($pedido->justificativa)
    <div class="secao">
        <h2>Justificativa</h2>
        <div class="bloco-destaque">{{ $pedido->justificativa }}</div>
    </div>
@endif

<div class="secao">
    <h2>Itens</h2>
    <table class="listada">
        <thead>
            <tr>
                <th style="width: 50%;">Material</th>
                <th style="width: 12%;" class="text-right">Qtd.</th>
                <th style="width: 19%;" class="text-right">Preço unit.</th>
                <th style="width: 19%;" class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($pedido->itens as $item)
                <tr>
                    <td>
                        {{ $item->material?->nome ?? '—' }}
                        @if ($item->observacoes)
                            <div class="small">{{ $item->observacoes }}</div>
                        @endif
                    </td>
                    <td class="text-right">{{ number_format((float) $item->quantidade, 4, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format((float) $item->preco_unitario, 4, ',', '.') }}</td>
                    <td class="text-right"><strong>R$ {{ number_format((float) $item->subtotal, 2, ',', '.') }}</strong></td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center small">Sem itens cadastrados.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-right" style="background: #f9fafb;"><strong>TOTAL</strong></td>
                <td class="text-right" style="background: #f9fafb;">
                    <strong>R$ {{ number_format((float) $pedido->valor_total, 2, ',', '.') }}</strong>
                </td>
            </tr>
        </tfoot>
    </table>
</div>

<div class="aviso">
    Este documento serve como autorização formal para fornecimento dos itens listados.
    Favor referenciar o número do pedido em todas as comunicações e na nota fiscal.
</div>

<div class="assinaturas">
    <div class="linha-assinatura">
        <div class="traco">
            Solicitante<br>
            <span class="small">{{ $pedido->solicitante?->nome }}</span>
        </div>
    </div>
    <div class="linha-assinatura">
        <div class="traco">
            Autorizado por<br>
            <span class="small">Departamento de Compras</span>
        </div>
    </div>
</div>

@endsection
