<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Cidade extends Model
{
    /** @var list<string> */
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'capital' => 'boolean',
        ];
    }

    public function estado(): BelongsTo
    {
        return $this->belongsTo(Estado::class);
    }

    /**
     * Nome completo: "Itaú de Minas/MG"
     */
    public function getNomeCompletoAttribute(): string
    {
        return $this->nome . ($this->estado ? '/' . $this->estado->uf : '');
    }
}
