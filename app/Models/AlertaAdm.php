<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\AuditaUsuario;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Alerta administrativo: mensagem fixada pelo admin, exibida para
 * usuários selecionados até serem visualizados (ou data_fim).
 *
 * Prioridade:
 *   baixa | normal | alta | critica
 *
 * Críticos aparecem como banner persistente no topo das páginas.
 */
class AlertaAdm extends Model
{
    use HasFactory;
    use AuditaUsuario;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'alertas_adm';

    public const PRIORIDADE_BAIXA = 'baixa';
    public const PRIORIDADE_NORMAL = 'normal';
    public const PRIORIDADE_ALTA = 'alta';
    public const PRIORIDADE_CRITICA = 'critica';

    /** @var list<string> */
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'data_inicio' => 'date',
            'data_fim' => 'date',
        ];
    }

    // ============================================================
    // Relacionamentos
    // ============================================================

    public function destinatarios(): HasMany
    {
        return $this->hasMany(AlertaAdmDestinatario::class, 'alerta_adm_id');
    }

    /**
     * Colaboradores destinatários (via pivot com visualizado_em).
     */
    public function colaboradores(): BelongsToMany
    {
        return $this->belongsToMany(
            Colaborador::class,
            'alerta_adm_destinatarios',
            'alerta_adm_id',
            'colaborador_id',
        )->withPivot('visualizado_em')->withTimestamps();
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
            $q->whereRaw('LOWER(titulo) LIKE ?', [$like])
              ->orWhereRaw('LOWER(mensagem) LIKE ?', [$like]);
        });
    }

    /**
     * Alertas atualmente no ar (ativo + dentro da janela de datas).
     */
    public function scopeVigentes(Builder $query): Builder
    {
        $hoje = now()->toDateString();
        return $query->where('ativo', true)
            ->where(function (Builder $q) use ($hoje): void {
                $q->whereNull('data_inicio')->orWhereDate('data_inicio', '<=', $hoje);
            })
            ->where(function (Builder $q) use ($hoje): void {
                $q->whereNull('data_fim')->orWhereDate('data_fim', '>=', $hoje);
            });
    }

    /**
     * Alertas vigentes para um colaborador específico (que ainda não visualizou).
     */
    public function scopePendentesPara(Builder $query, int $colaboradorId): Builder
    {
        return $query->vigentes()
            ->whereHas('destinatarios', function (Builder $sub) use ($colaboradorId): void {
                $sub->where('colaborador_id', $colaboradorId)
                    ->whereNull('visualizado_em');
            });
    }

    // ============================================================
    // Accessors
    // ============================================================

    protected function prioridadeLabel(): Attribute
    {
        return Attribute::get(fn (): string => match ($this->prioridade) {
            self::PRIORIDADE_BAIXA => 'Baixa',
            self::PRIORIDADE_NORMAL => 'Normal',
            self::PRIORIDADE_ALTA => 'Alta',
            self::PRIORIDADE_CRITICA => 'Crítica',
            default => (string) $this->prioridade,
        });
    }

    protected function prioridadeCor(): Attribute
    {
        return Attribute::get(fn (): string => match ($this->prioridade) {
            self::PRIORIDADE_BAIXA => 'gray',
            self::PRIORIDADE_NORMAL => 'blue',
            self::PRIORIDADE_ALTA => 'yellow',
            self::PRIORIDADE_CRITICA => 'red',
            default => 'gray',
        });
    }

    /**
     * Está vigente neste momento? (ativo + dentro da janela)
     */
    protected function vigente(): Attribute
    {
        return Attribute::get(function (): bool {
            if (!$this->ativo) {
                return false;
            }
            $hoje = now()->startOfDay();
            if ($this->data_inicio && $this->data_inicio->gt($hoje)) {
                return false;
            }
            if ($this->data_fim && $this->data_fim->lt($hoje)) {
                return false;
            }
            return true;
        });
    }

    /**
     * @return array<string, string>
     */
    public static function prioridadesComLabel(): array
    {
        return [
            self::PRIORIDADE_BAIXA => 'Baixa',
            self::PRIORIDADE_NORMAL => 'Normal',
            self::PRIORIDADE_ALTA => 'Alta',
            self::PRIORIDADE_CRITICA => 'Crítica',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logExcept(['updated_at', 'created_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('alerta_adm');
    }
}
