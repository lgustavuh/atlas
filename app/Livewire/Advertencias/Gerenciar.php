<?php

declare(strict_types=1);

namespace App\Livewire\Advertencias;

use App\Models\Advertencia;
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
 * Gestão de Advertências.
 *
 * Permite registrar advertências verbais, escritas ou suspensões.
 * Documento de evidência (PDF assinado) é opcional.
 */
#[Layout('layouts.app')]
#[Title('Advertências')]
class Gerenciar extends Component
{
    use WithFileUploads;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'tipo')]
    public string $filterTipo = '';

    #[Url(as: 'col')]
    public ?int $filterColaboradorId = null;

    // Modal
    public bool $showModal = false;
    public bool $editando = false;
    public ?int $editingId = null;

    // Form
    public ?int $colaborador_id = null;
    public string $tipo = 'escrita';
    public ?string $data_ocorrencia = null;
    public ?string $data_aplicacao = null;
    public string $motivo = '';
    public string $descricao_ocorrencia = '';
    public ?int $dias_suspensao = null;
    public ?int $aplicado_por_id = null;
    public bool $ciente_colaborador = false;
    public string $observacoes = '';

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $documento = null;

    // Exclusão
    public bool $showDeleteModal = false;
    public ?int $deletingId = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Advertencia::class);
        $this->data_ocorrencia = now()->toDateString();
        $this->data_aplicacao = now()->toDateString();
    }

    public function updating(string $name): void
    {
        if (in_array($name, ['search', 'filterTipo', 'filterColaboradorId'])) {
            $this->resetPage();
        }
    }

    public function openCreate(): void
    {
        $this->authorize('create', Advertencia::class);
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $adv = Advertencia::findOrFail($id);
        $this->authorize('update', $adv);

        $this->editingId = $adv->id;
        $this->colaborador_id = $adv->colaborador_id;
        $this->tipo = $adv->tipo;
        $this->data_ocorrencia = $adv->data_ocorrencia?->toDateString();
        $this->data_aplicacao = $adv->data_aplicacao?->toDateString();
        $this->motivo = $adv->motivo;
        $this->descricao_ocorrencia = $adv->descricao_ocorrencia;
        $this->dias_suspensao = $adv->dias_suspensao;
        $this->aplicado_por_id = $adv->aplicado_por_id;
        $this->ciente_colaborador = $adv->ciente_colaborador;
        $this->observacoes = (string) $adv->observacoes;
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
        $data = $this->validate([
            'colaborador_id' => ['required', 'integer', 'exists:colaboradores,id'],
            'tipo' => ['required', 'in:verbal,escrita,suspensao'],
            'data_ocorrencia' => ['required', 'date', 'before_or_equal:today'],
            'data_aplicacao' => ['required', 'date', 'after_or_equal:data_ocorrencia', 'before_or_equal:today'],
            'motivo' => ['required', 'string', 'min:5', 'max:500'],
            'descricao_ocorrencia' => ['required', 'string', 'min:10', 'max:2000'],
            'dias_suspensao' => ['nullable', 'integer', 'min:1', 'max:30', 'required_if:tipo,suspensao'],
            'aplicado_por_id' => ['nullable', 'integer', 'exists:colaboradores,id'],
            'ciente_colaborador' => ['boolean'],
            'observacoes' => ['nullable', 'string', 'max:1000'],
            'documento' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'mimetypes:application/pdf,image/jpeg,image/png',
                'max:10240', // 10MB
            ],
        ], messages: [
            'dias_suspensao.required_if' => 'Informe os dias de suspensão.',
            'data_aplicacao.after_or_equal' => 'Aplicação deve ser igual ou posterior à ocorrência.',
            'motivo.min' => 'Descreva o motivo com pelo menos 5 caracteres.',
        ], attributes: [
            'colaborador_id' => 'colaborador',
            'aplicado_por_id' => 'aplicador',
            'dias_suspensao' => 'dias de suspensão',
            'descricao_ocorrencia' => 'descrição da ocorrência',
        ]);

        try {
            DB::transaction(function () use ($data, $upload): void {
                $payload = [
                    'colaborador_id' => $data['colaborador_id'],
                    'tipo' => $data['tipo'],
                    'data_ocorrencia' => $data['data_ocorrencia'],
                    'data_aplicacao' => $data['data_aplicacao'],
                    'motivo' => $data['motivo'],
                    'descricao_ocorrencia' => $data['descricao_ocorrencia'],
                    'dias_suspensao' => $data['tipo'] === 'suspensao' ? $data['dias_suspensao'] : null,
                    'aplicado_por_id' => $data['aplicado_por_id'] ?: null,
                    'ciente_colaborador' => $data['ciente_colaborador'] ?? false,
                    'data_ciencia' => ($data['ciente_colaborador'] ?? false) ? now() : null,
                    'observacoes' => $data['observacoes'] ?: null,
                    'updated_by' => Auth::id(),
                ];

                // Upload do documento (opcional)
                if ($this->documento) {
                    $meta = $upload->armazenar($this->documento, 'advertencias');
                    $payload['documento_path'] = $meta['arquivo_path'];
                }

                if ($this->editando) {
                    $adv = Advertencia::findOrFail($this->editingId);
                    $this->authorize('update', $adv);
                    $adv->update($payload);
                } else {
                    $payload['created_by'] = Auth::id();
                    Advertencia::create($payload);
                }
            });

            $this->dispatch('toast', type: 'success', message: 'Advertência ' . ($this->editando ? 'atualizada' : 'registrada') . '.');
            $this->closeModal();
        } catch (\Throwable $e) {
            \Log::error('Erro ao salvar advertência', ['erro' => $e->getMessage()]);
            $this->dispatch('toast', type: 'error', message: 'Erro ao salvar. Tente novamente.');
        }
    }

    public function confirmDelete(int $id): void
    {
        $adv = Advertencia::findOrFail($id);
        $this->authorize('delete', $adv);
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $adv = Advertencia::findOrFail($this->deletingId);
        $this->authorize('delete', $adv);
        $adv->update(['updated_by' => Auth::id()]);
        $adv->delete();
        $this->dispatch('toast', type: 'success', message: 'Advertência excluída.');
        $this->showDeleteModal = false;
    }

    private function resetForm(): void
    {
        $this->reset([
            'colaborador_id', 'motivo', 'descricao_ocorrencia', 'dias_suspensao',
            'aplicado_por_id', 'observacoes', 'documento', 'editingId', 'editando',
        ]);
        $this->tipo = 'escrita';
        $this->data_ocorrencia = now()->toDateString();
        $this->data_aplicacao = now()->toDateString();
        $this->ciente_colaborador = false;
        $this->resetErrorBag();
    }

    public function render()
    {
        $query = Advertencia::query()
            ->with(['colaborador:id,nome,cpf', 'aplicadoPor:id,nome'])
            ->when($this->filterTipo !== '', fn (Builder $q) => $q->where('tipo', $this->filterTipo))
            ->when($this->filterColaboradorId, fn (Builder $q) => $q->where('colaborador_id', $this->filterColaboradorId))
            ->when($this->search !== '', function (Builder $q): void {
                $q->whereHas('colaborador', function (Builder $sub): void {
                    $sub->buscar($this->search);
                });
            })
            ->orderByDesc('data_aplicacao');

        return view('livewire.advertencias.gerenciar', [
            'advertencias' => $query->paginate(15),
            'colaboradores' => Colaborador::orderBy('nome')->get(['id', 'nome']),
        ]);
    }
}
