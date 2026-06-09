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

class GrupoMaterial extends Model
{
    use HasFactory;
    use AuditaUsuario;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'grupos_materiais';

    /** @var list<string> */
    protected $guarded = ['id'];

    public function grupoPai(): BelongsTo
    {
        return $this->belongsTo(GrupoMaterial::class, 'grupo_pai_id');
    }

    public function subGrupos(): HasMany
    {
        return $this->hasMany(GrupoMaterial::class, 'grupo_pai_id');
    }

    public function materiais(): HasMany
    {
        return $this->hasMany(Material::class, 'grupo_id');
    }

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
     * Detecta ciclo na hierarquia (reaproveitado do padrão de Departamento).
     */
    public function criariaCiclo(?int $candidatoPaiId): bool
    {
        if ($candidatoPaiId === null) {
            return false;
        }
        if ($candidatoPaiId === $this->id) {
            return true;
        }

        $atualId = $candidatoPaiId;
        $tentativas = 50;
        while ($atualId !== null && $tentativas-- > 0) {
            $atual = self::find($atualId);
            if (!$atual) {
                return false;
            }
            if ($atual->id === $this->id) {
                return true;
            }
            $atualId = $atual->grupo_pai_id;
        }
        return false;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('grupo_material');
    }
}
