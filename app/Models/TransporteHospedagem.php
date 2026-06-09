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
 * Transporte e/ou Hospedagem para colaborador em viagem a serviço.
 *
 * tipo discriminador:
 *   transporte | hospedagem | ambos
 *
 * Campos relevantes variam conforme o tipo:
 *   - transporte: origem, destino, meio_transporte
 *   - hospedagem: hospedagem_local, hospedagem_endereco
 *   - ambos: todos os campos acima
 */
class TransporteHospedagem extends Model
{
    use HasFactory;
    use AuditaUsuario;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'transportes_hospedagens';

    public const TIPO_TRANSPORTE = 'transporte';
    public const TIPO_HOSPEDAGEM = 'hospedagem';
    public const TIPO_AMBOS = 'ambos';

    public const MEIO_ONIBUS = 'onibus';
    public const MEIO_AVIAO = 'aviao';
    public const MEIO_CARRO_PROPRIO = 'carro_proprio';
    public const MEIO_CARRO_EMPRESA = 'carro_empresa';
    public const MEIO_VAN = 'van';
    public const MEIO_OUTRO = 'outro';

    /** @var list<string> */
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'data_inicio' => 'date',
            'data_fim' => 'date',
            'valor' => 'decimal:2',
        ];
    }

    // ============================================================
    // Relacionamentos
    // ============================================================

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class);
    }

    public function obra(): BelongsTo
    {
        return $this->belongsTo(Obra::class);
    }

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Fornecedor::class);
    }

    public function hospedagemCidade(): BelongsTo
    {
        return $this->belongsTo(Cidade::class, 'hospedagem_cidade_id');
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
            $q->whereRaw('LOWER(origem) LIKE ?', [$like])
              ->orWhereRaw('LOWER(destino) LIKE ?', [$like])
              ->orWhereRaw('LOWER(hospedagem_local) LIKE ?', [$like])
              ->orWhereHas('colaborador', fn (Builder $sub) => $sub->whereRaw('LOWER(nome) LIKE ?', [$like]));
        });
    }

    public function scopeDoColaborador(Builder $query, int $colaboradorId): Builder
    {
        return $query->where('colaborador_id', $colaboradorId);
    }

    public function scopeDaObra(Builder $query, int $obraId): Builder
    {
        return $query->where('obra_id', $obraId);
    }

    public function scopeDoTipo(Builder $query, string $tipo): Builder
    {
        return $query->where('tipo', $tipo);
    }

    /**
     * Em andamento neste momento (data_inicio <= hoje E (data_fim NULL OU data_fim >= hoje)).
     */
    public function scopeEmAndamento(Builder $query): Builder
    {
        $hoje = now()->toDateString();
        return $query->whereDate('data_inicio', '<=', $hoje)
            ->where(function (Builder $q) use ($hoje): void {
                $q->whereNull('data_fim')->orWhereDate('data_fim', '>=', $hoje);
            });
    }

    // ============================================================
    // Accessors
    // ============================================================

    protected function tipoLabel(): Attribute
    {
        return Attribute::get(fn (): string => match ($this->tipo) {
            self::TIPO_TRANSPORTE => 'Transporte',
            self::TIPO_HOSPEDAGEM => 'Hospedagem',
            self::TIPO_AMBOS => 'Transporte + Hospedagem',
            default => (string) $this->tipo,
        });
    }

    protected function tipoCor(): Attribute
    {
        return Attribute::get(fn (): string => match ($this->tipo) {
            self::TIPO_TRANSPORTE => 'blue',
            self::TIPO_HOSPEDAGEM => 'purple',
            self::TIPO_AMBOS => 'indigo',
            default => 'gray',
        });
    }

    protected function meioTransporteLabel(): Attribute
    {
        return Attribute::get(fn (): string => match ($this->meio_transporte) {
            self::MEIO_ONIBUS => 'Ônibus',
            self::MEIO_AVIAO => 'Avião',
            self::MEIO_CARRO_PROPRIO => 'Carro próprio',
            self::MEIO_CARRO_EMPRESA => 'Carro da empresa',
            self::MEIO_VAN => 'Van',
            self::MEIO_OUTRO => 'Outro',
            null => '',
            default => (string) $this->meio_transporte,
        });
    }

    /**
     * Status temporal: futura, em andamento, ou concluída.
     */
    protected function statusTemporal(): Attribute
    {
        return Attribute::get(function (): string {
            $hoje = now()->startOfDay();
            if ($this->data_inicio && $this->data_inicio->gt($hoje)) {
                return 'futura';
            }
            if ($this->data_fim && $this->data_fim->lt($hoje)) {
                return 'concluida';
            }
            return 'em_andamento';
        });
    }

    protected function statusTemporalLabel(): Attribute
    {
        return Attribute::get(fn (): string => match ($this->status_temporal) {
            'futura' => 'Futura',
            'em_andamento' => 'Em andamento',
            'concluida' => 'Concluída',
            default => '—',
        });
    }

    protected function statusTemporalCor(): Attribute
    {
        return Attribute::get(fn (): string => match ($this->status_temporal) {
            'futura' => 'gray',
            'em_andamento' => 'green',
            'concluida' => 'blue',
            default => 'gray',
        });
    }

    /**
     * Tem componente de transporte?
     */
    public function temTransporte(): bool
    {
        return in_array($this->tipo, [self::TIPO_TRANSPORTE, self::TIPO_AMBOS], true);
    }

    /**
     * Tem componente de hospedagem?
     */
    public function temHospedagem(): bool
    {
        return in_array($this->tipo, [self::TIPO_HOSPEDAGEM, self::TIPO_AMBOS], true);
    }

    /**
     * @return array<string, string>
     */
    public static function tiposComLabel(): array
    {
        return [
            self::TIPO_TRANSPORTE => 'Transporte',
            self::TIPO_HOSPEDAGEM => 'Hospedagem',
            self::TIPO_AMBOS => 'Transporte + Hospedagem',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function meiosTransporteComLabel(): array
    {
        return [
            self::MEIO_ONIBUS => 'Ônibus',
            self::MEIO_AVIAO => 'Avião',
            self::MEIO_CARRO_PROPRIO => 'Carro próprio',
            self::MEIO_CARRO_EMPRESA => 'Carro da empresa',
            self::MEIO_VAN => 'Van',
            self::MEIO_OUTRO => 'Outro',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logExcept(['updated_at', 'created_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('transporte_hospedagem');
    }
}
