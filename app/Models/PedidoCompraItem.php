<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\AuditaUsuario;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoCompraItem extends Model
{
    protected $table = 'pedido_compra_itens';

    /** @var list<string> */
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'quantidade' => 'decimal:4',
            'preco_unitario' => 'decimal:4',
            'subtotal' => 'decimal:2',
            'quantidade_recebida' => 'decimal:4',
        ];
    }

    public function pedidoCompra(): BelongsTo
    {
        return $this->belongsTo(PedidoCompra::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    /**
     * Item foi totalmente recebido?
     */
    protected function recebidoCompleto(): Attribute
    {
        return Attribute::get(fn (): bool => (float) $this->quantidade_recebida >= (float) $this->quantidade);
    }
}
