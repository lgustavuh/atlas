<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Classe base para todos os exports do sistema.
 *
 * Cuida do estilo padronizado: cabeçalho azul escuro, bordas, auto-size.
 * As classes filhas só precisam implementar:
 *   - headings(): list<string> — colunas
 *   - map($item): list<mixed> — uma linha por item
 *   - collection() — dados (já chega filtrado conforme query do controller)
 */
abstract class BaseExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, WithEvents, ShouldAutoSize
{
    use Exportable;

    /**
     * Cor do cabeçalho (azul-índigo combinando com o sistema).
     */
    protected string $headerFillColor = 'FF4F46E5';

    /**
     * Cor do texto do cabeçalho.
     */
    protected string $headerTextColor = 'FFFFFFFF';

    /**
     * Título da aba (override nas filhas).
     */
    public function title(): string
    {
        return 'Dados';
    }

    /**
     * Estilo do cabeçalho (linha 1).
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => $this->headerTextColor],
                    'size' => 11,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => $this->headerFillColor],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    /**
     * Eventos: aplica bordas em tudo após carregar e congela primeira linha.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $highest = $sheet->getHighestRowAndColumn();
                $range = 'A1:' . $highest['column'] . $highest['row'];

                // Bordas finas em todas as células
                $sheet->getStyle($range)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FFD1D5DB'],
                        ],
                    ],
                ]);

                // Congela primeira linha (cabeçalho fixo ao rolar)
                $sheet->freezePane('A2');

                // Altura do cabeçalho
                $sheet->getRowDimension(1)->setRowHeight(28);
            },
        ];
    }

    /**
     * Auxiliar: formata data para 'd/m/Y' ou string vazia.
     */
    protected function formatarData($data): string
    {
        if ($data === null) {
            return '';
        }
        if ($data instanceof \Carbon\Carbon || $data instanceof \DateTimeInterface) {
            return $data->format('d/m/Y');
        }
        return (string) $data;
    }

    /**
     * Auxiliar: formata moeda brasileira sem o "R$ " (para deixar a célula como número).
     */
    protected function formatarMoeda($valor): float
    {
        if ($valor === null) {
            return 0.0;
        }
        return round((float) $valor, 2);
    }
}
