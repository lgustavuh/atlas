<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BibliotecaDocumento;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Serve documentos da Biblioteca de Documentos Padronizados.
 *
 * Conta downloads, valida Policy. Arquivo no disco usa hash SHA-256 como nome,
 * mas serve usando o nome original para a UX do usuário.
 */
class BibliotecaController extends Controller
{
    /**
     * Faz o download (força save) e incrementa o contador.
     */
    public function download(int $documentoId): BinaryFileResponse|Response
    {
        $doc = BibliotecaDocumento::findOrFail($documentoId);

        // Policy: tem permissão de download?
        if (!Auth::user()?->can('download', $doc)) {
            abort(403);
        }

        if (!Storage::disk('local')->exists($doc->arquivo_path)) {
            abort(404, 'Arquivo não encontrado no servidor.');
        }

        // Conta o download (incrementa atomicamente, sem disparar timestamps/eventos)
        $doc->increment('downloads_count');

        return response()->download(
            Storage::disk('local')->path($doc->arquivo_path),
            $doc->arquivo_nome_original,
            ['Content-Type' => $doc->arquivo_mime],
        );
    }

    /**
     * Visualiza inline no browser (PDFs, imagens).
     */
    public function visualizar(int $documentoId): BinaryFileResponse|Response
    {
        $doc = BibliotecaDocumento::findOrFail($documentoId);

        if (!Auth::user()?->can('view', $doc)) {
            abort(403);
        }

        if (!Storage::disk('local')->exists($doc->arquivo_path)) {
            abort(404, 'Arquivo não encontrado no servidor.');
        }

        return response()->file(
            Storage::disk('local')->path($doc->arquivo_path),
            ['Content-Type' => $doc->arquivo_mime],
        );
    }
}
