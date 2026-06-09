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

class Departamento extends Model
{
    use HasFactory;
    use AuditaUsuario;
    use LogsActivity;
    use SoftDeletes;

    /** @var list<string> */
    protected $guarded = ['id'];

    // ============================================================
    // Relacionamentos
    // ============================================================

    public function departamentoPai(): BelongsTo
    {
        return $this->belongsTo(Departamento::class, 'departamento_pai_id');
    }

    public function subDepartamentos(): HasMany
    {
        return $this->hasMany(Departamento::class, 'departamento_pai_id');
    }

    /**
     * Carrega recursivamente todos os subdepartamentos (árvore).
     */
    public function subDepartamentosRecursivo(): HasMany
    {
        return $this->subDepartamentos()->with('subDepartamentosRecursivo');
    }

    public function colaboradores(): HasMany
    {
        return $this->hasMany(Colaborador::class);
    }

    public function cargos(): HasMany
    {
        return $this->hasMany(Cargo::class);
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
              ->orWhereRaw('LOWER(sigla) LIKE ?', [$like]);
        });
    }

    /**
     * Apenas departamentos raiz (sem pai).
     */
    public function scopeRaiz(Builder $query): Builder
    {
        return $query->whereNull('departamento_pai_id');
    }

    // ============================================================
    // Helpers
    // ============================================================

    /**
     * Retorna o caminho completo: "Diretoria > TI > Suporte"
     */
    public function getCaminhoCompletoAttribute(): string
    {
        $caminho = collect([$this->nome]);
        $atual = $this->departamentoPai;
        // Limita a 10 níveis para prevenir loop infinito
        $tentativas = 10;
        while ($atual && $tentativas-- > 0) {
            $caminho->prepend($atual->nome);
            $atual = $atual->departamentoPai;
        }
        return $caminho->implode(' > ');
    }

    /**
     * Detecta ciclo na hierarquia (departamento não pode ser pai de si mesmo, direta ou indiretamente).
     */
    public function criariaCiclo(?int $candidatoPaiId): bool
    {
        if ($candidatoPaiId === null) {
            return false;
        }
        if ($candidatoPaiId === $this->id) {
            return true;
        }

        // Sobe a árvore do candidato a pai. Se passar por mim, é ciclo.
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
            $atualId = $atual->departamento_pai_id;
        }
        return false;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('departamento');
    }
}
