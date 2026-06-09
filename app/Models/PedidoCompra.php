<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\AuditaUsuario;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Pedido de Compra.
 *
 * Máquina de estados (fluxo de duas aprovações):
 *   rascunho → aguardando_liberacao → liberado
 *            → aguardando_aprovacao → aprovado
 *            → enviado_fornecedor → parcialmente_recebido → recebido
 *   (cancelado / rejeitado podem ocorrer antes de recebido)
 */
class PedidoCompra extends Model
{
    use HasFactory;
    use AuditaUsuario;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'pedidos_compra';

    public const STATUS_RASCUNHO = 'rascunho';
    public const STATUS_AGUARDANDO_LIBERACAO = 'aguardando_liberacao';
    public const STATUS_LIBERADO = 'liberado';
    public const STATUS_AGUARDANDO_APROVACAO = 'aguardando_aprovacao';
    public const STATUS_APROVADO = 'aprovado';
    public const STATUS_ENVIADO = 'enviado_fornecedor';
    public const STATUS_PARCIAL = 'parcialmente_recebido';
    public const STATUS_RECEBIDO = 'recebido';
    public const STATUS_CANCELADO = 'cancelado';
    public const STATUS_REJEITADO = 'rejeitado';

    /** @var list<string> */
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'data_pedido' => 'date',
            'data_entrega_prevista' => 'date',
            'data_entrega_realizada' => 'date',
            'valor_total' => 'decimal:2',
            'valor_desconto' => 'decimal:2',
            'valor_frete' => 'decimal:2',
            'valor_final' => 'decimal:2',
            'parcelas' => 'integer',
        ];
    }

    // ============================================================
    // Relacionamentos
    // ============================================================

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Fornecedor::class);
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class, 'solicitante_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(PedidoCompraItem::class);
    }

    public function aprovacoes(): HasMany
    {
        return $this->hasMany(PedidoCompraAprovacao::class);
    }

    // ============================================================
    // Scopes
    // ============================================================

    public function scopeComStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeBuscar(Builder $query, string $termo): Builder
    {
        $termo = trim($termo);
        if ($termo === '') {
            return $query;
        }
        $like = '%' . strtolower($termo) . '%';
        return $query->where(function (Builder $q) use ($like): void {
            $q->whereRaw('LOWER(numero) LIKE ?', [$like])
              ->orWhereHas('fornecedor', fn (Builder $sub) => $sub->buscar($like));
        });
    }

    /**
     * Pedidos pendentes de alguma ação de aprovação.
     */
    public function scopePendentesAprovacao(Builder $query): Builder
    {
        return $query->whereIn('status', [
            self::STATUS_AGUARDANDO_LIBERACAO,
            self::STATUS_AGUARDANDO_APROVACAO,
        ]);
    }

    // ============================================================
    // Helpers de estado
    // ============================================================

    public function podeEditar(): bool
    {
        return in_array($this->status, [self::STATUS_RASCUNHO, self::STATUS_REJEITADO]);
    }

    public function podeCancelar(): bool
    {
        return !in_array($this->status, [
            self::STATUS_RECEBIDO,
            self::STATUS_CANCELADO,
        ]);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_RASCUNHO => 'Rascunho',
            self::STATUS_AGUARDANDO_LIBERACAO => 'Aguardando liberação',
            self::STATUS_LIBERADO => 'Liberado',
            self::STATUS_AGUARDANDO_APROVACAO => 'Aguardando aprovação',
            self::STATUS_APROVADO => 'Aprovado',
            self::STATUS_ENVIADO => 'Enviado ao fornecedor',
            self::STATUS_PARCIAL => 'Parcialmente recebido',
            self::STATUS_RECEBIDO => 'Recebido',
            self::STATUS_CANCELADO => 'Cancelado',
            self::STATUS_REJEITADO => 'Rejeitado',
            default => $this->status,
        };
    }

    public function getStatusCorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_RASCUNHO => 'gray',
            self::STATUS_AGUARDANDO_LIBERACAO, self::STATUS_AGUARDANDO_APROVACAO => 'yellow',
            self::STATUS_LIBERADO, self::STATUS_APROVADO => 'blue',
            self::STATUS_ENVIADO => 'indigo',
            self::STATUS_PARCIAL => 'purple',
            self::STATUS_RECEBIDO => 'green',
            self::STATUS_CANCELADO, self::STATUS_REJEITADO => 'red',
            default => 'gray',
        };
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
            ->useLogName('pedido_compra');
    }
}
