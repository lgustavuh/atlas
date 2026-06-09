<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\AuditaUsuario;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de destinatário de um alerta administrativo.
 *
 * Não usamos pivot puro porque temos visualizado_em e queremos eventos
 * ao marcar visualização.
 */
class AlertaAdmDestinatario extends Model
{
    protected $table = 'alerta_adm_destinatarios';

    /** @var list<string> */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'visualizado_em' => 'datetime',
        ];
    }

    public function alerta(): BelongsTo
    {
        return $this->belongsTo(AlertaAdm::class, 'alerta_adm_id');
    }

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class);
    }
}
