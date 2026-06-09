<?php

declare(strict_types=1);

namespace App\Livewire\Atestados;

use App\Models\Atestado;
use App\Models\Colaborador;
use App\Services\DocumentoUploadService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Gestão de Atestados Médicos.
 *
 * Workflow:
 *   pendente -> aprovado | rejeitado
 *
 * Upload de documento é OBRIGATÓRIO (a evidência do atestado).
 *
 * Quem aprova/rejeita precisa de permissão específica.
 */
#[Layout('layouts.app')]
#[Title('Atestados')]
class Gerenciar extends Component
{
    use WithFileUploads;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $filterStatus = 'pendente';

    #[Url(as: 'col')]
    public ?int $filterColaboradorId = null;

    // Modal cadastrar
    public bool $showModal = false;
    public bool $editando = false;
    public ?int $editingId = null;

    // Form
    public ?int $colaborador_id = null;
    public string $tipo = 'medico';
    public ?string $data_inicio = null;
    public ?string $data_fim = null;
    public string $cid = '';
    public string $medico_nome = '';
    public string $medico_crm = '';
    public string $medico_crm_uf = '';
    public string $observacoes = '';

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $arquivo = null;

    // Modal aprovação/rejeição
    public bool $showApprovalModal = false;
    public ?int $approvalAtestadoId = null;
    public string $approvalAction = ''; // 'aprovar' | 'rejeitar'
    public string $motivoRejeicao = '';

    // Exclusão
    public bool $showDeleteModal = false;
    public ?int $deletingId = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Atestado::class);
        $this->data_inicio = now()->toDateString();
        $this->data_fim = now()->toDateString();
    }

    public function updating(string $name): void
    {
        if (in_array($name, ['search', 'filterStatus', 'filterColaboradorId'])) {
            $this->resetPage();
        }
    }

    public function openCreate(): void
    {
        $this->authorize('create', Atestado::class);
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $atestado = Atestado::findOrFail($id);
        $this->authorize('update', $atestado);

        $this->editingId = $atestado->id;
        $this->colaborador_id = $atestado->colaborador_id;
        $this->tipo = $atestado->tipo;
        $this->data_inicio = $atestado->data_inicio?->toDateString();
        $this->data_fim = $atestado->data_fim?->toDateString();
        $this->cid = (string) $atestado->cid;
        $this->medico_nome = (string) $atestado->medico_nome;
        $this->medico_crm = (string) $atestado->medico_crm;
        $this->medico_crm_uf = (string) $atestado->medico_crm_uf;
        $this->observacoes = (string) $atestado->observacoes;
        $this->editando = true;
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function save(DocumentoUploadService $upload): void
    {
        // Para criação, arquivo é obrigatório. Para edição, só se quiser trocar.
        $arquivoRules = $this->editando
            ? ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'mimetypes:application/pdf,image/jpeg,image/png', 'max:10240']
            : ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'mimetypes:application/pdf,image/jpeg,image/png', 'max:10240'];

        $data = $this->validate([
            'colaborador_id' => ['required', 'integer', 'exists:colaboradores,id'],
            'tipo' => ['required', 'in:medico,odontologico,acompanhante,declaracao_comparecimento'],
            'data_inicio' => ['required', 'date'],
            'data_fim' => ['required', 'date', 'after_or_equal:data_inicio'],
            'cid' => ['nullable', 'string', 'max:10'],
            'medico_nome' => ['nullable', 'string', 'max:150'],
            'medico_crm' => ['nullable', 'string', 'max:20'],
            'medico_crm_uf' => ['nullable', 'string', 'size:2'],
            'observacoes' => ['nullable', 'string', 'max:1000'],
            'arquivo' => $arquivoRules,
        ], messages: [
            'arquivo.required' => 'O atestado físico é obrigatório.',
            'data_fim.after_or_equal' => 'A data final deve ser igual ou posterior ao início.',
        ], attributes: [
            'colaborador_id' => 'colaborador',
            'arquivo' => 'atestado físico',
        ]);

        try {
            DB::transaction(function () use ($data, $upload): void {
                $diasAfastamento = \Carbon\Carbon::parse($data['data_inicio'])
                    ->diffInDays(\Carbon\Carbon::parse($data['data_fim'])) + 1;

                $payload = [
                    'colaborador_id' => $data['colaborador_id'],
                    'tipo' => $data['tipo'],
                    'data_inicio' => $data['data_inicio'],
                    'data_fim' => $data['data_fim'],
                    'dias_afastamento' => $diasAfastamento,
                    'cid' => $data['cid'] ?: null,
                    'medico_nome' => $data['medico_nome'] ?: null,
                    'medico_crm' => $data['medico_crm'] ?: null,
                    'medico_crm_uf' => $data['medico_crm_uf'] ?: null,
                    'observacoes' => $data['observacoes'] ?: null,
                    'updated_by' => Auth::id(),
                ];

                if ($this->arquivo) {
                    $meta = $upload->armazenar($this->arquivo, 'atestados');
                    $payload = array_merge($payload, $meta);
                }

                if ($this->editando) {
                    $atestado = Atestado::findOrFail($this->editingId);
                    $this->authorize('update', $atestado);
                    $atestado->update($payload);
                } else {
                    $payload['status'] = Atestado::STATUS_PENDENTE;
                    $payload['created_by'] = Auth::id();
                    Atestado::create($payload);
                }
            });

            $this->dispatch('toast', type: 'success', message: 'Atestado ' . ($this->editando ? 'atualizado' : 'registrado') . '.');
            $this->closeModal();
        } catch (\Throwable $e) {
            \Log::error('Erro ao salvar atestado', ['erro' => $e->getMessage()]);
            $this->dispatch('toast', type: 'error', message: 'Erro ao salvar. Tente novamente.');
        }
    }

    // ============================================================
    // Workflow de aprovação
    // ============================================================

    public function abrirAprovacao(int $id, string $action): void
    {
        $atestado = Atestado::findOrFail($id);

        if ($action === 'aprovar') {
            $this->authorize('approve', $atestado);
        } else {
            $this->authorize('reject', $atestado);
        }

        $this->approvalAtestadoId = $id;
        $this->approvalAction = $action;
        $this->motivoRejeicao = '';
        $this->showApprovalModal = true;
    }

    public function confirmarAprovacao(): void
    {
        $atestado = Atestado::findOrFail($this->approvalAtestadoId);

        if ($this->approvalAction === 'aprovar') {
            $this->authorize('approve', $atestado);
            $atestado->update([
                'status' => Atestado::STATUS_APROVADO,
                'aprovado_por_id' => Auth::id(),
                'data_aprovacao' => now(),
                'motivo_rejeicao' => null,
                'updated_by' => Auth::id(),
            ]);
            $this->dispatch('toast', type: 'success', message: 'Atestado aprovado.');
        } else {
            $this->authorize('reject', $atestado);

            if (trim($this->motivoRejeicao) === '') {
                $this->addError('motivoRejeicao', 'Informe o motivo da rejeição.');
                return;
            }

            $atestado->update([
                'status' => Atestado::STATUS_REJEITADO,
                'aprovado_por_id' => Auth::id(),
                'data_aprovacao' => now(),
                'motivo_rejeicao' => $this->motivoRejeicao,
                'updated_by' => Auth::id(),
            ]);
            $this->dispatch('toast', type: 'warning', message: 'Atestado rejeitado.');
        }

        $this->showApprovalModal = false;
        $this->approvalAtestadoId = null;
    }

    public function confirmDelete(int $id): void
    {
        $atestado = Atestado::findOrFail($id);
        $this->authorize('delete', $atestado);
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $atestado = Atestado::findOrFail($this->deletingId);
        $this->authorize('delete', $atestado);
        $atestado->update(['updated_by' => Auth::id()]);
        $atestado->delete();
        $this->dispatch('toast', type: 'success', message: 'Atestado excluído.');
        $this->showDeleteModal = false;
    }

    private function resetForm(): void
    {
        $this->reset([
            'colaborador_id', 'cid', 'medico_nome', 'medico_crm', 'medico_crm_uf',
            'observacoes', 'arquivo', 'editingId', 'editando',
        ]);
        $this->tipo = 'medico';
        $this->data_inicio = now()->toDateString();
        $this->data_fim = now()->toDateString();
        $this->resetErrorBag();
    }

    public function render()
    {
        $query = Atestado::query()
            ->with(['colaborador:id,nome,cpf', 'aprovadoPor:id,name'])
            ->when($this->filterStatus !== '' && $this->filterStatus !== 'todos',
                fn (Builder $q) => $q->where('status', $this->filterStatus))
            ->when($this->filterColaboradorId, fn (Builder $q) => $q->where('colaborador_id', $this->filterColaboradorId))
            ->when($this->search !== '', function (Builder $q): void {
                $q->whereHas('colaborador', function (Builder $sub): void {
                    $sub->buscar($this->search);
                });
            })
            ->orderByRaw("CASE WHEN status = 'pendente' THEN 0 ELSE 1 END")
            ->orderByDesc('data_inicio');

        return view('livewire.atestados.gerenciar', [
            'atestados' => $query->paginate(15),
            'colaboradores' => Colaborador::orderBy('nome')->get(['id', 'nome']),
            'stats' => [
                'pendentes' => Atestado::pendentes()->count(),
                'aprovados_mes' => Atestado::aprovados()
                    ->whereMonth('data_aprovacao', now()->month)
                    ->count(),
            ],
        ]);
    }
}
