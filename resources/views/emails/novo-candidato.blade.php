<x-mail.layout :titulo="$titulo" :tipoNotificacao="$tipoNotificacao" :linkAcesso="$linkAcesso">

<p style="margin:0 0 15px 0; font-size:15px; font-weight:bold; color:#1f2937;">
    {{ $candidato->nome }} se candidatou a uma vaga.
</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:6px; margin:20px 0;">
    @if ($candidato->vaga)
        <tr>
            <td style="padding:14px 16px; border-bottom:1px solid #e5e7eb;">
                <span style="font-size:11px; color:#6b7280;">Vaga</span><br>
                <strong style="font-size:14px;">{{ $candidato->vaga->titulo }}</strong>
            </td>
        </tr>
    @endif
    <tr>
        <td style="padding:14px 16px; border-bottom:1px solid #e5e7eb;">
            <span style="font-size:11px; color:#6b7280;">Nome</span><br>
            <strong style="font-size:13px;">{{ $candidato->nome }}</strong>
        </td>
    </tr>
    <tr>
        <td style="padding:14px 16px; border-bottom:1px solid #e5e7eb;">
            <span style="font-size:11px; color:#6b7280;">E-mail</span><br>
            <strong style="font-size:13px;">{{ $candidato->email }}</strong>
        </td>
    </tr>
    @if ($candidato->telefone)
        <tr>
            <td style="padding:14px 16px; border-bottom:1px solid #e5e7eb;">
                <span style="font-size:11px; color:#6b7280;">Telefone</span><br>
                <strong style="font-size:13px;">{{ $candidato->telefone }}</strong>
            </td>
        </tr>
    @endif
    @if ($candidato->experiencia)
        <tr>
            <td style="padding:14px 16px;">
                <span style="font-size:11px; color:#6b7280;">Experiência</span><br>
                <span style="font-size:12px; color:#1f2937;">{{ \Illuminate\Support\Str::limit($candidato->experiencia, 250) }}</span>
            </td>
        </tr>
    @endif
</table>

<p style="margin:20px 0 0 0; font-size:12px; color:#6b7280;">
    Acesse o sistema para fazer a triagem e iniciar o processo seletivo.
</p>

</x-mail.layout>
