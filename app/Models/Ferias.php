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
 * Férias do colaborador (CLT).
 *
 * Conceitos:
 *   - Período aquisitivo: 12 meses de trabalho que dão direito a 30 dias de férias
 *   - Período de gozo: quando o colaborador efetivamente sai de férias
 *   - Abono pecuniário: "vender" até 10 dias (1/3 das férias) — opcional
 *   - Adiantamento do 13º: pode receber junto das férias (opcional)
 *
 * Workflow: programada → aprovada → em_gozo → concluida
 *           (ou cancelada em qualquer ponto antes de concluida)
 *
 * Regras CLT consideradas:
 *   - Mínimo 5 dias por período de gozo (não fracionar abaixo)
 *   - Máximo 30 dias por período aquisitivo (descontando abono)
 *   - Abono: até 1/3 do período (máx 10 dias para período de 30)
 */
class Ferias extends Model
{
    use HasFactory;
    use AuditaUsuario;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'ferias';

    public const STATUS_PROGRAMADA = 'programada';
    public const STATUS_APROVADA = 'aprovada';
    public const STATUS_EM_GOZO = 'em_gozo';
    public const STATUS_CONCLUIDA = 'concluida';
    public const STATUS_CANCELADA = 'cancelada';

    /** @var list<string> */
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'periodo_aquisitivo_inicio' => 'date',
            'periodo_aquisitivo_fim' => 'date',
            'data_inicio_gozo' => 'date',
            'data_fim_gozo' => 'date',
            'data_aprovacao' => 'datetime',
            'abono_pecuniario' => 'boolean',
            'adiantar_13_salario' => 'boolean',
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

    public function scopeProgramadas(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PROGRAMADA);
    }

    public function scopeAguardandoAprovacao(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PROGRAMADA);
    }

    public function scopeEmGozo(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_EM_GOZO);
    }

    /**
     * Férias previstas para este mês ou próximo.
     */
    public function scopeProximas(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_APROVADA, self::STATUS_EM_GOZO])
            ->whereDate('data_inicio_gozo', '>=', now()->subDays(7))
            ->whereDate('data_inicio_gozo', '<=', now()->addDays(60));
    }

    // ============================================================
    // Accessors
    // ============================================================

    /**
     * Total de dias usados (gozo + abono).
     */
    protected function totalDiasUsados(): Attribute
    {
        return Attribute::get(fn (): int => ($this->dias_gozo ?? 0) + ($this->dias_abono ?? 0));
    }

    /**
     * Quantos dias restam do período aquisitivo (de 30 dias por padrão CLT).
     */
    protected function diasRestantes(): Attribute
    {
        return Attribute::get(fn (): int => max(0, 30 - $this->total_dias_usados));
    }

    /**
     * Está em gozo agora?
     */
    protected function emGozoHoje(): Attribute
    {
        return Attribute::get(function (): bool {
            if (!$this->data_inicio_gozo || !$this->data_fim_gozo) {
                return false;
            }
            $hoje = now()->startOfDay();
            return $hoje->between($this->data_inicio_gozo, $this->data_fim_gozo);
        });
    }

    /**
     * Label amigável do status.
     */
    protected function statusLabel(): Attribute
    {
        return Attribute::get(fn (): string => match ($this->status) {
            self::STATUS_PROGRAMADA => 'Programada',
            self::STATUS_APROVADA => 'Aprovada',
            self::STATUS_EM_GOZO => 'Em gozo',
            self::STATUS_CONCLUIDA => 'Concluída',
            self::STATUS_CANCELADA => 'Cancelada',
            default => $this->status,
        });
    }

    // ============================================================
    // Activity Log
    // ============================================================

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logExcept(['updated_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('ferias');
    }
}
