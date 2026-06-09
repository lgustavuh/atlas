<?php

declare(strict_types=1);

namespace App\Pdf;

use App\Models\Colaborador;

/**
 * Ficha funcional do colaborador — dados pessoais e funcionais para uso interno do RH.
 */
class ColaboradorFichaPdf extends BasePdf
{
    public function __construct(
        protected Colaborador $colaborador,
    ) {
        $this->colaborador->loadMissing([
            'cargo', 'departamento', 'enderecoResidencial', 'dependentes',
        ]);
    }

    protected function view(): string
    {
        return 'pdfs.colaborador-ficha';
    }

    protected function dados(): array
    {
        return [
            'titulo' => 'Ficha Funcional',
            'c' => $this->colaborador,
            'endereco' => $this->colaborador->enderecoResidencial,
            'dependentes' => $this->colaborador->dependentes,
            'orgaoSecundario' => 'Departamento de Recursos Humanos',
        ];
    }

    protected function nome(): string
    {
        $matricula = $this->colaborador->matricula ?? $this->colaborador->id;
        return "ficha-funcional_{$matricula}";
    }
}
