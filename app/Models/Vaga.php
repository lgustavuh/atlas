<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\AuditaUsuario;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Vaga de emprego.
 *
 * Workflow: rascunho → aberta → em_selecao → preenchida (ou cancelada)
 */
class Vaga extends Model
{
    use HasFactory;
    use AuditaUsuario;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'vagas';

    public const STATUS_RASCUNHO = 'rascunho';
    public const STATUS_ABERTA = 'aberta';
    public const STATUS_EM_SELECAO = 'em_selecao';
    public const STATUS_PREENCHIDA = 'preenchida';
    public const STATUS_CANCELADA = 'cancelada';

    /** @var list<string> */
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'salario_de' => 'decimal:2',
            'salario_ate' => 'decimal:2',
            'salario_publicar' => 'boolean',
            'quantidade_vagas' => 'integer',
            'data_abertura' => 'date',
            'data_fechamento' => 'date',
        ];
    }

    // ============================================================
    // Relacionamentos
    // ============================================================

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class);
    }

    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class);
    }

    public function candidatos(): HasMany
    {
        return $this->hasMany(Candidato::class);
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
              ->orWhereRaw('LOWER(descricao) LIKE ?', [$like])
              ->orWhereRaw('LOWER(requisitos) LIKE ?', [$like]);
        });
    }

    public function scopeComStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeAtivas(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_ABERTA, self::STATUS_EM_SELECAO]);
    }

    // ============================================================
    // Accessors
    // ============================================================

    protected function statusLabel(): Attribute
    {
        return Attribute::get(fn (): string => match ($this->status) {
            self::STATUS_RASCUNHO => 'Rascunho',
            self::STATUS_ABERTA => 'Aberta',
            self::STATUS_EM_SELECAO => 'Em seleção',
            self::STATUS_PREENCHIDA => 'Preenchida',
            self::STATUS_CANCELADA => 'Cancelada',
            default => (string) $this->status,
        });
    }

    protected function statusCor(): Attribute
    {
        return Attribute::get(fn (): string => match ($this->status) {
            self::STATUS_RASCUNHO => 'gray',
            self::STATUS_ABERTA => 'green',
            self::STATUS_EM_SELECAO => 'blue',
            self::STATUS_PREENCHIDA => 'indigo',
            self::STATUS_CANCELADA => 'red',
            default => 'gray',
        });
    }

    /**
     * Faixa salarial formatada (R$ 2.500,00 — R$ 3.500,00) ou "A combinar".
     */
    protected function faixaSalarial(): Attribute
    {
        return Attribute::get(function (): string {
            if (!$this->salario_publicar || (!$this->salario_de && !$this->salario_ate)) {
                return 'A combinar';
            }
            $fmt = fn (float $v): string => 'R$ ' . number_format($v, 2, ',', '.');
            if ($this->salario_de && $this->salario_ate) {
                return $fmt((float) $this->salario_de) . ' — ' . $fmt((float) $this->salario_ate);
            }
            return $this->salario_de ? 'A partir de ' . $fmt((float) $this->salario_de)
                                     : 'Até ' . $fmt((float) $this->salario_ate);
        });
    }

    /**
     * Vaga expirada (data fechamento passou)?
     */
    protected function expirada(): Attribute
    {
        return Attribute::get(function (): bool {
            return $this->data_fechamento !== null
                && $this->data_fechamento->isPast()
                && in_array($this->status, [self::STATUS_ABERTA, self::STATUS_EM_SELECAO]);
        });
    }

    /**
     * @return array<string, string>
     */
    public static function statusesComLabel(): array
    {
        return [
            self::STATUS_RASCUNHO => 'Rascunho',
            self::STATUS_ABERTA => 'Aberta',
            self::STATUS_EM_SELECAO => 'Em seleção',
            self::STATUS_PREENCHIDA => 'Preenchida',
            self::STATUS_CANCELADA => 'Cancelada',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logExcept(['updated_at', 'created_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('vaga');
    }
}
