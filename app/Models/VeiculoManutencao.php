<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\AuditaUsuario;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Manutenção de veículo (preventiva, corretiva, revisão, etc).
 */
class VeiculoManutencao extends Model
{
    use HasFactory;
    use AuditaUsuario;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'veiculo_manutencoes';

    public const TIPO_PREVENTIVA = 'preventiva';
    public const TIPO_CORRETIVA = 'corretiva';
    public const TIPO_REVISAO = 'revisao';
    public const TIPO_TROCA_OLEO = 'troca_oleo';
    public const TIPO_PNEUS = 'pneus';
    public const TIPO_ELETRICA = 'eletrica';
    public const TIPO_FUNILARIA = 'funilaria';
    public const TIPO_OUTRO = 'outro';

    /** @var list<string> */
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'data_manutencao' => 'date',
            'km_no_momento' => 'integer',
            'valor' => 'decimal:2',
            'proxima_manutencao_data' => 'date',
            'proxima_manutencao_km' => 'integer',
        ];
    }

    // ============================================================
    // Relacionamentos
    // ============================================================

    public function veiculo(): BelongsTo
    {
        return $this->belongsTo(Veiculo::class);
    }

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Fornecedor::class);
    }

    // ============================================================
    // Scopes
    // ============================================================

    public function scopeDoVeiculo(Builder $query, int $veiculoId): Builder
    {
        return $query->where('veiculo_id', $veiculoId);
    }

    public function scopeDoTipo(Builder $query, string $tipo): Builder
    {
        return $query->where('tipo', $tipo);
    }

    /**
     * Manutenções com data ou km de próxima manutenção próximos.
     */
    public function scopeProximasManutencoes(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->whereDate('proxima_manutencao_data', '<=', now()->addDays(30))
              ->whereDate('proxima_manutencao_data', '>=', now()->subDays(7));
        });
    }

    // ============================================================
    // Accessors
    // ============================================================

    protected function tipoLabel(): Attribute
    {
        return Attribute::get(fn (): string => match ($this->tipo) {
            self::TIPO_PREVENTIVA => 'Preventiva',
            self::TIPO_CORRETIVA => 'Corretiva',
            self::TIPO_REVISAO => 'Revisão',
            self::TIPO_TROCA_OLEO => 'Troca de óleo',
            self::TIPO_PNEUS => 'Pneus',
            self::TIPO_ELETRICA => 'Elétrica',
            self::TIPO_FUNILARIA => 'Funilaria',
            self::TIPO_OUTRO => 'Outro',
            default => (string) $this->tipo,
        });
    }

    /**
     * Lista todos os tipos com label, útil para selects.
     *
     * @return array<string, string>
     */
    public static function tiposComLabel(): array
    {
        return [
            self::TIPO_PREVENTIVA => 'Preventiva',
            self::TIPO_CORRETIVA => 'Corretiva',
            self::TIPO_REVISAO => 'Revisão',
            self::TIPO_TROCA_OLEO => 'Troca de óleo',
            self::TIPO_PNEUS => 'Pneus',
            self::TIPO_ELETRICA => 'Elétrica',
            self::TIPO_FUNILARIA => 'Funilaria',
            self::TIPO_OUTRO => 'Outro',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logExcept(['updated_at', 'created_at', 'comprovante_path'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('manutencao');
    }
}
