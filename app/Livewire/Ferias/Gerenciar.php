<?php

declare(strict_types=1);

namespace App\Livewire\Ferias;

use App\Models\Colaborador;
use App\Models\Ferias;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Gestão de Férias.
 *
 * Workflow: programada → aprovada → em_gozo → concluida
 *           (ou cancelada em qualquer ponto)
 *
 * Regras CLT aplicadas:
 *   - Mínimo 5 dias por período de gozo
 *   - Máximo 30 dias por período aquisitivo
 *   - Abono pecuniário: até 1/3 do total (máx 10 dias)
 */
#[Layout('layouts.app')]
#[Title('Férias')]
class Gerenciar extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $filterStatus = '';

    // Modal
    public bool $showModal = false;
    public bool $editando = false;
    public ?int $editingId = null;

    // Form
    public ?int $colaborador_id = null;
    public ?string $periodo_aquisitivo_inicio = null;
    public ?string $periodo_aquisitivo_fim = null;
    public ?string $data_inicio_gozo = null;
    public ?string $data_fim_gozo = null;
    public ?int $dias_gozo = 30;
    public bool $abono_pecuniario = false;
    public ?int $dias_abono = 0;
    public bool $adiantar_13_salario = false;
    public string $observacoes = '';

    // Aprovação/rejeição
    public bool $showApprovalModal = false;
    public ?int $approvalId = null;
    public string $approvalAction = '';
    public string $approvalObs = '';

    // Exclusão
    public bool $showDeleteModal = false;
    public ?int $deletingId = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Ferias::class);
    }

    public function updating(string $name): void
    {
        if (in_array($name, ['search', 'filterStatus'])) {
            $this->resetPage();
        }
    }

    public function openCreate(): void
    {
        $this->authorize('create', Ferias::class);
        $this->resetForm();
        $this->showModal = true;
    }

    /**
     * Quando seleciona um colaborador, sugere período aquisitivo
     * baseado na data de admissão.
     */
    public function updatedColaboradorId($value): void
    {
        if (!$value) {
            return;
        }

        $colaborador = Colaborador::find($value);
        if (!$colaborador?->data_admissao) {
            return;
        }

        // Período aquisitivo: 12 meses contados a partir da admissão
        // ou do próximo período aquisitivo após o último já concluído
        $ultimaFerias = Ferias::where('colaborador_id', $value)
            ->where('status', '!=', Ferias::STATUS_CANCELADA)
            ->orderByDesc('periodo_aquisitivo_fim')
            ->first();

        $inicio = $ultimaFerias
            ? Carbon::parse($ultimaFerias->periodo_aquisitivo_fim)->addDay()
            : Carbon::parse($colaborador->data_admissao);

        $this->periodo_aquisitivo_inicio = $inicio->toDateString();
        $this->periodo_aquisitivo_fim = $inicio->copy()->addYear()->subDay()->toDateString();
    }

    /**
     * Quando marca/desmarca abono pecuniário, sugere valores padrão APENAS se
     * os campos estiverem em seus defaults. Não sobrescreve valores que o usuário
     * já tenha digitado.
     */
    public function updatedAbonoPecuniario(bool $value): void
    {
        if ($value) {
            // Só preenche se ainda está no default (30 dias gozo, 0 abono)
            if ($this->dias_gozo === 30 && (int) $this->dias_abono === 0) {
                $this->dias_abono = 10;
                $this->dias_gozo = 20;
            }
        } else {
            // Desmarcou: zera o abono. Não mexe no dias_gozo (pode ser que o usuário queira manter)
            $this->dias_abono = 0;
        }

        $this->recalcularDataFim();
    }

    public function updatedDiasGozo(): void
    {
        $this->recalcularDataFim();
    }

    public function updatedDataInicioGozo(): void
    {
        $this->recalcularDataFim();
    }

    private function recalcularDataFim(): void
    {
        if ($this->data_inicio_gozo && $this->dias_gozo) {
            $this->data_fim_gozo = Carbon::parse($this->data_inicio_gozo)
                ->addDays($this->dias_gozo - 1)
                ->toDateString();
        }
    }

    public function openEdit(int $id): void
    {
        $ferias = Ferias::findOrFail($id);
        $this->authorize('update', $ferias);

        $this->editingId = $ferias->id;
        $this->colaborador_id = $ferias->colaborador_id;
        $this->periodo_aquisitivo_inicio = $ferias->periodo_aquisitivo_inicio?->toDateString();
        $this->periodo_aquisitivo_fim = $ferias->periodo_aquisitivo_fim?->toDateString();
        $this->data_inicio_gozo = $ferias->data_inicio_gozo?->toDateString();
        $this->data_fim_gozo = $ferias->data_fim_gozo?->toDateString();
        $this->dias_gozo = $ferias->dias_gozo;
        $this->abono_pecuniario = $ferias->abono_pecuniario;
        $this->dias_abono = $ferias->dias_abono;
        $this->adiantar_13_salario = $ferias->adiantar_13_salario;
        $this->observacoes = (string) $ferias->observacoes;
        $this->editando = true;
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function save(): void
    {
        $data = $this->validate([
            'colaborador_id' => ['required', 'integer', 'exists:colaboradores,id'],
            'periodo_aquisitivo_inicio' => ['required', 'date'],
            'periodo_aquisitivo_fim' => ['required', 'date', 'after:periodo_aquisitivo_inicio'],
            'data_inicio_gozo' => ['required', 'date'],
            'data_fim_gozo' => ['required', 'date', 'after_or_equal:data_inicio_gozo'],
            'dias_gozo' => ['required', 'integer', 'min:5', 'max:30'],
            'abono_pecuniario' => ['boolean'],
            'dias_abono' => ['required_if:abono_pecuniario,true', 'integer', 'min:0', 'max:10'],
            'adiantar_13_salario' => ['boolean'],
            'observacoes' => ['nullable', 'string', 'max:1000'],
        ], messages: [
            'dias_gozo.min' => 'Mínimo 5 dias de gozo (regra CLT).',
            'dias_gozo.max' => 'Máximo 30 dias de gozo por período aquisitivo.',
            'dias_abono.max' => 'Abono pecuniário máximo: 10 dias (1/3 das férias).',
            'periodo_aquisitivo_fim.after' => 'Fim do período aquisitivo deve ser após o início.',
            'data_fim_gozo.after_or_equal' => 'Fim do gozo deve ser igual ou posterior ao início.',
        ], attributes: [
            'colaborador_id' => 'colaborador',
            'periodo_aquisitivo_inicio' => 'início do período aquisitivo',
            'periodo_aquisitivo_fim' => 'fim do período aquisitivo',
            'data_inicio_gozo' => 'data de início do gozo',
            'data_fim_gozo' => 'data de fim do gozo',
        ]);

        // Validação CLT: total não pode exceder 30 dias
        $total = ($data['dias_gozo'] ?? 0) + ($data['dias_abono'] ?? 0);
        if ($total > 30) {
            throw ValidationException::withMessages([
                'dias_gozo' => "Total de dias (gozo + abono) excede 30. Atual: {$total}.",
            ]);
        }

        $payload = array_merge($data, [
            'updated_by' => Auth::id(),
        ]);

        if ($this->editando) {
            $ferias = Ferias::findOrFail($this->editingId);
            $this->authorize('update', $ferias);
            $ferias->update($payload);
            $this->dispatch('toast', type: 'success', message: 'Férias atualizadas.');
        } else {
            $payload['status'] = Ferias::STATUS_PROGRAMADA;
            $payload['created_by'] = Auth::id();
            Ferias::create($payload);
            $this->dispatch('toast', type: 'success', message: 'Férias programadas com sucesso.');
        }

        $this->closeModal();
    }

    // ============================================================
    // Workflow de aprovação
    // ============================================================

    public function abrirAprovacao(int $id, string $action): void
    {
        $ferias = Ferias::findOrFail($id);

        if ($action === 'aprovar') {
            $this->authorize('approve', $ferias);
        } else {
            $this->authorize('reject', $ferias);
        }

        $this->approvalId = $id;
        $this->approvalAction = $action;
        $this->approvalObs = '';
        $this->showApprovalModal = true;
    }

    public function confirmarAprovacao(): void
    {
        $ferias = Ferias::findOrFail($this->approvalId);

        if ($this->approvalAction === 'aprovar') {
            $this->authorize('approve', $ferias);
            $ferias->update([
                'status' => Ferias::STATUS_APROVADA,
                'aprovado_por_id' => Auth::id(),
                'data_aprovacao' => now(),
                'observacoes' => trim(($ferias->observacoes ?? '') . "\n[Aprovado] " . $this->approvalObs),
                'updated_by' => Auth::id(),
            ]);
            $this->dispatch('toast', type: 'success', message: 'Férias aprovadas.');
        } else {
            $this->authorize('reject', $ferias);
            if (trim($this->approvalObs) === '') {
                $this->addError('approvalObs', 'Informe o motivo da rejeição.');
                return;
            }
            $ferias->update([
                'status' => Ferias::STATUS_CANCELADA,
                'observacoes' => trim(($ferias->observacoes ?? '') . "\n[Rejeitado] " . $this->approvalObs),
                'updated_by' => Auth::id(),
            ]);
            $this->dispatch('toast', type: 'warning', message: 'Solicitação de férias rejeitada.');
        }

        $this->showApprovalModal = false;
    }

    /**
     * Marca como em gozo (quando colaborador realmente sai).
     */
    public function iniciarGozo(int $id): void
    {
        $ferias = Ferias::findOrFail($id);
        $this->authorize('update', $ferias);

        if ($ferias->status !== Ferias::STATUS_APROVADA) {
            $this->dispatch('toast', type: 'error', message: 'Apenas férias aprovadas podem iniciar o gozo.');
            return;
        }

        $ferias->update([
            'status' => Ferias::STATUS_EM_GOZO,
            'updated_by' => Auth::id(),
        ]);
        $this->dispatch('toast', type: 'success', message: 'Gozo de férias iniciado.');
    }

    /**
     * Marca como concluída (quando colaborador retorna).
     */
    public function concluir(int $id): void
    {
        $ferias = Ferias::findOrFail($id);
        $this->authorize('update', $ferias);

        if ($ferias->status !== Ferias::STATUS_EM_GOZO) {
            $this->dispatch('toast', type: 'error', message: 'Só é possível concluir férias em gozo.');
            return;
        }

        $ferias->update([
            'status' => Ferias::STATUS_CONCLUIDA,
            'updated_by' => Auth::id(),
        ]);
        $this->dispatch('toast', type: 'success', message: 'Férias concluídas.');
    }

    public function confirmDelete(int $id): void
    {
        $ferias = Ferias::findOrFail($id);
        $this->authorize('delete', $ferias);
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $ferias = Ferias::findOrFail($this->deletingId);
        $this->authorize('delete', $ferias);
        $ferias->update(['updated_by' => Auth::id()]);
        $ferias->delete();
        $this->dispatch('toast', type: 'success', message: 'Registro de férias excluído.');
        $this->showDeleteModal = false;
    }

    private function resetForm(): void
    {
        $this->reset([
            'colaborador_id', 'periodo_aquisitivo_inicio', 'periodo_aquisitivo_fim',
            'data_inicio_gozo', 'data_fim_gozo', 'observacoes',
            'editingId', 'editando',
        ]);
        $this->dias_gozo = 30;
        $this->abono_pecuniario = false;
        $this->dias_abono = 0;
        $this->adiantar_13_salario = false;
        $this->resetErrorBag();
    }

    public function render()
    {
        $query = Ferias::query()
            ->with(['colaborador:id,nome,cpf', 'aprovadoPor:id,name'])
            ->when($this->filterStatus !== '', fn (Builder $q) => $q->where('status', $this->filterStatus))
            ->when($this->search !== '', function (Builder $q): void {
                $q->whereHas('colaborador', fn (Builder $sub) => $sub->buscar($this->search));
            })
            ->orderByRaw("CASE
                WHEN status = 'programada' THEN 1
                WHEN status = 'em_gozo' THEN 2
                WHEN status = 'aprovada' THEN 3
                ELSE 4 END")
            ->orderByDesc('data_inicio_gozo');

        return view('livewire.ferias.gerenciar', [
            'ferias' => $query->paginate(15),
            'colaboradores' => Colaborador::orderBy('nome')->get(['id', 'nome', 'data_admissao']),
            'stats' => [
                'aguardando' => Ferias::aguardandoAprovacao()->count(),
                'em_gozo' => Ferias::emGozo()->count(),
                'proximas' => Ferias::proximas()->count(),
            ],
        ]);
    }
}
