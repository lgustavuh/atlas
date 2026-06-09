<?php

declare(strict_types=1);

namespace App\Livewire\Auditoria;

use App\Exports\AuditoriaExport;
use App\Livewire\Concerns\ExportaExcel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

/**
 * Tela de auditoria: consulta o registro de todas as atividades do sistema.
 *
 * O activity_log é populado automaticamente pelo trait LogsActivity dos models.
 * Esta tela apenas exibe e filtra os registros.
 */
#[Layout('layouts.app')]
#[Title('Auditoria')]
class Consultar extends Component
{
    use ExportaExcel;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'modulo')]
    public string $filterLogName = '';

    #[Url(as: 'evento')]
    public string $filterEvent = '';

    #[Url(as: 'user')]
    public ?int $filterCauserId = null;

    #[Url(as: 'de')]
    public ?string $filterDataDe = null;

    #[Url(as: 'ate')]
    public ?string $filterDataAte = null;

    public bool $showDetalhe = false;
    public ?int $detalheId = null;

    public function mount(): void
    {
        if (!auth()->user()?->can('audit.view-any')) {
            abort(403);
        }
    }

    public function updating(string $name): void
    {
        if (in_array($name, ['search', 'filterLogName', 'filterEvent', 'filterCauserId', 'filterDataDe', 'filterDataAte'])) {
            $this->resetPage();
        }
    }

    public function limparFiltros(): void
    {
        $this->reset(['search', 'filterLogName', 'filterEvent', 'filterCauserId', 'filterDataDe', 'filterDataAte']);
        $this->resetPage();
    }

    public function abrirDetalhe(int $id): void
    {
        $this->detalheId = $id;
        $this->showDetalhe = true;
    }

    public function fecharDetalhe(): void
    {
        $this->showDetalhe = false;
        $this->detalheId = null;
    }

    /**
     * @return \Closure(Builder): Builder
     */
    protected function aplicarFiltros(): \Closure
    {
        return function (Builder $q): Builder {
            return $q
                ->when($this->search !== '', function (Builder $q): void {
                    $like = '%' . strtolower($this->search) . '%';
                    $q->whereRaw('LOWER(description) LIKE ?', [$like]);
                })
                ->when($this->filterLogName !== '', fn (Builder $q) => $q->where('log_name', $this->filterLogName))
                ->when($this->filterEvent !== '', fn (Builder $q) => $q->where('event', $this->filterEvent))
                ->when($this->filterCauserId, fn (Builder $q) => $q->where('causer_id', $this->filterCauserId)->where('causer_type', \App\Models\User::class))
                ->when($this->filterDataDe, fn (Builder $q) => $q->whereDate('created_at', '>=', $this->filterDataDe))
                ->when($this->filterDataAte, fn (Builder $q) => $q->whereDate('created_at', '<=', $this->filterDataAte));
        };
    }

    public function exportar()
    {
        if (!auth()->user()?->can('audit.view-any')) {
            abort(403);
        }
        return $this->fazerDownload(new AuditoriaExport($this->aplicarFiltros()), 'auditoria');
    }

    public function render()
    {
        $query = Activity::query()
            ->with('causer:id,name,email')
            ->orderByDesc('id');

        $query = ($this->aplicarFiltros())($query);

        // Detalhe expandido
        $detalhe = $this->detalheId ? Activity::with('causer:id,name,email')->find($this->detalheId) : null;

        // Listas para filtros (cache leve via select distinct)
        $logNames = DB::table('activity_log')
            ->select('log_name')
            ->whereNotNull('log_name')
            ->distinct()
            ->orderBy('log_name')
            ->pluck('log_name');

        $eventos = DB::table('activity_log')
            ->select('event')
            ->whereNotNull('event')
            ->distinct()
            ->orderBy('event')
            ->pluck('event');

        // Usuários que aparecem no log (limit para não explodir)
        $usuarios = \App\Models\User::query()
            ->whereExists(function ($q): void {
                $q->select(DB::raw(1))
                  ->from('activity_log')
                  ->whereColumn('causer_id', 'users.id')
                  ->where('causer_type', \App\Models\User::class);
            })
            ->orderBy('name')
            ->limit(200)
            ->get(['id', 'name']);

        return view('livewire.auditoria.consultar', [
            'atividades' => $query->paginate(25),
            'detalhe' => $detalhe,
            'logNames' => $logNames,
            'eventos' => $eventos,
            'usuarios' => $usuarios,
            'stats' => [
                'total' => Activity::count(),
                'hoje' => Activity::whereDate('created_at', now())->count(),
                'ultima_hora' => Activity::where('created_at', '>=', now()->subHour())->count(),
            ],
        ]);
    }
}
