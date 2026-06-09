<?php

declare(strict_types=1);

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

class AuditoriaExport extends BaseExport
{
    public function __construct(
        protected ?\Closure $aplicarFiltros = null,
    ) {
        $this->aplicarFiltros ??= fn (Builder $q): Builder => $q;
    }

    public function title(): string
    {
        return 'Auditoria';
    }

    public function collection(): Collection
    {
        $query = Activity::query()
            ->with('causer:id,name,email')
            ->orderByDesc('id');

        return (($this->aplicarFiltros)($query))->get();
    }

    public function headings(): array
    {
        return [
            'Data/hora',
            'Usuário',
            'E-mail',
            'Módulo',
            'Evento',
            'Recurso',
            'ID recurso',
            'Descrição',
            'IP',
        ];
    }

    /**
     * @param Activity $a
     */
    public function map($a): array
    {
        return [
            $a->created_at?->format('d/m/Y H:i:s') ?? '',
            $a->causer?->name ?? 'Sistema',
            $a->causer?->email ?? '',
            $a->log_name ?? '',
            $a->event ?? '',
            $a->subject_type ? class_basename($a->subject_type) : '',
            $a->subject_id ?? '',
            $a->description ?? '',
            $a->ip_address ?? '',
        ];
    }
}
