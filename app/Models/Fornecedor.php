<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\AuditaUsuario;

use App\Rules\Cnpj as CnpjRule;
use App\Rules\Cpf as CpfRule;
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
 * Fornecedor (pessoa física ou jurídica que vende para a empresa).
 */
class Fornecedor extends Model
{
    use HasFactory;
    use AuditaUsuario;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'fornecedores';

    /** @var list<string> */
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'homologado' => 'boolean',
            'avaliacao' => 'integer',
        ];
    }

    // ============================================================
    // Relacionamentos
    // ============================================================

    public function cidade(): BelongsTo
    {
        return $this->belongsTo(Cidade::class);
    }

    public function pedidosCompra(): HasMany
    {
        return $this->hasMany(PedidoCompra::class);
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

        $apenasNumeros = preg_replace('/[^0-9]/', '', $termo);
        $like = '%' . strtolower($termo) . '%';

        return $query->where(function (Builder $q) use ($like, $apenasNumeros): void {
            $q->whereRaw('LOWER(razao_social) LIKE ?', [$like])
              ->orWhereRaw('LOWER(nome_fantasia) LIKE ?', [$like]);

            if ($apenasNumeros !== '' && strlen($apenasNumeros) >= 3) {
                $q->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(cnpj_cpf, '.', ''), '/', ''), '-', ''), ' ', '') LIKE ?",
                    ["%{$apenasNumeros}%"]);
            }
        });
    }

    public function scopeHomologados(Builder $query): Builder
    {
        return $query->where('homologado', true);
    }

    // ============================================================
    // Accessors
    // ============================================================

    /**
     * CNPJ ou CPF formatado conforme o tipo de pessoa.
     */
    protected function documentoFormatado(): Attribute
    {
        return Attribute::get(function (): string {
            if (!$this->cnpj_cpf) {
                return '';
            }
            return $this->tipo_pessoa === 'fisica'
                ? CpfRule::formatar($this->cnpj_cpf)
                : CnpjRule::formatar($this->cnpj_cpf);
        });
    }

    /**
     * Nome usado para exibição (nome_fantasia se houver, senão razao_social).
     */
    protected function nomeExibicao(): Attribute
    {
        return Attribute::get(fn (): string => $this->nome_fantasia ?: $this->razao_social);
    }

    // ============================================================
    // Mutators
    // ============================================================

    /**
     * Armazena CNPJ/CPF apenas com dígitos.
     */
    protected function cnpjCpf(): Attribute
    {
        return Attribute::set(fn (?string $value): ?string => $value
            ? (preg_replace('/[^0-9]/', '', $value) ?: null)
            : null);
    }

    // ============================================================
    // Activity Log
    // ============================================================

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logExcept(['updated_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('fornecedor');
    }
}
