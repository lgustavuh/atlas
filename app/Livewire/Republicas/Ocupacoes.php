<?php

declare(strict_types=1);

namespace App\Livewire\Republicas;

use App\Models\Colaborador;
use App\Models\Republica;
use App\Models\RepublicaOcupacao;
use App\Services\RepublicaService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Ocupação de República')]
class Ocupacoes extends Component
{
    public int $republicaId;
    public Republica $republica;

    // Form de alocação
    public bool $showAlocarModal = false;
    public ?int $colaborador_id = null;
    public ?string $data_entrada = null;
    public string $observacoes = '';

    // Form de saída
    public bool $showSaidaModal = false;
    public ?int $saindoOcupacaoId = null;
    public string $saindoNome = '';
    public ?string $data_saida = null;

    public function mount(int $id): void
    {
        $this->republicaId = $id;
        $this->republica = Republica::with('cidade')->findOrFail($id);
        $this->authorize('view', $this->republica);
    }

    public function openAlocar(): void
    {
        $this->authorize('update', $this->republica);
        $this->reset(['colaborador_id', 'data_entrada', 'observacoes']);
        $this->data_entrada = now()->toDateString();
        $this->resetErrorBag();
        $this->showAlocarModal = true;
    }

    public function closeAlocar(): void
    {
        $this->showAlocarModal = false;
        $this->reset(['colaborador_id', 'data_entrada', 'observacoes']);
    }

    public function alocar(RepublicaService $service): void
    {
        $data = $this->validate([
            'colaborador_id' => ['required', 'integer', 'exists:colaboradores,id'],
            'data_entrada' => ['required', 'date'],
            'observacoes' => ['nullable', 'string', 'max:1000'],
        ], attributes: [
            'colaborador_id' => 'colaborador',
            'data_entrada' => 'data de entrada',
        ]);

        try {
            $service->alocar(
                $this->republica,
                $data['colaborador_id'],
                $data['data_entrada'],
                $data['observacoes'] ?? null,
            );
            // Recarrega a república para atualizar contadores
            $this->republica = Republica::with('cidade')->findOrFail($this->republicaId);
            $this->dispatch('toast', type: 'success', message: 'Colaborador alocado.');
            $this->closeAlocar();
        } catch (\Throwable $e) {
            if (app()->environment('testing', 'local')) {
                throw $e;
            }
            \Log::error('Erro ao alocar', ['erro' => $e->getMessage()]);
        }
    }

    public function openSaida(int $ocupacaoId): void
    {
        $ocup = RepublicaOcupacao::with('colaborador:id,nome')->findOrFail($ocupacaoId);
        $this->authorize('update', $this->republica);

        $this->saindoOcupacaoId = $ocupacaoId;
        $this->saindoNome = $ocup->colaborador?->nome ?? '';
        $this->data_saida = now()->toDateString();
        $this->resetErrorBag();
        $this->showSaidaModal = true;
    }

    public function closeSaida(): void
    {
        $this->showSaidaModal = false;
        $this->reset(['saindoOcupacaoId', 'saindoNome', 'data_saida']);
    }

    public function darSaida(RepublicaService $service): void
    {
        $this->validate([
            'data_saida' => ['required', 'date'],
        ], attributes: ['data_saida' => 'data de saída']);

        try {
            $ocup = RepublicaOcupacao::findOrFail($this->saindoOcupacaoId);
            $service->darSaida($ocup, $this->data_saida);

            $this->republica = Republica::with('cidade')->findOrFail($this->republicaId);
            $this->dispatch('toast', type: 'success', message: "Saída de {$this->saindoNome} registrada.");
            $this->closeSaida();
        } catch (\Throwable $e) {
            if (app()->environment('testing', 'local')) {
                throw $e;
            }
            \Log::error('Erro ao dar saída', ['erro' => $e->getMessage()]);
        }
    }

    public function render()
    {
        $ocupacoesAtuais = RepublicaOcupacao::query()
            ->with('colaborador:id,nome,cargo_id', 'colaborador.cargo:id,nome')
            ->where('republica_id', $this->republicaId)
            ->atuais()
            ->orderBy('data_entrada')
            ->get();

        $historico = RepublicaOcupacao::query()
            ->with('colaborador:id,nome')
            ->where('republica_id', $this->republicaId)
            ->historicas()
            ->orderByDesc('data_saida')
            ->limit(20)
            ->get();

        // Colaboradores disponíveis: ativos que NÃO estão em outra república atualmente
        $colaboradoresDisponiveis = Colaborador::query()
            ->ativos()
            ->whereDoesntHave('ocupacoesRepublica', function ($q): void {
                $q->where(function ($sub): void {
                    $sub->whereNull('data_saida')->orWhereDate('data_saida', '>', now()->toDateString());
                });
            })
            ->orderBy('nome')
            ->get(['id', 'nome']);

        // Recarrega república com contagem fresca
        $republicaAtualizada = Republica::withCount('ocupacoesAtuais')->find($this->republicaId);

        return view('livewire.republicas.ocupacoes', [
            'republica' => $republicaAtualizada,
            'ocupacoesAtuais' => $ocupacoesAtuais,
            'historico' => $historico,
            'colaboradoresDisponiveis' => $colaboradoresDisponiveis,
        ]);
    }
}
