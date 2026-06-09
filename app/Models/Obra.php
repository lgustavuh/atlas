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
 * Obra: projeto físico onde colaboradores trabalham e materiais são consumidos.
 *
 * Status:
 *   planejamento → em_andamento → concluida
 *                  (ou pausada / cancelada)
 */
class Obra extends Model
{
    use HasFactory;
    use AuditaUsuario;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'obras';

    public const STATUS_PLANEJAMENTO = 'planejamento';
    public const STATUS_EM_ANDAMENTO = 'em_andamento';
    public const STATUS_PAUSADA = 'pausada';
    public const STATUS_CONCLUIDA = 'concluida';
    public const STATUS_CANCELADA = 'cancelada';

    /** @var list<string> */
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'data_inicio' => 'date',
            'data_termino_previsto' => 'date',
            'data_termino_real' => 'date',
            'orcamento' => 'decimal:2',
        ];
    }

    // ============================================================
    // Relacionamentos
    // ============================================================

    public function cidade(): BelongsTo
    {
        return $this->belongsTo(Cidade::class);
    }

    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class, 'responsavel_id');
    }

    // ============================================================
    // Scopes
    // ============================================================

    public function scopeBuscar(Builder $query, string $termo): Builder
    {
        $termo = trim($termo);
        if ($termo === '') {
            return $query;
        }
        $like = '%' . strtolower($termo) . '%';
        return $query->where(function (Builder $q) use ($like): void {
            $q->whereRaw('LOWER(nome) LIKE ?', [$like])
              ->orWhereRaw('LOWER(codigo) LIKE ?', [$like])
              ->orWhereRaw('LOWER(endereco) LIKE ?', [$like]);
        });
    }

    public function scopeComStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeAtivas(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_PLANEJAMENTO, self::STATUS_EM_ANDAMENTO]);
    }

    // ============================================================
    // Accessors
    // ============================================================

    /**
     * Está atrasada? (passou da data prevista e ainda não foi concluída)
     */
    protected function atrasada(): Attribute
    {
        return Attribute::get(function (): bool {
            if (!$this->data_termino_previsto) {
                return false;
            }
            if (in_array($this->status, [self::STATUS_CONCLUIDA, self::STATUS_CANCELADA])) {
                return false;
            }
            return $this->data_termino_previsto->isPast();
        });
    }

    /**
     * Quantos dias até o término previsto (negativo se já passou).
     */
    protected function diasParaTermino(): Attribute
    {
        return Attribute::get(function (): ?int {
            if (!$this->data_termino_previsto) {
                return null;
            }
            return (int) now()->startOfDay()->diffInDays($this->data_termino_previsto, false);
        });
    }

    protected function statusLabel(): Attribute
    {
        return Attribute::get(fn (): string => match ($this->status) {
            self::STATUS_PLANEJAMENTO => 'Planejamento',
            self::STATUS_EM_ANDAMENTO => 'Em andamento',
            self::STATUS_PAUSADA => 'Pausada',
            self::STATUS_CONCLUIDA => 'Concluída',
            self::STATUS_CANCELADA => 'Cancelada',
            default => (string) $this->status,
        });
    }

    protected function statusCor(): Attribute
    {
        return Attribute::get(fn (): string => match ($this->status) {
            self::STATUS_PLANEJAMENTO => 'gray',
            self::STATUS_EM_ANDAMENTO => 'blue',
            self::STATUS_PAUSADA => 'yellow',
            self::STATUS_CONCLUIDA => 'green',
            self::STATUS_CANCELADA => 'red',
            default => 'gray',
        });
    }

    /**
     * @return array<string, string>
     */
    public static function statusesComLabel(): array
    {
        return [
            self::STATUS_PLANEJAMENTO => 'Planejamento',
            self::STATUS_EM_ANDAMENTO => 'Em andamento',
            self::STATUS_PAUSADA => 'Pausada',
            self::STATUS_CONCLUIDA => 'Concluída',
            self::STATUS_CANCELADA => 'Cancelada',
        ];
    }

    // ============================================================
    // Activity Log
    // ============================================================

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logExcept(['updated_at', 'created_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('obra');
    }
}
