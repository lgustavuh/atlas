<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\AuditaUsuario;

use App\Rules\Placa as PlacaRule;
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
 * Veículo da frota.
 *
 * Status:
 *   disponivel | em_uso | em_manutencao | inativo | vendido
 */
class Veiculo extends Model
{
    use HasFactory;
    use AuditaUsuario;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'veiculos';

    public const STATUS_DISPONIVEL = 'disponivel';
    public const STATUS_EM_USO = 'em_uso';
    public const STATUS_EM_MANUTENCAO = 'em_manutencao';
    public const STATUS_INATIVO = 'inativo';
    public const STATUS_VENDIDO = 'vendido';

    /** @var list<string> */
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'ano_fabricacao' => 'integer',
            'ano_modelo' => 'integer',
            'km_atual' => 'integer',
            'valor_aquisicao' => 'decimal:2',
            'data_aquisicao' => 'date',
            'licenciamento_vencimento' => 'date',
            'seguro_vencimento' => 'date',
        ];
    }

    // ============================================================
    // Relacionamentos
    // ============================================================

    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class, 'responsavel_id');
    }

    public function manutencoes(): HasMany
    {
        return $this->hasMany(VeiculoManutencao::class)->orderByDesc('data_manutencao');
    }

    public function ultimaManutencao(): HasMany
    {
        return $this->hasMany(VeiculoManutencao::class)->latest('data_manutencao')->limit(1);
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
        $placaLimpa = PlacaRule::limpar($termo);

        return $query->where(function (Builder $q) use ($like, $placaLimpa): void {
            $q->whereRaw('LOWER(marca) LIKE ?', [$like])
              ->orWhereRaw('LOWER(modelo) LIKE ?', [$like]);

            if (strlen($placaLimpa) >= 3) {
                $q->orWhereRaw("REPLACE(placa, '-', '') LIKE ?", ["%{$placaLimpa}%"]);
            }
        });
    }

    public function scopeComStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Veículos com licenciamento vencendo nos próximos N dias.
     */
    public function scopeLicenciamentoProximo(Builder $query, int $dias = 30): Builder
    {
        return $query->whereNotNull('licenciamento_vencimento')
            ->whereDate('licenciamento_vencimento', '<=', now()->addDays($dias))
            ->whereDate('licenciamento_vencimento', '>=', now()->subDays(7));
    }

    /**
     * Veículos com seguro vencendo nos próximos N dias.
     */
    public function scopeSeguroProximo(Builder $query, int $dias = 30): Builder
    {
        return $query->whereNotNull('seguro_vencimento')
            ->whereDate('seguro_vencimento', '<=', now()->addDays($dias))
            ->whereDate('seguro_vencimento', '>=', now()->subDays(7));
    }

    // ============================================================
    // Accessors
    // ============================================================

    /**
     * Placa formatada para exibição (antiga com hífen, Mercosul sem).
     */
    protected function placaFormatada(): Attribute
    {
        return Attribute::get(fn (): string => PlacaRule::formatar($this->placa ?? ''));
    }

    /**
     * Identificação curta: "Marca Modelo (ABC-1234)"
     */
    protected function identificacao(): Attribute
    {
        return Attribute::get(fn (): string => trim("{$this->marca} {$this->modelo}") . ' (' . $this->placa_formatada . ')');
    }

    /**
     * Licenciamento vencido?
     */
    protected function licenciamentoVencido(): Attribute
    {
        return Attribute::get(function (): bool {
            return $this->licenciamento_vencimento !== null
                && $this->licenciamento_vencimento->isPast();
        });
    }

    /**
     * Seguro vencido?
     */
    protected function seguroVencido(): Attribute
    {
        return Attribute::get(function (): bool {
            return $this->seguro_vencimento !== null
                && $this->seguro_vencimento->isPast();
        });
    }

    /**
     * Label amigável do status.
     */
    protected function statusLabel(): Attribute
    {
        return Attribute::get(fn (): string => match ($this->status) {
            self::STATUS_DISPONIVEL => 'Disponível',
            self::STATUS_EM_USO => 'Em uso',
            self::STATUS_EM_MANUTENCAO => 'Em manutenção',
            self::STATUS_INATIVO => 'Inativo',
            self::STATUS_VENDIDO => 'Vendido',
            default => (string) $this->status,
        });
    }

    protected function statusCor(): Attribute
    {
        return Attribute::get(fn (): string => match ($this->status) {
            self::STATUS_DISPONIVEL => 'green',
            self::STATUS_EM_USO => 'blue',
            self::STATUS_EM_MANUTENCAO => 'yellow',
            self::STATUS_INATIVO => 'gray',
            self::STATUS_VENDIDO => 'red',
            default => 'gray',
        });
    }

    // ============================================================
    // Mutators
    // ============================================================

    /**
     * Armazena placa só com letras/dígitos maiúsculos.
     */
    protected function placa(): Attribute
    {
        return Attribute::set(fn (?string $value): ?string => $value
            ? PlacaRule::limpar($value)
            : null);
    }

    // ============================================================
    // Activity Log
    // ============================================================

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logExcept(['updated_at', 'created_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('veiculo');
    }
}
