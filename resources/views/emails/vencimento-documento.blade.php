<x-mail.layout :titulo="$titulo" :tipoNotificacao="$tipoNotificacao" :linkAcesso="$linkAcesso">

<p style="margin:0 0 15px 0; font-size:15px; font-weight:bold; color:#1f2937;">
    @if ($vencido)
        <span style="color:#dc2626;">⚠ Documento vencido</span>
    @elseif ($diasRestantes === 0)
        <span style="color:#dc2626;">⚠ Vencimento HOJE</span>
    @elseif ($diasRestantes <= 7)
        <span style="color:#d97706;">⚠ Vencimento próximo</span>
    @else
        Vencimento programado
    @endif
</p>

<p style="margin:0 0 20px 0; font-size:14px; line-height:1.5;">
    O documento <strong>{{ $tipoDocumento }}</strong> referente a
    <strong>{{ $referente }}</strong>
    @if ($vencido)
        está <strong style="color:#dc2626;">vencido desde {{ $dataVencimento->format('d/m/Y') }}</strong>.
    @elseif ($diasRestantes === 0)
        <strong style="color:#dc2626;">vence hoje ({{ $dataVencimento->format('d/m/Y') }})</strong>.
    @else
        vence em <strong>{{ $diasRestantes }} dia(s)</strong> ({{ $dataVencimento->format('d/m/Y') }}).
    @endif
</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="background:#f9fafb; border-left:3px solid #4F46E5; padding:12px; margin:20px 0;">
    <tr>
        <td>
            <p style="margin:0; font-size:12px; color:#6b7280;">Tipo do documento</p>
            <p style="margin:2px 0 10px 0; font-size:13px;"><strong>{{ $tipoDocumento }}</strong></p>

            <p style="margin:0; font-size:12px; color:#6b7280;">Referente a</p>
            <p style="margin:2px 0 10px 0; font-size:13px;"><strong>{{ $referente }}</strong></p>

            <p style="margin:0; font-size:12px; color:#6b7280;">Data de vencimento</p>
            <p style="margin:2px 0 0 0; font-size:13px;"><strong>{{ $dataVencimento->format('d/m/Y') }}</strong></p>
        </td>
    </tr>
</table>

<p style="margin:20px 0 0 0; font-size:13px; color:#4b5563;">
    Providencie a renovação o quanto antes para evitar irregularidades.
</p>

</x-mail.layout>
