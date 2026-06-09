<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

/**
 * Preenche automaticamente os campos de auditoria (created_by, updated_by, deleted_by)
 * com o usuário autenticado, sem permitir mass-assignment desses campos.
 *
 * Como os campos estão em $guarded, o setter explícito aqui é a única forma
 * de defini-los — impedindo que um atacante forge a autoria via input.
 *
 * Usar em models que tiverem as colunas correspondentes:
 *
 *   class MeuModel extends Model
 *   {
 *       use AuditaUsuario;
 *   }
 *
 * Detecta automaticamente quais colunas existem (created_by, updated_by, deleted_by)
 * via cache do Schema, então é seguro aplicar em models que não tenham todas.
 */
trait AuditaUsuario
{
    /**
     * @var array<class-string, array{has_created: bool, has_updated: bool, has_deleted: bool}>|null
     */
    private static ?array $auditColumnsCache = null;

    public static function bootAuditaUsuario(): void
    {
        static::creating(function (Model $model): void {
            $cols = self::detectarColunas($model);
            $userId = Auth::check() ? Auth::id() : null;

            // SEMPRE força o valor — mesmo que tenha sido passado via mass-assignment.
            // Isso impede que um atacante forge a autoria via input do form.
            if ($cols['has_created']) {
                $model->setAttribute('created_by', $userId);
            }
            if ($cols['has_updated']) {
                $model->setAttribute('updated_by', $userId);
            }
        });

        static::updating(function (Model $model): void {
            $cols = self::detectarColunas($model);

            if ($cols['has_updated']) {
                // SEMPRE força updated_by, sobrescrevendo qualquer tentativa via input
                $model->setAttribute('updated_by', Auth::check() ? Auth::id() : null);
            }
            // created_by é imutável — se foi sujado, restaura
            if ($cols['has_created'] && $model->isDirty('created_by')) {
                $model->setAttribute('created_by', $model->getOriginal('created_by'));
            }
        });

        static::deleting(function (Model $model): void {
            $cols = self::detectarColunas($model);

            // Só registra deleted_by em soft delete
            if ($cols['has_deleted']
                && Auth::check()
                && method_exists($model, 'trashed')
                && !$model->isForceDeleting()) {
                $model->setAttribute('deleted_by', Auth::id());
                $model->saveQuietly();
            }
        });
    }

    /**
     * @return array{has_created: bool, has_updated: bool, has_deleted: bool}
     */
    private static function detectarColunas(Model $model): array
    {
        $class = static::class;

        if (isset(self::$auditColumnsCache[$class])) {
            return self::$auditColumnsCache[$class];
        }

        $table = $model->getTable();

        $cache = [
            'has_created' => Schema::hasColumn($table, 'created_by'),
            'has_updated' => Schema::hasColumn($table, 'updated_by'),
            'has_deleted' => Schema::hasColumn($table, 'deleted_by'),
        ];

        self::$auditColumnsCache[$class] = $cache;
        return $cache;
    }
}
