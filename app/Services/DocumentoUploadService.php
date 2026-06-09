<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Serviço genérico para upload de documentos.
 *
 * Padrão reutilizável para atestados, advertências, documentos do colaborador,
 * compartilhamentos, biblioteca, etc.
 *
 * Características de segurança:
 *   - Storage privado (fora do document root)
 *   - Nome do arquivo = SHA-256 do conteúdo (sem revelar nome original)
 *   - Validação por finfo (mime real) feita antes pelo FormRequest
 *   - Detecta duplicatas automaticamente (mesmo hash = mesmo arquivo, reusa)
 *
 * Retorna metadados que o model deve persistir:
 *   - arquivo_path
 *   - arquivo_nome_original (para exibir/download)
 *   - arquivo_mime
 *   - arquivo_tamanho_bytes
 *   - arquivo_hash (para detectar duplicatas no futuro)
 */
class DocumentoUploadService
{
    /**
     * Armazena um arquivo enviado e retorna seus metadados.
     *
     * @param UploadedFile $arquivo  Arquivo já validado (pelo FormRequest)
     * @param string $pasta          Subpasta dentro do storage privado, ex: "atestados", "advertencias"
     * @return array{
     *     arquivo_path: string,
     *     arquivo_nome_original: string,
     *     arquivo_mime: string,
     *     arquivo_tamanho_bytes: int,
     *     arquivo_hash: string
     * }
     */
    public function armazenar(UploadedFile $arquivo, string $pasta): array
    {
        $hash = hash_file('sha256', $arquivo->getRealPath());
        $extensao = $this->extensaoSegura($arquivo);
        $nomeArmazenado = "private/{$pasta}/{$hash}.{$extensao}";

        // Já existe? Reusa (economia + idempotência)
        if (!Storage::disk('local')->exists($nomeArmazenado)) {
            Storage::disk('local')->putFileAs(
                "private/{$pasta}",
                $arquivo,
                "{$hash}.{$extensao}"
            );
        }

        return [
            'arquivo_path' => $nomeArmazenado,
            'arquivo_nome_original' => $this->sanitizarNome($arquivo->getClientOriginalName()),
            'arquivo_mime' => $arquivo->getMimeType() ?? 'application/octet-stream',
            'arquivo_tamanho_bytes' => $arquivo->getSize(),
            'arquivo_hash' => $hash,
        ];
    }

    /**
     * Remove um arquivo. Antes verifica se nenhum outro registro está usando o mesmo hash.
     *
     * @param string $path Caminho relativo no storage
     * @param callable|null $verificarUso Callback que retorna bool true se ainda há outros usos
     */
    public function remover(string $path, ?callable $verificarUso = null): bool
    {
        // Se foi fornecido um callback de verificação e ainda há outros usos do arquivo,
        // NÃO apaga (compartilhamento por hash)
        if ($verificarUso !== null && $verificarUso()) {
            return false;
        }

        if (Storage::disk('local')->exists($path)) {
            return Storage::disk('local')->delete($path);
        }

        return true;
    }

    /**
     * Determina a extensão a partir do MIME type validado, não da extensão do nome
     * (porque o atacante controla o nome — "shell.php.pdf" tem extensão "pdf" no nome).
     */
    private function extensaoSegura(UploadedFile $arquivo): string
    {
        $mime = $arquivo->getMimeType();

        return match ($mime) {
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            default => 'bin',
        };
    }

    /**
     * Sanitiza nome para uso no download header.
     *
     * Defesa contra header injection no Content-Disposition:
     *   - Remove caracteres de controle (\x00-\x1F, \x7F)
     *   - Remove caracteres de path traversal (/ \)
     *   - Remove aspas, ponto-e-vírgula, sinais de < > | * ? que podem quebrar headers ou nomes de arquivo
     *   - Colapsa espaços múltiplos
     *   - Limita a 200 caracteres
     *
     * Se o resultado ficar vazio (ex: nome só com caracteres ilegais), retorna 'arquivo'.
     */
    private function sanitizarNome(string $nome): string
    {
        // 1. Remove caracteres de controle + path traversal + caracteres de header/filesystem perigosos
        $nome = preg_replace('/[\x00-\x1F\x7F\/\\\\"\';<>|*?]/', '_', $nome) ?? 'arquivo';

        // 2. Colapsa múltiplos underscores e espaços
        $nome = preg_replace('/[_\s]+/', '_', $nome) ?? 'arquivo';

        // 3. Remove pontos iniciais (esconde extensão como .htaccess)
        $nome = ltrim($nome, '.');

        // 4. Se ficou vazio depois da sanitização, fallback
        if (trim($nome, '_') === '') {
            $nome = 'arquivo';
        }

        // 5. Limita tamanho
        if (mb_strlen($nome) > 200) {
            // Preserva extensão se possível
            $extensao = pathinfo($nome, PATHINFO_EXTENSION);
            $base = pathinfo($nome, PATHINFO_FILENAME);
            $base = mb_substr($base, 0, 200 - mb_strlen($extensao) - 1);
            $nome = $extensao ? "{$base}.{$extensao}" : $base;
        }

        return $nome;
    }
}
