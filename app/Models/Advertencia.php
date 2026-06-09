<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\AuditaUsuario;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Advertencia extends Model
{
    use HasFactory;
    use AuditaUsuario;
    use LogsActivity;
    use SoftDeletes;

    /** @var list<string> */
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'data_ocorrencia' => 'date',
            'data_aplicacao' => 'date',
            'data_ciencia' => 'datetime',
            'ciente_colaborador' => 'boolean',
        ];
    }

    // ============================================================
    // Relacionamentos
    // ============================================================

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class);
    }

    public function aplicadoPor(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class, 'aplicado_por_id');
    }

    // ============================================================
    // Scopes
    // ============================================================

    public function scopeDoColaborador(Builder $query, int $colaboradorId): Builder
    {
        return $query->where('colaborador_id', $colaboradorId);
    }

    public function scopeDoTipo(Builder $query, string $tipo): Builder
    {
        return $query->where('tipo', $tipo);
    }

    /**
     * Advertências aplicadas em um período.
     */
    public function scopeNoPeriodo(Builder $query, string $de, string $ate): Builder
    {
        return $query->whereBetween('data_aplicacao', [$de, $ate]);
    }

    // ============================================================
    // Activity Log
    // ============================================================

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logExcept(['updated_at', 'documento_path'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('advertencia');
    }
}
