<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Colaborador;
use App\Models\ColaboradorEndereco;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

/**
 * Service do Colaborador.
 *
 * Centraliza operações que envolvem múltiplas tabelas ou efeitos colaterais
 * (upload de foto, exclusão de arquivos, etc).
 *
 * Por que existe?
 *   - O componente Livewire não deve conhecer detalhes de armazenamento
 *   - Lógica testável isoladamente (sem precisar montar o framework HTTP)
 *   - Reuso (se um dia importar via CSV, mesmo service)
 *   - Garantia de transação atômica (ou tudo grava, ou nada grava)
 */
class ColaboradorService
{
    /**
     * Cria um novo colaborador.
     *
     * @param array<string, mixed> $dados
     */
    public function criar(array $dados, ?UploadedFile $foto = null): Colaborador
    {
        return DB::transaction(function () use ($dados, $foto): Colaborador {
            // Separa dados do colaborador dos dados de endereço
            $dadosEndereco = $this->extrairEndereco($dados);

            // Normaliza strings vazias para null (evita violar CHECK constraints em colunas enum nullable)
            $dados = $this->stringVaziaParaNull($dados);

            // Auditoria
            $dados['created_by'] = Auth::id();
            $dados['updated_by'] = Auth::id();

            // Foto: processa antes pra capturar o caminho
            if ($foto) {
                $dados['foto_path'] = $this->processarFoto($foto);
            }
            // 'foto' veio das rules de validação como UploadedFile; não vai pro banco
            unset($dados['foto']);

            $colaborador = Colaborador::create($dados);

            // Cria endereço se houver dados
            if ($this->temDadosEndereco($dadosEndereco)) {
                $this->salvarEndereco($colaborador, $dadosEndereco);
            }

            return $colaborador->fresh(['cargo', 'departamento', 'enderecoResidencial.cidade']);
        });
    }

    /**
     * Atualiza um colaborador existente.
     *
     * @param array<string, mixed> $dados
     */
    public function atualizar(Colaborador $colaborador, array $dados, ?UploadedFile $foto = null): Colaborador
    {
        return DB::transaction(function () use ($colaborador, $dados, $foto): Colaborador {
            $dadosEndereco = $this->extrairEndereco($dados);
            $dados = $this->stringVaziaParaNull($dados);
            $dados['updated_by'] = Auth::id();

            if ($foto) {
                // Apaga foto antiga
                if ($colaborador->foto_path) {
                    Storage::disk('local')->delete($colaborador->foto_path);
                }
                $dados['foto_path'] = $this->processarFoto($foto);
            }
            unset($dados['foto']);

            $colaborador->update($dados);

            if ($this->temDadosEndereco($dadosEndereco)) {
                $this->salvarEndereco($colaborador, $dadosEndereco);
            }

            return $colaborador->fresh(['cargo', 'departamento', 'enderecoResidencial.cidade']);
        });
    }

    /**
     * Desativa um colaborador (soft delete).
     */
    public function desativar(Colaborador $colaborador): void
    {
        DB::transaction(function () use ($colaborador): void {
            $colaborador->update([
                'deleted_by' => Auth::id(),
            ]);
            $colaborador->delete();
        });
    }

    /**
     * Reativa um colaborador desativado.
     */
    public function reativar(Colaborador $colaborador): void
    {
        $colaborador->restore();
        $colaborador->update([
            'deleted_by' => null,
            'updated_by' => Auth::id(),
        ]);
    }

    /**
     * Remove a foto atual do colaborador.
     */
    public function removerFoto(Colaborador $colaborador): void
    {
        if ($colaborador->foto_path) {
            Storage::disk('local')->delete($colaborador->foto_path);
            $colaborador->update([
                'foto_path' => null,
                'updated_by' => Auth::id(),
            ]);
        }
    }

    /**
     * Processa o upload da foto:
     *   - Valida que é imagem real (não apenas extensão)
     *   - Redimensiona para 600x600 max (mantém proporção)
     *   - Converte para JPEG com qualidade 85%
     *   - Armazena em disco privado (fora do public)
     */
    private function processarFoto(UploadedFile $foto): string
    {
        // Hash do conteúdo no nome (detecta duplicatas + nome imprevisível)
        $hash = hash_file('sha256', $foto->getRealPath());
        $nome = "colaboradores/fotos/{$hash}.jpg";

        // Já existe? Reaproveita
        if (Storage::disk('local')->exists($nome)) {
            return $nome;
        }

        // Redimensiona e converte usando Intervention/Image
        $imagem = Image::read($foto->getRealPath())
            ->scaleDown(600, 600)
            ->toJpeg(85);

        Storage::disk('local')->put($nome, (string) $imagem);

        return $nome;
    }

    /**
     * Converte strings vazias para null no array de dados.
     * Essencial para evitar violações de CHECK constraint em campos enum nullable
     * (ex: banco_tipo_conta aceita 'corrente'/'poupanca'/'salario'/NULL, mas não '').
     *
     * @param array<string, mixed> $dados
     * @return array<string, mixed>
     */
    private function stringVaziaParaNull(array $dados): array
    {
        foreach ($dados as $key => $valor) {
            if (is_string($valor) && trim($valor) === '') {
                $dados[$key] = null;
            }
        }
        return $dados;
    }

    /**
     * Extrai os campos de endereço do array de dados.
     *
     * @param array<string, mixed> $dados Modificado por referência
     * @return array<string, mixed>
     */
    private function extrairEndereco(array &$dados): array
    {
        $campos = ['cep', 'logradouro', 'numero', 'complemento', 'bairro', 'cidade_id'];
        $endereco = [];

        foreach ($campos as $campo) {
            $chave = "endereco_{$campo}";
            if (array_key_exists($chave, $dados)) {
                $endereco[$campo] = $dados[$chave];
                unset($dados[$chave]);
            }
        }

        return $endereco;
    }

    /**
     * @param array<string, mixed> $endereco
     */
    private function temDadosEndereco(array $endereco): bool
    {
        return !empty(array_filter($endereco, fn ($v) => $v !== null && $v !== ''));
    }

    /**
     * @param array<string, mixed> $dados
     */
    private function salvarEndereco(Colaborador $colaborador, array $dados): void
    {
        $colaborador->enderecos()->updateOrCreate(
            ['tipo' => 'residencial', 'principal' => true],
            array_merge($dados, [
                'tipo' => 'residencial',
                'principal' => true,
                'logradouro' => $dados['logradouro'] ?? 'Não informado',
            ])
        );
    }
}
