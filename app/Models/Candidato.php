<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\AuditaUsuario;

use App\Rules\Cpf as CpfRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Candidato a uma vaga.
 *
 * Workflow:
 *   inscrito → triagem → entrevista → aprovado → contratado
 *                                  \→ rejeitado
 */
class Candidato extends Model
{
    use HasFactory;
    use AuditaUsuario;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'candidatos';

    public const STATUS_INSCRITO = 'inscrito';
    public const STATUS_TRIAGEM = 'triagem';
    public const STATUS_ENTREVISTA = 'entrevista';
    public const STATUS_APROVADO = 'aprovado';
    public const STATUS_REJEITADO = 'rejeitado';
    public const STATUS_CONTRATADO = 'contratado';

    /** @var list<string> */
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'pontuacao' => 'integer',
        ];
    }

    public function vaga(): BelongsTo
    {
        return $this->belongsTo(Vaga::class);
    }

    public function scopeBuscar(Builder $query, string $termo): Builder
    {
        $termo = trim($termo);
        if ($termo === '') {
            return $query;
        }
        $like = '%' . strtolower($termo) . '%';
        $cpfLimpo = CpfRule::limpar($termo);

        return $query->where(function (Builder $q) use ($like, $cpfLimpo): void {
            $q->whereRaw('LOWER(nome) LIKE ?', [$like])
              ->orWhereRaw('LOWER(email) LIKE ?', [$like]);
            if (strlen($cpfLimpo) >= 4) {
                $q->orWhere('cpf', 'like', "%{$cpfLimpo}%");
            }
        });
    }

    public function scopeDaVaga(Builder $query, int $vagaId): Builder
    {
        return $query->where('vaga_id', $vagaId);
    }

    public function scopeComStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    // ============================================================
    // Mutators
    // ============================================================

    protected function cpf(): Attribute
    {
        return Attribute::set(fn (?string $value): ?string => $value
            ? CpfRule::limpar($value)
            : null);
    }

    // ============================================================
    // Accessors
    // ============================================================

    protected function cpfFormatado(): Attribute
    {
        return Attribute::get(fn (): string => CpfRule::formatar($this->cpf ?? ''));
    }

    protected function statusLabel(): Attribute
    {
        return Attribute::get(fn (): string => match ($this->status) {
            self::STATUS_INSCRITO => 'Inscrito',
            self::STATUS_TRIAGEM => 'Triagem',
            self::STATUS_ENTREVISTA => 'Entrevista',
            self::STATUS_APROVADO => 'Aprovado',
            self::STATUS_REJEITADO => 'Rejeitado',
            self::STATUS_CONTRATADO => 'Contratado',
            default => (string) $this->status,
        });
    }

    protected function statusCor(): Attribute
    {
        return Attribute::get(fn (): string => match ($this->status) {
            self::STATUS_INSCRITO => 'gray',
            self::STATUS_TRIAGEM => 'blue',
            self::STATUS_ENTREVISTA => 'yellow',
            self::STATUS_APROVADO => 'green',
            self::STATUS_REJEITADO => 'red',
            self::STATUS_CONTRATADO => 'indigo',
            default => 'gray',
        });
    }

    /**
     * @return array<string, string>
     */
    public static function statusesComLabel(): array
    {
        return [
            self::STATUS_INSCRITO => 'Inscrito',
            self::STATUS_TRIAGEM => 'Triagem',
            self::STATUS_ENTREVISTA => 'Entrevista',
            self::STATUS_APROVADO => 'Aprovado',
            self::STATUS_REJEITADO => 'Rejeitado',
            self::STATUS_CONTRATADO => 'Contratado',
        ];
    }

    /**
     * Transições válidas a partir do status atual.
     *
     * @return list<string>
     */
    public function transicoesPossiveis(): array
    {
        return match ($this->status) {
            self::STATUS_INSCRITO => [self::STATUS_TRIAGEM, self::STATUS_REJEITADO],
            self::STATUS_TRIAGEM => [self::STATUS_ENTREVISTA, self::STATUS_REJEITADO],
            self::STATUS_ENTREVISTA => [self::STATUS_APROVADO, self::STATUS_REJEITADO],
            self::STATUS_APROVADO => [self::STATUS_CONTRATADO, self::STATUS_REJEITADO],
            default => [],
        };
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logExcept(['updated_at', 'created_at', 'curriculo_path'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('candidato');
    }
}
