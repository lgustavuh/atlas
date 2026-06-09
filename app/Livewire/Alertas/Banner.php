<?php

declare(strict_types=1);

namespace App\Livewire\Alertas;

use App\Models\AlertaAdm;
use App\Services\AlertaAdmService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Banner persistente de alertas pendentes para o usuário corrente.
 *
 * Exibido no layout em todas as páginas autenticadas.
 * Mostra alertas vigentes que o colaborador do usuário ainda não visualizou.
 */
class Banner extends Component
{
    /** @var list<int> */
    public array $alertasOcultos = [];

    /**
     * Marca um alerta como visualizado.
     */
    public function descartar(int $alertaId, AlertaAdmService $service): void
    {
        $colaboradorId = Auth::user()?->colaborador_id;
        if (!$colaboradorId) {
            return;
        }
        $service->marcarVisualizado($alertaId, $colaboradorId);
        $this->alertasOcultos[] = $alertaId;
    }

    public function render(): View
    {
        $alertas = collect();

        $colaboradorId = Auth::user()?->colaborador_id;
        if ($colaboradorId) {
            $alertas = AlertaAdm::query()
                ->pendentesPara($colaboradorId)
                ->whereNotIn('id', $this->alertasOcultos)
                ->orderByRaw("CASE prioridade
                    WHEN 'critica' THEN 1
                    WHEN 'alta' THEN 2
                    WHEN 'normal' THEN 3
                    ELSE 4 END")
                ->get();
        }

        return view('livewire.alertas.banner', [
            'alertas' => $alertas,
        ]);
    }
}
