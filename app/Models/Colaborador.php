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
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Colaborador (funcionário).
 *
 * Tabela central do sistema. Demais módulos (advertências, atestados, férias)
 * referenciam esta tabela.
 *
 * Soft delete: registros "desativados" no legado viram deleted_at aqui.
 * Activity log: TODA alteração é registrada automaticamente.
 */
class Colaborador extends Model
{
    use HasFactory;
    use AuditaUsuario;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'colaboradores';

    /** @var list<string> */
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * Casts automáticos.
     */
    protected function casts(): array
    {
        return [
            'data_nascimento' => 'date',
            'data_admissao' => 'date',
            'data_demissao' => 'date',
            'cnh_validade' => 'date',
            'rg_data_emissao' => 'date',
            'salario' => 'decimal:2',
            'doador_orgaos' => 'boolean',
            'pcd' => 'boolean',
            'horario_entrada' => 'datetime:H:i',
            'horario_saida' => 'datetime:H:i',
        ];
    }

    // ============================================================
    // Relacionamentos
    // ============================================================

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class);
    }

    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class);
    }

    /**
     * Usuario do sistema vinculado a este colaborador (se houver).
     * Um colaborador pode ter no maximo UM usuario (relacao 1:1).
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function naturalidadeCidade(): BelongsTo
    {
        return $this->belongsTo(Cidade::class, 'naturalidade_cidade_id');
    }

    public function enderecos(): HasMany
    {
        return $this->hasMany(ColaboradorEndereco::class);
    }

    public function enderecoResidencial(): HasOne
    {
        return $this->hasOne(ColaboradorEndereco::class)
            ->where('tipo', 'residencial')
            ->where('principal', true);
    }

    public function dependentes(): HasMany
    {
        return $this->hasMany(ColaboradorDependente::class);
    }

    public function ocupacoesRepublica(): HasMany
    {
        return $this->hasMany(RepublicaOcupacao::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ============================================================
    // Scopes (filtros reusáveis)
    // ============================================================

    /**
     * Apenas ativos (não desativados). Usado por padrão por causa do SoftDeletes,
     * mas explícito aqui para clareza no código de negócio.
     */
    public function scopeAtivos(Builder $query): Builder
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Busca por nome, CPF, matrícula ou email.
     */
    public function scopeBuscar(Builder $query, string $termo): Builder
    {
        $termo = trim($termo);
        if ($termo === '') {
            return $query;
        }

        $cpfLimpo = preg_replace('/[^0-9]/', '', $termo);
        $like = '%' . strtolower($termo) . '%';

        return $query->where(function (Builder $q) use ($like, $cpfLimpo): void {
            $q->whereRaw('LOWER(nome) LIKE ?', [$like])
                ->orWhereRaw('LOWER(matricula) LIKE ?', [$like])
                ->orWhereRaw('LOWER(email) LIKE ?', [$like]);

            // Se o termo parecer um CPF, busca também por CPF
            if ($cpfLimpo !== '' && strlen($cpfLimpo) >= 3) {
                $q->orWhere('cpf', 'LIKE', "%{$cpfLimpo}%")
                  ->orWhereRaw("REPLACE(REPLACE(cpf, '.', ''), '-', '') LIKE ?", ["%{$cpfLimpo}%"]);
            }
        });
    }

    public function scopeDoCargo(Builder $query, int $cargoId): Builder
    {
        return $query->where('cargo_id', $cargoId);
    }

    public function scopeDoDepartamento(Builder $query, int $departamentoId): Builder
    {
        return $query->where('departamento_id', $departamentoId);
    }

    // ============================================================
    // Accessors
    // ============================================================

    /**
     * Primeiro nome (para saudações).
     */
    protected function primeiroNome(): Attribute
    {
        return Attribute::get(fn (): string => explode(' ', $this->nome ?? '')[0]);
    }

    /**
     * CPF formatado: 000.000.000-00
     */
    protected function cpfFormatado(): Attribute
    {
        return Attribute::get(fn (): string => $this->cpf ? CpfRule::formatar($this->cpf) : '');
    }

    /**
     * URL da foto (ou avatar gerado se não tem foto).
     */
    protected function fotoUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            if ($this->foto_path && Storage::disk('local')->exists($this->foto_path)) {
                // Rota interna que verifica permissão antes de servir
                return route('colaboradores.foto', $this->id);
            }
            return null;
        });
    }

    /**
     * Iniciais (para avatar quando não há foto).
     */
    protected function iniciais(): Attribute
    {
        return Attribute::get(function (): string {
            $partes = explode(' ', trim($this->nome ?? ''));
            if (count($partes) === 1) {
                return strtoupper(substr($partes[0], 0, 2));
            }
            return strtoupper(substr($partes[0], 0, 1) . substr(end($partes), 0, 1));
        });
    }

    /**
     * Idade calculada.
     */
    protected function idade(): Attribute
    {
        return Attribute::get(fn (): ?int => $this->data_nascimento?->age);
    }

    /**
     * Tempo de empresa em meses (para cálculo de férias e cargos).
     */
    protected function tempoEmpresaMeses(): Attribute
    {
        return Attribute::get(function (): ?int {
            if (!$this->data_admissao) {
                return null;
            }
            $fim = $this->data_demissao ?? now();
            return (int) $this->data_admissao->diffInMonths($fim);
        });
    }

    /**
     * Está ativo na empresa? (admitido e não demitido).
     */
    protected function ativoNaEmpresa(): Attribute
    {
        return Attribute::get(fn (): bool => $this->data_admissao !== null && $this->data_demissao === null && $this->deleted_at === null);
    }

    // ============================================================
    // Mutators
    // ============================================================

    /**
     * Sempre armazena CPF apenas com dígitos (sem máscara).
     * Aceita "000.000.000-00" ou "00000000000" na entrada.
     */
    protected function cpf(): Attribute
    {
        return Attribute::set(fn (?string $value): ?string => $value ? CpfRule::limpar($value) : null);
    }

    /**
     * Mesma coisa para o PIS.
     */
    protected function pis(): Attribute
    {
        return Attribute::set(fn (?string $value): ?string => $value ? preg_replace('/[^0-9]/', '', $value) : null);
    }

    // ============================================================
    // Activity Log
    // ============================================================

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logExcept(['updated_at', 'created_at', 'foto_path'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('colaborador');
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return match ($eventName) {
            'created' => "Colaborador {$this->nome} foi cadastrado",
            'updated' => "Colaborador {$this->nome} foi atualizado",
            'deleted' => "Colaborador {$this->nome} foi desativado",
            'restored' => "Colaborador {$this->nome} foi reativado",
            default => "Colaborador {$this->nome} - evento {$eventName}",
        };
    }
}
