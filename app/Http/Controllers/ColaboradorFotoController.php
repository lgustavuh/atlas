<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Colaborador;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Serve a foto do colaborador.
 *
 * Por que um controller em vez de URL pública?
 *   - Arquivos ficam em storage privado (fora do public)
 *   - Verifica autorização antes de servir
 *   - Permite log de quem acessou (se necessário no futuro)
 *   - Permite gerar URLs assinadas com expiração depois
 */
class ColaboradorFotoController extends Controller
{
    public function __invoke(int $colaboradorId): Response|BinaryFileResponse
    {
        $colaborador = Colaborador::withTrashed()->findOrFail($colaboradorId);

        $this->authorize('view', $colaborador);

        if (!$colaborador->foto_path || !Storage::disk('local')->exists($colaborador->foto_path)) {
            abort(404);
        }

        $path = Storage::disk('local')->path($colaborador->foto_path);

        return response()->file($path, [
            'Content-Type' => 'image/jpeg',
            // Cache 1 hora no browser, mas precisa revalidar (foto pode mudar)
            'Cache-Control' => 'private, max-age=3600, must-revalidate',
        ]);
    }
}
