<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Advertencia;
use App\Models\Atestado;
use App\Models\Veiculo;
use App\Models\VeiculoManutencao;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Servir documentos privados (atestados, advertências, documentos de veículo,
 * comprovantes de manutenção).
 *
 * Cada método verifica a Policy do recurso antes de servir.
 *
 * Importante: o nome do arquivo no disco é o HASH (SHA-256). Quando servimos,
 * usamos o nome ORIGINAL para o usuário ver "atestado_dr_silva.pdf" ao baixar,
 * não "a3f5b...c9.pdf".
 */
class DocumentoController extends Controller
{
    /**
     * Servir arquivo de atestado.
     *
     * @param string $modo 'view' (inline no browser) ou 'download' (força save)
     */
    public function atestado(int $atestadoId, string $modo = 'view'): Response|BinaryFileResponse
    {
        $atestado = Atestado::withTrashed()->findOrFail($atestadoId);
        $this->authorize('view', $atestado);

        return $this->servirArquivo(
            $atestado->arquivo_path,
            $atestado->arquivo_nome_original,
            $atestado->arquivo_mime,
            $modo === 'download'
        );
    }

    /**
     * Servir documento de advertência.
     */
    public function advertencia(int $advertenciaId, string $modo = 'view'): Response|BinaryFileResponse
    {
        $advertencia = Advertencia::withTrashed()->findOrFail($advertenciaId);
        $this->authorize('view', $advertencia);

        if (!$advertencia->documento_path) {
            abort(404);
        }

        return $this->servirArquivo(
            $advertencia->documento_path,
            "advertencia_{$advertenciaId}.pdf",
            'application/pdf',
            $modo === 'download'
        );
    }

    /**
     * Servir documento (CRLV) de veiculo.
     */
    public function veiculo(int $veiculoId, string $modo = 'view'): Response|BinaryFileResponse
    {
        $veiculo = Veiculo::withTrashed()->findOrFail($veiculoId);
        $this->authorize('view', $veiculo);

        if (! $veiculo->documento_path) {
            abort(404);
        }

        return $this->servirArquivo(
            $veiculo->documento_path,
            $veiculo->documento_nome_original ?: "documento_veiculo_{$veiculoId}.pdf",
            'application/pdf',
            $modo === 'download'
        );
    }

    /**
     * Servir comprovante de manutencao (item 4 do v1.9).
     */
    public function comprovanteManutencao(int $manutencaoId, string $modo = 'view'): Response|BinaryFileResponse
    {
        $manutencao = VeiculoManutencao::withTrashed()->findOrFail($manutencaoId);
        $this->authorize('view', $manutencao);

        if (! $manutencao->comprovante_path) {
            abort(404);
        }

        return $this->servirArquivo(
            $manutencao->comprovante_path,
            $manutencao->comprovante_nome_original ?: "comprovante_manutencao_{$manutencaoId}.pdf",
            $manutencao->comprovante_mime ?: 'application/pdf',
            $modo === 'download'
        );
    }

    /**
     * Lógica central: serve o arquivo do storage privado com cache controlado.
     */
    private function servirArquivo(
        string $path,
        string $nomeOriginal,
        string $mime,
        bool $download
    ): BinaryFileResponse {
        if (!Storage::disk('local')->exists($path)) {
            abort(404);
        }

        $caminhoCompleto = Storage::disk('local')->path($path);

        $disposition = $download ? 'attachment' : 'inline';

        // Defesa em profundidade: sanitiza nome antes de injetar no header,
        // bloqueando aspas, CRLF e qualquer caractere fora do ASCII imprimível,
        // que poderiam quebrar/duplicar headers.
        $nomeSafe = preg_replace('/[\x00-\x1F\x7F"\'\\\\]/', '_', $nomeOriginal) ?? 'arquivo';
        $nomeSafe = mb_substr($nomeSafe, 0, 200);

        // RFC 5987: ASCII fallback + UTF-8 encoded para nomes com acento
        $asciiFallback = preg_replace('/[^A-Za-z0-9._-]/', '_', $nomeSafe) ?: 'arquivo';
        $utf8Encoded = rawurlencode($nomeSafe);

        $contentDisposition = sprintf(
            '%s; filename="%s"; filename*=UTF-8\'\'%s',
            $disposition,
            $asciiFallback,
            $utf8Encoded,
        );

        return response()->file($caminhoCompleto, [
            'Content-Type' => $mime,
            'Content-Disposition' => $contentDisposition,
            // Documentos sensíveis: cache curto e privado
            'Cache-Control' => 'private, max-age=60, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
