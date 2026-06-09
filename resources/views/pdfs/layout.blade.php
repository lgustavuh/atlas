<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>{{ $titulo ?? 'Documento' }}</title>
    <style>
        @page {
            margin: 80px 50px 60px 50px;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #1f2937;
            margin: 0;
        }

        /* Cabeçalho institucional fixo */
        .pdf-header {
            position: fixed;
            top: -65px;
            left: 0;
            right: 0;
            height: 70px;
            border-bottom: 2px solid #4F46E5;
            padding-bottom: 8px;
        }
        .pdf-header .brasao {
            float: left;
            width: 50px;
            height: 50px;
            background: #4F46E5;
            color: white;
            text-align: center;
            line-height: 50px;
            font-weight: bold;
            font-size: 16pt;
            border-radius: 50%;
        }
        .pdf-header .org {
            margin-left: 65px;
        }
        .pdf-header .org-nome {
            font-size: 13pt;
            font-weight: bold;
            color: #4F46E5;
            margin: 0;
        }
        .pdf-header .org-sub {
            font-size: 9pt;
            color: #6b7280;
            margin: 0;
        }

        /* Rodapé fixo */
        .pdf-footer {
            position: fixed;
            bottom: -45px;
            left: 0;
            right: 0;
            height: 35px;
            border-top: 1px solid #e5e7eb;
            padding-top: 5px;
            font-size: 8pt;
            color: #6b7280;
            text-align: center;
        }
        .pdf-footer .pagina:after {
            content: counter(page);
        }

        /* Título do documento */
        h1.doc-titulo {
            font-size: 16pt;
            color: #1f2937;
            text-align: center;
            margin: 0 0 20px 0;
            padding-bottom: 8px;
            border-bottom: 1px solid #e5e7eb;
        }

        /* Seções */
        .secao {
            margin-bottom: 18px;
        }
        .secao h2 {
            font-size: 11pt;
            color: #4F46E5;
            margin: 0 0 6px 0;
            padding-bottom: 3px;
            border-bottom: 1px dashed #e5e7eb;
        }

        /* Tabelas de dados (rótulo: valor) */
        table.dados {
            width: 100%;
            border-collapse: collapse;
        }
        table.dados td {
            padding: 4px 8px;
            vertical-align: top;
        }
        table.dados td.rotulo {
            font-weight: bold;
            color: #6b7280;
            width: 30%;
            font-size: 10pt;
        }
        table.dados td.valor {
            color: #1f2937;
        }

        /* Tabela listada */
        table.listada {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table.listada th {
            background: #4F46E5;
            color: white;
            padding: 6px 8px;
            text-align: left;
            font-size: 10pt;
        }
        table.listada td {
            padding: 6px 8px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 10pt;
        }

        /* Bloco destacado (motivo, descrição) */
        .bloco-destaque {
            background: #f9fafb;
            border-left: 3px solid #4F46E5;
            padding: 10px 14px;
            margin: 10px 0;
            font-size: 10pt;
            white-space: pre-wrap;
        }

        /* Assinaturas */
        .assinaturas {
            margin-top: 60px;
            width: 100%;
        }
        .assinaturas .linha-assinatura {
            display: inline-block;
            width: 45%;
            text-align: center;
            margin: 0 2%;
        }
        .assinaturas .linha-assinatura .traco {
            border-top: 1px solid #1f2937;
            padding-top: 5px;
            font-size: 9pt;
        }

        /* Aviso */
        .aviso {
            background: #fef3c7;
            border: 1px solid #fbbf24;
            padding: 8px 12px;
            font-size: 9pt;
            margin: 15px 0;
            color: #78350f;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .small { font-size: 9pt; color: #6b7280; }
        .mono { font-family: 'DejaVu Sans Mono', monospace; font-size: 9pt; }
    </style>
</head>
<body>

<div class="pdf-header">
    <div class="brasao">PMI</div>
    <div class="org">
        <p class="org-nome">PREFEITURA MUNICIPAL DE ITAÚ DE MINAS</p>
        <p class="org-sub">{{ $orgaoSecundario ?? 'Departamento de Recursos Humanos' }}</p>
        <p class="org-sub">CNPJ: 16.713.103/0001-44 — Rua Sete de Setembro, 245, Centro, Itaú de Minas - MG</p>
    </div>
</div>

<div class="pdf-footer">
    <span>Documento gerado em {{ now()->format('d/m/Y H:i') }} — Página <span class="pagina"></span></span>
</div>

<main>
    @yield('conteudo')
</main>

</body>
</html>
