<?php

declare(strict_types=1);

namespace App\Pdf;

use App\Models\Advertencia;

/**
 * Termo de Advertência em PDF — documento oficial assinável.
 */
class AdvertenciaPdf extends BasePdf
{
    public function __construct(
        protected Advertencia $advertencia,
    ) {
        $this->advertencia->loadMissing(['colaborador.cargo', 'colaborador.departamento', 'aplicadoPor']);
    }

    protected function view(): string
    {
        return 'pdfs.advertencia';
    }

    protected function dados(): array
    {
        return [
            'titulo' => 'Termo de Advertência',
            'advertencia' => $this->advertencia,
            'colaborador' => $this->advertencia->colaborador,
            'aplicadoPor' => $this->advertencia->aplicadoPor,
            'orgaoSecundario' => 'Departamento de Recursos Humanos',
        ];
    }

    protected function nome(): string
    {
        $matricula = $this->advertencia->colaborador->matricula ?? $this->advertencia->colaborador->id;
        $data = $this->advertencia->data_aplicacao?->format('Y-m-d') ?? 'sem-data';
        return "advertencia_{$matricula}_{$data}";
    }
}
