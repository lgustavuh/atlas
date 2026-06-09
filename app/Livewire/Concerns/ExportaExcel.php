<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Exports\BaseExport;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Trait para Livewire components que oferecem botão de "Exportar Excel".
 *
 * O componente filho usa assim:
 *
 *   public function exportar()
 *   {
 *       return $this->fazerDownload(
 *           new ColaboradoresExport($this->callbackFiltros()),
 *           'colaboradores',
 *       );
 *   }
 *
 * Cuida do nome do arquivo padronizado (slug-data-hora.xlsx).
 */
trait ExportaExcel
{
    protected function fazerDownload(BaseExport $export, string $slugBase): BinaryFileResponse
    {
        $timestamp = now()->format('Y-m-d_His');
        $nome = "{$slugBase}_{$timestamp}.xlsx";

        return Excel::download($export, $nome);
    }
}
