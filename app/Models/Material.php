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
 * Material / Insumo do almoxarifado.
 */
class Material extends Model
{
    use HasFactory;
    use AuditaUsuario;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'materiais';

    /** @var list<string> */
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'estoque_atual' => 'decimal:4',
            'estoque_minimo' => 'decimal:4',
            'estoque_maximo' => 'decimal:4',
            'preco_referencia' => 'decimal:4',
        ];
    }

    // ============================================================
    // Relacionamentos
    // ============================================================

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(GrupoMaterial::class, 'grupo_id');
    }

    public function itensPedido(): HasMany
    {
        return $this->hasMany(PedidoCompraItem::class);
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
              ->orWhereRaw('LOWER(codigo) LIKE ?', [$like]);
        });
    }

    /**
     * Materiais com estoque abaixo do mínimo.
     */
    public function scopeAbaixoDoMinimo(Builder $query): Builder
    {
        return $query->whereColumn('estoque_atual', '<', 'estoque_minimo');
    }

    // ============================================================
    // Accessors
    // ============================================================

    /**
     * Está com estoque baixo?
     */
    protected function estoqueBaixo(): Attribute
    {
        return Attribute::get(function (): bool {
            if ($this->estoque_minimo === null) {
                return false;
            }
            return (float) $this->estoque_atual < (float) $this->estoque_minimo;
        });
    }

    /**
     * Estoque atual formatado com a unidade.
     */
    protected function estoqueFormatado(): Attribute
    {
        return Attribute::get(function (): string {
            // Remove zeros decimais desnecessários (10.0000 -> 10, 2.5000 -> 2,5)
            $valor = (float) $this->estoque_atual;
            $formatado = rtrim(rtrim(number_format($valor, 4, ',', '.'), '0'), ',');
            return "{$formatado} {$this->unidade_medida}";
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
            ->useLogName('material');
    }
}
