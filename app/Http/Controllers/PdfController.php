<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Advertencia;
use App\Models\Colaborador;
use App\Models\PedidoCompra;
use App\Pdf\AdvertenciaPdf;
use App\Pdf\ColaboradorFichaPdf;
use App\Pdf\PedidoCompraPdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Endpoints de geração e download de PDFs.
 *
 * Usa stream() quando ?inline=1 (preview na aba), download() caso contrário.
 */
class PdfController extends Controller
{
    public function advertencia(Request $request, Advertencia $advertencia): Response
    {
        $this->authorize('view', $advertencia);
        $pdf = new AdvertenciaPdf($advertencia);
        return $request->boolean('inline') ? $pdf->stream() : $pdf->download();
    }

    public function pedidoCompra(Request $request, PedidoCompra $pedido): Response
    {
        $this->authorize('view', $pedido);
        $pdf = new PedidoCompraPdf($pedido);
        return $request->boolean('inline') ? $pdf->stream() : $pdf->download();
    }

    public function colaboradorFicha(Request $request, Colaborador $colaborador): Response
    {
        $this->authorize('view', $colaborador);
        $pdf = new ColaboradorFichaPdf($colaborador);
        return $request->boolean('inline') ? $pdf->stream() : $pdf->download();
    }
}
