<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

/**
 * Usuário do sistema.
 *
 * Combina:
 *   - Autenticação (Authenticatable)
 *   - Verificação de email (MustVerifyEmail)
 *   - Soft delete (deleted_at em vez de Status='Desativado')
 *   - Permissões granulares (HasRoles do spatie)
 *   - Auditoria automática (LogsActivity)
 *
 * Campos sensíveis (password, remember_token) NUNCA são serializados.
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory;
    use HasRoles;
    use LogsActivity;
    use Notifiable;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'active',
        'colaborador_id',
        'created_by',
        'updated_by',
        'last_login_at',
        'last_login_ip',
        'failed_login_attempts',
        'locked_until',
    ];

    /**
     * Nunca expor em arrays/JSON.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casts automáticos. 'password' => 'hashed' garante que qualquer
     * atribuição $user->password = '...' aplica Hash::make() — não tem como
     * salvar senha em texto puro por engano.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
            'last_login_at' => 'datetime',
            'locked_until' => 'datetime',
        ];
    }

    // ============================================================
    // Relacionamentos
    // ============================================================

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class);
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
    // Accessors
    // ============================================================

    /**
     * Está atualmente bloqueado por excesso de tentativas?
     */
    protected function isLocked(): Attribute
    {
        return Attribute::get(fn (): bool => $this->locked_until !== null && $this->locked_until->isFuture());
    }

    /**
     * Primeiro nome (para saudações na UI).
     */
    protected function firstName(): Attribute
    {
        return Attribute::get(fn (): string => explode(' ', $this->name)[0]);
    }

    // ============================================================
    // Métodos auxiliares
    // ============================================================

    /**
     * Registra um login bem-sucedido. Limpa contadores de falha.
     */
    public function registrarLoginSucesso(?string $ip = null): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);
    }

    /**
     * Registra uma tentativa de login falhada.
     * Bloqueia a conta após N tentativas.
     */
    public function registrarTentativaFalha(): void
    {
        $maxTentativas = config('atlas.security.login_max_attempts', 5);

        $this->increment('failed_login_attempts');

        if ($this->failed_login_attempts >= $maxTentativas) {
            // Bloqueia por 15 minutos
            $this->update(['locked_until' => now()->addMinutes(15)]);
        }
    }

    /**
     * O usuário pode logar?
     */
    public function podeAutenticar(): bool
    {
        return $this->active && !$this->is_locked && $this->deleted_at === null;
    }

    /**
     * Envia o email de recuperação de senha usando nossa notification em português.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new \App\Notifications\ResetPasswordNotification($token));
    }

    // ============================================================
    // Activity Log (spatie/laravel-activitylog)
    // ============================================================

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'active', 'colaborador_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('user');
    }
}
