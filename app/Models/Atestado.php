<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\AuditaUsuario;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Atestado médico do colaborador.
 *
 * Workflow:
 *   pendente → aprovado | rejeitado
 *
 * O arquivo (PDF/imagem) é obrigatório e armazenado em storage privado.
 */
class Atestado extends Model
{
    use HasFactory;
    use AuditaUsuario;
    use LogsActivity;
    use SoftDeletes;

    public const STATUS_PENDENTE = 'pendente';
    public const STATUS_APROVADO = 'aprovado';
    public const STATUS_REJEITADO = 'rejeitado';

    /** @var list<string> */
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'data_inicio' => 'date',
            'data_fim' => 'date',
            'data_aprovacao' => 'datetime',
        ];
    }

    // ============================================================
    // Relacionamentos
    // ============================================================

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class);
    }

    public function aprovadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprovado_por_id');
    }

    // ============================================================
    // Scopes
    // ============================================================

    public function scopeDoColaborador(Builder $query, int $colaboradorId): Builder
    {
        return $query->where('colaborador_id', $colaboradorId);
    }

    public function scopePendentes(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDENTE);
    }

    public function scopeAprovados(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APROVADO);
    }

    public function scopeRejeitados(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_REJEITADO);
    }

    // ============================================================
    // Accessors
    // ============================================================

    /**
     * Tamanho do arquivo formatado (KB / MB).
     */
    protected function tamanhoFormatado(): Attribute
    {
        return Attribute::get(function (): string {
            $bytes = $this->arquivo_tamanho_bytes ?? 0;
            if ($bytes < 1024) {
                return "{$bytes} B";
            }
            if ($bytes < 1048576) {
                return number_format($bytes / 1024, 1, ',', '.') . ' KB';
            }
            return number_format($bytes / 1048576, 2, ',', '.') . ' MB';
        });
    }

    // ============================================================
    // Activity Log
    // ============================================================

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logExcept(['updated_at', 'arquivo_path'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('atestado');
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return match ($eventName) {
            'created' => "Atestado registrado para {$this->colaborador?->nome}",
            'updated' => "Atestado atualizado",
            'deleted' => "Atestado excluído",
            default => "Atestado - evento {$eventName}",
        };
    }
}
