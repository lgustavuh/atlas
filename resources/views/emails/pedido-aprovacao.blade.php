<x-mail.layout :titulo="$titulo" :tipoNotificacao="$tipoNotificacao" :linkAcesso="$linkAcesso">

<p style="margin:0 0 15px 0; font-size:15px; font-weight:bold; color:#1f2937;">
    Um pedido de compra está aguardando sua {{ strtolower($etapaLabel) }}.
</p>

<p style="margin:0 0 20px 0; font-size:13px; line-height:1.5; color:#4b5563;">
    Você foi designado para esta etapa do fluxo de aprovação. Acesse o sistema
    para analisar os detalhes e decidir.
</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:6px; padding:0; margin:0 0 20px 0;">
    <tr>
        <td style="padding:14px 16px; border-bottom:1px solid #e5e7eb;">
            <span style="font-size:11px; color:#6b7280;">Número</span><br>
            <strong style="font-size:14px;">{{ $pedido->numero ?? '#'.$pedido->id }}</strong>
        </td>
    </tr>
    @if ($pedido->fornecedor)
        <tr>
            <td style="padding:14px 16px; border-bottom:1px solid #e5e7eb;">
                <span style="font-size:11px; color:#6b7280;">Fornecedor</span><br>
                <strong style="font-size:13px;">{{ $pedido->fornecedor->nome_fantasia ?: $pedido->fornecedor->razao_social }}</strong>
            </td>
        </tr>
    @endif
    @if ($pedido->solicitante)
        <tr>
            <td style="padding:14px 16px; border-bottom:1px solid #e5e7eb;">
                <span style="font-size:11px; color:#6b7280;">Solicitante</span><br>
                <strong style="font-size:13px;">{{ $pedido->solicitante->nome }}</strong>
            </td>
        </tr>
    @endif
    <tr>
        <td style="padding:14px 16px; border-bottom:1px solid #e5e7eb;">
            <span style="font-size:11px; color:#6b7280;">Valor total</span><br>
            <strong style="font-size:15px; color:#4F46E5;">R$ {{ number_format((float) $pedido->valor_total, 2, ',', '.') }}</strong>
        </td>
    </tr>
    @if ($pedido->justificativa)
        <tr>
            <td style="padding:14px 16px;">
                <span style="font-size:11px; color:#6b7280;">Justificativa</span><br>
                <span style="font-size:12px; color:#1f2937;">{{ \Illuminate\Support\Str::limit($pedido->justificativa, 200) }}</span>
            </td>
        </tr>
    @endif
</table>

<p style="margin:20px 0 0 0; font-size:12px; color:#6b7280;">
    Pedido criado em {{ $pedido->created_at?->format('d/m/Y H:i') }}.
</p>

</x-mail.layout>
