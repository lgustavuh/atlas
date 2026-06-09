<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\AuditaUsuario;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Documento da Biblioteca Padrão.
 * Repositório central de modelos, normas, manuais, contratos.
 */
class BibliotecaDocumento extends Model
{
    use HasFactory;
    use AuditaUsuario;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'biblioteca_documentos';

    /** @var list<string> */
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'arquivo_tamanho_bytes' => 'integer',
            'downloads_count' => 'integer',
        ];
    }

    public function areas(): BelongsToMany
    {
        return $this->belongsToMany(
            BibliotecaArea::class,
            'biblioteca_documento_areas',
            'documento_id',
            'area_id',
        );
    }

    public function scopeBuscar(Builder $query, string $termo): Builder
    {
        $termo = trim($termo);
        if ($termo === '') {
            return $query;
        }
        $like = '%' . strtolower($termo) . '%';
        return $query->where(function (Builder $q) use ($like): void {
            $q->whereRaw('LOWER(titulo) LIKE ?', [$like])
              ->orWhereRaw('LOWER(descricao) LIKE ?', [$like]);
        });
    }

    public function scopeDaArea(Builder $query, int $areaId): Builder
    {
        return $query->whereHas('areas', fn (Builder $sub) => $sub->where('biblioteca_areas.id', $areaId));
    }

    /**
     * Tamanho legível (1.5 MB, 234 KB, etc).
     */
    protected function tamanhoLegivel(): Attribute
    {
        return Attribute::get(function (): string {
            $bytes = (int) $this->arquivo_tamanho_bytes;
            if ($bytes < 1024) {
                return "{$bytes} B";
            }
            if ($bytes < 1024 * 1024) {
                return number_format($bytes / 1024, 1, ',', '.') . ' KB';
            }
            if ($bytes < 1024 * 1024 * 1024) {
                return number_format($bytes / (1024 * 1024), 1, ',', '.') . ' MB';
            }
            return number_format($bytes / (1024 * 1024 * 1024), 1, ',', '.') . ' GB';
        });
    }

    /**
     * Ícone Tabler conforme o tipo MIME.
     */
    protected function icone(): Attribute
    {
        return Attribute::get(fn (): string => match (true) {
            str_contains((string) $this->arquivo_mime, 'pdf') => 'file-type-pdf',
            str_contains((string) $this->arquivo_mime, 'wordprocessingml'),
            str_contains((string) $this->arquivo_mime, 'msword') => 'file-type-doc',
            str_contains((string) $this->arquivo_mime, 'spreadsheetml'),
            str_contains((string) $this->arquivo_mime, 'excel') => 'file-type-xls',
            str_contains((string) $this->arquivo_mime, 'image/') => 'photo',
            str_contains((string) $this->arquivo_mime, 'zip') => 'file-zip',
            default => 'file',
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logExcept(['updated_at', 'created_at', 'arquivo_hash', 'downloads_count'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('biblioteca_documento');
    }
}
