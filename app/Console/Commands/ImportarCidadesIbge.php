<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Cidade;
use App\Models\Estado;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Importa todas as ~5.570 cidades brasileiras a partir da API pública do IBGE.
 *
 * API: https://servicodados.ibge.gov.br/api/docs/localidades
 *
 * Uso:
 *   php artisan etc:importar-cidades-ibge
 *   php artisan etc:importar-cidades-ibge --force  (apaga e reimporta)
 *
 * Custo: ~10s e 1 request HTTP. Idempotente (não duplica).
 */
class ImportarCidadesIbge extends Command
{
    protected $signature = 'etc:importar-cidades-ibge
                            {--force : Apaga as cidades existentes antes de importar}';

    protected $description = 'Importa todas as cidades brasileiras da API do IBGE';

    private const IBGE_URL = 'https://servicodados.ibge.gov.br/api/v1/localidades/municipios';

    public function handle(): int
    {
        $this->info('Buscando municípios na API do IBGE...');

        try {
            $response = Http::timeout(60)->get(self::IBGE_URL);

            if (!$response->successful()) {
                $this->error("Falha na API do IBGE: HTTP {$response->status()}");
                return self::FAILURE;
            }

            $municipios = $response->json();
        } catch (\Throwable $e) {
            $this->error("Erro ao acessar IBGE: {$e->getMessage()}");
            $this->warn('Verifique sua conexão com a internet.');
            return self::FAILURE;
        }

        if (!is_array($municipios) || empty($municipios)) {
            $this->error('A API não retornou dados válidos.');
            return self::FAILURE;
        }

        $total = count($municipios);
        $this->info("Recebidos {$total} municípios.");

        // Mapeia UF → estado_id (carrega uma vez na memória)
        $estados = Estado::pluck('id', 'uf')->toArray();
        if (empty($estados)) {
            $this->error('Nenhum estado encontrado. Execute o seeder antes: php artisan db:seed');
            return self::FAILURE;
        }

        if ($this->option('force')) {
            if (!$this->confirm('Apagar TODAS as cidades existentes? Isso pode quebrar referências.')) {
                $this->info('Operação cancelada.');
                return self::SUCCESS;
            }
            DB::table('cidades')->delete();
            $this->warn('Cidades existentes apagadas.');
        }

        // Pega códigos IBGE já cadastrados para evitar duplicação
        $jaExistem = Cidade::whereNotNull('codigo_ibge')
            ->pluck('codigo_ibge')
            ->flip()
            ->toArray();

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $importados = 0;
        $pulados = 0;
        $erros = 0;
        $lote = [];
        $agora = now();

        foreach ($municipios as $m) {
            $codigoIbge = $m['id'] ?? null;
            $nome = $m['nome'] ?? null;
            $uf = $m['microrregiao']['mesorregiao']['UF']['sigla'] ?? null;

            if (!$codigoIbge || !$nome || !$uf) {
                $erros++;
                $bar->advance();
                continue;
            }

            if (isset($jaExistem[$codigoIbge])) {
                $pulados++;
                $bar->advance();
                continue;
            }

            if (!isset($estados[$uf])) {
                $erros++;
                $bar->advance();
                continue;
            }

            $lote[] = [
                'estado_id' => $estados[$uf],
                'nome' => $nome,
                'codigo_ibge' => $codigoIbge,
                'capital' => false, // capitais já vêm pelo seeder com capital=true
                'created_at' => $agora,
                'updated_at' => $agora,
            ];

            $importados++;

            // Insere em lotes de 500 (performance + memória)
            if (count($lote) >= 500) {
                DB::table('cidades')->insert($lote);
                $lote = [];
            }

            $bar->advance();
        }

        // Insere o resto
        if (!empty($lote)) {
            DB::table('cidades')->insert($lote);
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Importação concluída:");
        $this->line("  Importados: {$importados}");
        $this->line("  Já existiam: {$pulados}");
        if ($erros > 0) {
            $this->warn("  Erros: {$erros}");
        }

        $this->newLine();
        $totalCidades = Cidade::count();
        $this->info("Total de cidades no banco: {$totalCidades}");

        return self::SUCCESS;
    }
}
