<?php

declare(strict_types=1);

namespace App\Pdf;

use Barryvdh\DomPDF\Facade\Pdf as DomPDF;
use Barryvdh\DomPDF\PDF;
use Illuminate\Http\Response;

/**
 * Classe-base para geração de PDFs.
 *
 * Padroniza:
 *   - Tamanho A4 retrato
 *   - Margens consistentes
 *   - Cabeçalho institucional via layouts.pdf-base
 *   - Nome de arquivo padronizado
 *
 * Classes filhas implementam:
 *   - view(): string    — view blade a renderizar
 *   - dados(): array    — variáveis passadas para a view
 *   - nome(): string    — nome do arquivo (sem extensão)
 */
abstract class BasePdf
{
    /**
     * Identifica a view Blade a ser usada.
     */
    abstract protected function view(): string;

    /**
     * @return array<string, mixed>
     */
    abstract protected function dados(): array;

    /**
     * Nome do arquivo sem extensão (vai virar nome.pdf).
     */
    abstract protected function nome(): string;

    /**
     * Paper size — A4 por padrão. Override para Carta etc.
     */
    protected string $paperSize = 'a4';

    /**
     * Orientação — portrait/landscape.
     */
    protected string $orientation = 'portrait';

    /**
     * Constrói a instância do PDF aplicando layout e dados.
     */
    public function gerar(): PDF
    {
        $pdf = DomPDF::loadView($this->view(), $this->dados());
        $pdf->setPaper($this->paperSize, $this->orientation);

        // Configurações ajudam com fontes UTF-8 e renderização
        $pdf->setOptions([
            'defaultFont' => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => false,
            'chroot' => realpath(public_path()),
        ]);

        return $pdf;
    }

    /**
     * Baixa o PDF no browser (Content-Disposition: attachment).
     */
    public function download(): Response
    {
        return $this->gerar()->download($this->nome() . '.pdf');
    }

    /**
     * Renderiza inline no browser (Content-Disposition: inline).
     */
    public function stream(): Response
    {
        return $this->gerar()->stream($this->nome() . '.pdf');
    }

    /**
     * Retorna o conteúdo bruto do PDF (string).
     */
    public function output(): string
    {
        return $this->gerar()->output();
    }
}
