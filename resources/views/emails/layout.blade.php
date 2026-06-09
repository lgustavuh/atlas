<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>{{ $titulo ?? 'Notificação' }}</title>
</head>
<body style="margin:0; padding:0; font-family: Arial, sans-serif; background:#f3f4f6; color:#1f2937;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f3f4f6; padding:30px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0"
                       style="background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.1);">

                    {{-- Cabeçalho --}}
                    <tr>
                        <td style="background:#4F46E5; padding:20px 30px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="color:#ffffff; font-size:14px; font-weight:bold; letter-spacing:0.5px;">
                                        PREFEITURA MUNICIPAL DE ITAÚ DE MINAS
                                    </td>
                                </tr>
                                <tr>
                                    <td style="color:#c7d2fe; font-size:11px; padding-top:2px;">
                                        Sistema Atlas
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Tipo de notificação --}}
                    @if (isset($tipoNotificacao))
                        <tr>
                            <td style="padding:14px 30px; background:#eef2ff; border-bottom:1px solid #e0e7ff;">
                                <span style="font-size:11px; color:#4338ca; font-weight:bold; text-transform:uppercase; letter-spacing:1px;">
                                    {{ $tipoNotificacao }}
                                </span>
                            </td>
                        </tr>
                    @endif

                    {{-- Conteúdo --}}
                    <tr>
                        <td style="padding:30px;">
                            @isset($slot)
                                {{-- Vindo de <x-mail.layout> com slot --}}
                                {{ $slot }}
                            @elseif (! empty($conteudo))
                                {{-- Conteúdo pré-renderizado: marcado como HTMLString antes de chegar aqui --}}
                                {!! $conteudo instanceof \Illuminate\Support\HtmlString ? $conteudo : e($conteudo) !!}
                            @endisset
                        </td>
                    </tr>

                    {{-- Rodapé --}}
                    <tr>
                        <td style="padding:20px 30px; background:#f9fafb; border-top:1px solid #e5e7eb; font-size:11px; color:#6b7280; text-align:center;">
                            Esta é uma notificação automática do sistema Atlas.<br>
                            Por favor, não responda este e-mail.
                            @if (isset($linkAcesso))
                                <br><br>
                                <a href="{{ $linkAcesso }}"
                                   style="display:inline-block; padding:8px 16px; background:#4F46E5; color:#ffffff; text-decoration:none; border-radius:6px; font-size:12px;">
                                    Acessar o sistema
                                </a>
                            @endif
                        </td>
                    </tr>
                </table>

                <p style="font-size:10px; color:#9ca3af; margin-top:15px;">
                    Gerado em {{ now()->format('d/m/Y H:i') }}
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
