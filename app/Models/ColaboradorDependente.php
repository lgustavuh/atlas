<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\AuditaUsuario;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ColaboradorDependente extends Model
{
    use HasFactory;
    use AuditaUsuario;
    use SoftDeletes;

    /** @var list<string> */
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'data_nascimento' => 'date',
            'dependente_ir' => 'boolean',
            'dependente_salario_familia' => 'boolean',
            'pcd' => 'boolean',
        ];
    }

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class);
    }
}
