<?php

declare(strict_types=1);

namespace App\Livewire\Alertas;

use App\Models\AlertaAdm;
use App\Models\Colaborador;
use App\Services\AlertaAdmService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Alertas Administrativos')]
class Gerenciar extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'p')]
    public string $filterPrioridade = '';

    #[Url(as: 'st')]
    public string $filterStatus = ''; // '', 'ativos', 'inativos'

    public bool $showModal = false;
    public bool $editando = false;
    public ?int $editingId = null;

    // Form
    public string $titulo = '';
    public string $mensagem = '';
    public string $prioridade = AlertaAdm::PRIORIDADE_NORMAL;
    public bool $ativo = true;
    public ?string $data_inicio = null;
    public ?string $data_fim = null;
    /** @var list<int> */
    public array $colaboradorIds = [];
    public bool $enviarParaTodos = false;
    public string $searchColaborador = '';

    public bool $showDeleteModal = false;
    public ?int $deletingId = null;
    public string $deletingName = '';

    public function mount(): void
    {
        $this->authorize('viewAny', AlertaAdm::class);
    }

    public function updating(string $name): void
    {
        if (in_array($name, ['search', 'filterPrioridade', 'filterStatus'])) {
            $this->resetPage();
        }
    }

    public function openCreate(): void
    {
        $this->authorize('create', AlertaAdm::class);
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $a = AlertaAdm::with('colaboradores:id')->findOrFail($id);
        $this->authorize('update', $a);

        $this->editingId = $a->id;
        $this->titulo = $a->titulo;
        $this->mensagem = $a->mensagem;
        $this->prioridade = $a->prioridade;
        $this->ativo = $a->ativo;
        $this->data_inicio = $a->data_inicio?->toDateString();
        $this->data_fim = $a->data_fim?->toDateString();
        $this->colaboradorIds = $a->colaboradores->pluck('id')->all();
        $this->enviarParaTodos = false;
        $this->editando = true;
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function save(AlertaAdmService $service): void
    {
        $data = $this->validate([
            'titulo' => ['required', 'string', 'min:3', 'max:200'],
            'mensagem' => ['required', 'string', 'min:5', 'max:5000'],
            'prioridade' => ['required', Rule::in(array_keys(AlertaAdm::prioridadesComLabel()))],
            'ativo' => ['boolean'],
            'data_inicio' => ['nullable', 'date'],
            'data_fim' => ['nullable', 'date', 'after_or_equal:data_inicio'],
            'colaboradorIds' => ['array'],
            'colaboradorIds.*' => ['integer', 'exists:colaboradores,id'],
            'enviarParaTodos' => ['boolean'],
        ], messages: [
            'mensagem.min' => 'A mensagem deve ter pelo menos 5 caracteres.',
            'data_fim.after_or_equal' => 'A data fim deve ser após a data início.',
        ]);

        try {
            $payload = [
                'titulo' => $data['titulo'],
                'mensagem' => $data['mensagem'],
                'prioridade' => $data['prioridade'],
                'ativo' => $data['ativo'],
                'data_inicio' => $data['data_inicio'] ?: null,
                'data_fim' => $data['data_fim'] ?: null,
            ];

            if ($this->editando) {
                $alerta = AlertaAdm::findOrFail($this->editingId);
                $this->authorize('update', $alerta);
                $alerta = $service->atualizar($alerta, $payload, $this->colaboradorIds);
                $this->dispatch('toast', type: 'success', message: 'Alerta atualizado.');
            } else {
                $this->authorize('create', AlertaAdm::class);
                $alerta = $service->criar($payload, $this->colaboradorIds);
                $this->dispatch('toast', type: 'success', message: 'Alerta criado.');
            }

            // Se marcou "enviar para todos", aplica depois (sobrescreve a seleção manual)
            if ($this->enviarParaTodos) {
                $total = $service->enviarParaTodos($alerta);
                $this->dispatch('toast', type: 'success',
                    message: "Alerta enviado para {$total} colaboradores ativos.");
            }

            $this->closeModal();
        } catch (\Throwable $e) {
            \Log::error('Erro ao salvar alerta administrativo', ['erro' => $e->getMessage()]);
            if (app()->environment('testing', 'local')) {
                throw $e;
            }
            $this->dispatch('toast', type: 'error', message: 'Erro ao salvar alerta.');
        }
    }

    public function toggleAtivo(int $id): void
    {
        $a = AlertaAdm::findOrFail($id);
        $this->authorize('update', $a);
        $a->update(['ativo' => !$a->ativo, 'updated_by' => Auth::id()]);
        $msg = $a->ativo ? 'Alerta ativado.' : 'Alerta desativado.';
        $this->dispatch('toast', type: 'success', message: $msg);
    }

    public function confirmDelete(int $id): void
    {
        $a = AlertaAdm::findOrFail($id);
        $this->authorize('delete', $a);
        $this->deletingId = $id;
        $this->deletingName = $a->titulo;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $a = AlertaAdm::findOrFail($this->deletingId);
        $this->authorize('delete', $a);
        $a->update(['updated_by' => Auth::id()]);
        $a->delete();
        $this->dispatch('toast', type: 'success', message: 'Alerta excluído.');
        $this->showDeleteModal = false;
    }

    private function resetForm(): void
    {
        $this->reset([
            'titulo', 'mensagem', 'data_inicio', 'data_fim',
            'searchColaborador', 'editingId', 'editando',
        ]);
        $this->prioridade = AlertaAdm::PRIORIDADE_NORMAL;
        $this->ativo = true;
        $this->colaboradorIds = [];
        $this->enviarParaTodos = false;
        $this->resetErrorBag();
    }

    public function render()
    {
        $query = AlertaAdm::query()
            ->withCount(['destinatarios', 'destinatarios as visualizados_count' => function ($q) {
                $q->whereNotNull('visualizado_em');
            }])
            ->when($this->search !== '', fn (Builder $q) => $q->buscar($this->search))
            ->when($this->filterPrioridade !== '', fn (Builder $q) => $q->where('prioridade', $this->filterPrioridade))
            ->when($this->filterStatus === 'ativos', fn (Builder $q) => $q->where('ativo', true))
            ->when($this->filterStatus === 'inativos', fn (Builder $q) => $q->where('ativo', false))
            ->orderByRaw("CASE prioridade
                WHEN 'critica' THEN 1
                WHEN 'alta' THEN 2
                WHEN 'normal' THEN 3
                ELSE 4 END")
            ->orderByDesc('created_at');

        // Filtra colaboradores no modal pela busca
        $colaboradoresQuery = Colaborador::query()->ativos();
        if ($this->searchColaborador !== '') {
            $colaboradoresQuery->buscar($this->searchColaborador);
        }

        return view('livewire.alertas.gerenciar', [
            'alertas' => $query->paginate(15),
            'colaboradores' => $colaboradoresQuery->orderBy('nome')->limit(100)->get(['id', 'nome']),
            'totalColaboradoresAtivos' => Colaborador::ativos()->count(),
            'prioridades' => AlertaAdm::prioridadesComLabel(),
        ]);
    }
}
