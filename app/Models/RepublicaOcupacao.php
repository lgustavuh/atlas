<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de ocupação de uma República por um colaborador.
 *
 * Sem soft delete: o histórico fica para sempre. Para "saída" usa data_saida.
 */
class RepublicaOcupacao extends Model
{
    use HasFactory;

    protected $table = 'republica_ocupacoes';

    /** @var list<string> */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'data_entrada' => 'date',
            'data_saida' => 'date',
        ];
    }

    // ============================================================
    // Relacionamentos
    // ============================================================

    public function republica(): BelongsTo
    {
        return $this->belongsTo(Republica::class);
    }

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class);
    }

    // ============================================================
    // Scopes
    // ============================================================

    /**
     * Ocupações ativas (ainda morando lá).
     * Ativa = sem data_saida OU data_saida estritamente no futuro (saí amanhã, ainda estou aqui).
     */
    public function scopeAtuais(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->whereNull('data_saida')->orWhereDate('data_saida', '>', now()->toDateString());
        });
    }

    public function scopeHistoricas(Builder $query): Builder
    {
        return $query->whereNotNull('data_saida')->whereDate('data_saida', '<=', now()->toDateString());
    }

    // ============================================================
    // Accessors
    // ============================================================

    protected function atual(): Attribute
    {
        return Attribute::get(function (): bool {
            return $this->data_saida === null || $this->data_saida->gt(now()->startOfDay());
        });
    }

    /**
     * Quantos dias morou (até hoje se ainda ativa, até saída se histórica).
     */
    protected function diasDeOcupacao(): Attribute
    {
        return Attribute::get(function (): int {
            $fim = $this->data_saida ?? now();
            return (int) $this->data_entrada->diffInDays($fim);
        });
    }
}
