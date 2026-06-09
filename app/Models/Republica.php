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
 * República: imóvel alugado pela prefeitura para hospedar servidores
 * deslocados (ex.: temporários, deslocados temporariamente para outra cidade).
 */
class Republica extends Model
{
    use HasFactory;
    use AuditaUsuario;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'republicas';

    /** @var list<string> */
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'capacidade_total' => 'integer',
            'aluguel_mensal' => 'decimal:2',
            'ativa' => 'boolean',
        ];
    }

    // ============================================================
    // Relacionamentos
    // ============================================================

    public function cidade(): BelongsTo
    {
        return $this->belongsTo(Cidade::class);
    }

    public function ocupacoes(): HasMany
    {
        return $this->hasMany(RepublicaOcupacao::class);
    }

    /**
     * Ocupações atuais (sem data_saida ou data_saida estritamente no futuro).
     */
    public function ocupacoesAtuais(): HasMany
    {
        return $this->hasMany(RepublicaOcupacao::class)
            ->where(function (Builder $q): void {
                $q->whereNull('data_saida')->orWhereDate('data_saida', '>', now()->toDateString());
            });
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
              ->orWhereRaw('LOWER(endereco) LIKE ?', [$like]);
        });
    }

    public function scopeAtivas(Builder $query): Builder
    {
        return $query->where('ativa', true);
    }

    // ============================================================
    // Accessors
    // ============================================================

    /**
     * Quantidade de ocupantes atuais (calculado via withCount na query).
     * Cai pra contar manualmente se não veio com withCount.
     */
    protected function ocupantesAtuaisCount(): Attribute
    {
        return Attribute::get(function (): int {
            // Se veio com withCount("ocupacoesAtuais") no query, usa
            if (array_key_exists('ocupacoes_atuais_count', $this->attributes)) {
                return (int) $this->attributes['ocupacoes_atuais_count'];
            }
            return $this->ocupacoesAtuais()->count();
        });
    }

    /**
     * Vagas disponíveis (capacidade - ocupantes atuais).
     */
    protected function vagasDisponiveis(): Attribute
    {
        return Attribute::get(function (): int {
            return max(0, ((int) $this->capacidade_total) - $this->ocupantes_atuais_count);
        });
    }

    /**
     * Está cheia?
     */
    protected function lotada(): Attribute
    {
        return Attribute::get(fn (): bool => $this->vagas_disponiveis === 0);
    }

    /**
     * Percentual de ocupação (0-100).
     */
    protected function percentualOcupacao(): Attribute
    {
        return Attribute::get(function (): int {
            if ((int) $this->capacidade_total === 0) {
                return 0;
            }
            return (int) round(($this->ocupantes_atuais_count / $this->capacidade_total) * 100);
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logExcept(['updated_at', 'created_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('republica');
    }
}
