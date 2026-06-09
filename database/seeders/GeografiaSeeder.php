<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder de geografia brasileira.
 *
 * Popula:
 *   - País: Brasil (e alguns vizinhos comuns)
 *   - Estados: todas as 27 UFs brasileiras com código IBGE
 *   - Cidades: apenas as capitais inicialmente.
 *
 * Para popular TODAS as cidades brasileiras (~5.570), use o comando:
 *     php artisan etc:importar-cidades-ibge
 *
 * O comando baixa o CSV do IBGE e popula a tabela. Esse processo demora
 * alguns minutos e deve ser feito uma vez só (não no seed padrão).
 */
class GeografiaSeeder extends Seeder
{
    public function run(): void
    {
        // === Países ===
        $paises = [
            ['nome' => 'Brasil',    'iso2' => 'BR', 'iso3' => 'BRA', 'codigo_numerico' => 76,  'telefone_ddi' => '+55'],
            ['nome' => 'Argentina', 'iso2' => 'AR', 'iso3' => 'ARG', 'codigo_numerico' => 32,  'telefone_ddi' => '+54'],
            ['nome' => 'Uruguai',   'iso2' => 'UY', 'iso3' => 'URY', 'codigo_numerico' => 858, 'telefone_ddi' => '+598'],
            ['nome' => 'Paraguai',  'iso2' => 'PY', 'iso3' => 'PRY', 'codigo_numerico' => 600, 'telefone_ddi' => '+595'],
            ['nome' => 'Estados Unidos', 'iso2' => 'US', 'iso3' => 'USA', 'codigo_numerico' => 840, 'telefone_ddi' => '+1'],
            ['nome' => 'Portugal',  'iso2' => 'PT', 'iso3' => 'PRT', 'codigo_numerico' => 620, 'telefone_ddi' => '+351'],
        ];

        foreach ($paises as &$pais) {
            $pais['created_at'] = now();
            $pais['updated_at'] = now();
        }

        DB::table('paises')->insert($paises);

        $brasilId = DB::table('paises')->where('iso2', 'BR')->value('id');

        // === Estados (UFs) com códigos IBGE ===
        $estados = [
            // Norte
            ['uf' => 'AC', 'nome' => 'Acre',                'codigo_ibge' => 12],
            ['uf' => 'AP', 'nome' => 'Amapá',               'codigo_ibge' => 16],
            ['uf' => 'AM', 'nome' => 'Amazonas',            'codigo_ibge' => 13],
            ['uf' => 'PA', 'nome' => 'Pará',                'codigo_ibge' => 15],
            ['uf' => 'RO', 'nome' => 'Rondônia',            'codigo_ibge' => 11],
            ['uf' => 'RR', 'nome' => 'Roraima',             'codigo_ibge' => 14],
            ['uf' => 'TO', 'nome' => 'Tocantins',           'codigo_ibge' => 17],
            // Nordeste
            ['uf' => 'AL', 'nome' => 'Alagoas',             'codigo_ibge' => 27],
            ['uf' => 'BA', 'nome' => 'Bahia',               'codigo_ibge' => 29],
            ['uf' => 'CE', 'nome' => 'Ceará',               'codigo_ibge' => 23],
            ['uf' => 'MA', 'nome' => 'Maranhão',            'codigo_ibge' => 21],
            ['uf' => 'PB', 'nome' => 'Paraíba',             'codigo_ibge' => 25],
            ['uf' => 'PE', 'nome' => 'Pernambuco',          'codigo_ibge' => 26],
            ['uf' => 'PI', 'nome' => 'Piauí',               'codigo_ibge' => 22],
            ['uf' => 'RN', 'nome' => 'Rio Grande do Norte', 'codigo_ibge' => 24],
            ['uf' => 'SE', 'nome' => 'Sergipe',             'codigo_ibge' => 28],
            // Centro-Oeste
            ['uf' => 'DF', 'nome' => 'Distrito Federal',    'codigo_ibge' => 53],
            ['uf' => 'GO', 'nome' => 'Goiás',               'codigo_ibge' => 52],
            ['uf' => 'MT', 'nome' => 'Mato Grosso',         'codigo_ibge' => 51],
            ['uf' => 'MS', 'nome' => 'Mato Grosso do Sul',  'codigo_ibge' => 50],
            // Sudeste
            ['uf' => 'ES', 'nome' => 'Espírito Santo',      'codigo_ibge' => 32],
            ['uf' => 'MG', 'nome' => 'Minas Gerais',        'codigo_ibge' => 31],
            ['uf' => 'RJ', 'nome' => 'Rio de Janeiro',      'codigo_ibge' => 33],
            ['uf' => 'SP', 'nome' => 'São Paulo',           'codigo_ibge' => 35],
            // Sul
            ['uf' => 'PR', 'nome' => 'Paraná',              'codigo_ibge' => 41],
            ['uf' => 'RS', 'nome' => 'Rio Grande do Sul',   'codigo_ibge' => 43],
            ['uf' => 'SC', 'nome' => 'Santa Catarina',      'codigo_ibge' => 42],
        ];

        foreach ($estados as &$estado) {
            $estado['pais_id'] = $brasilId;
            $estado['created_at'] = now();
            $estado['updated_at'] = now();
        }

        DB::table('estados')->insert($estados);

        // === Capitais + a cidade do cliente (Itaú de Minas) ===
        // Para popular o restante, usar o comando de importação IBGE
        $estadosMap = DB::table('estados')->where('pais_id', $brasilId)
            ->pluck('id', 'uf')->toArray();

        $capitais = [
            ['uf' => 'AC', 'nome' => 'Rio Branco',     'codigo_ibge' => 1200401],
            ['uf' => 'AL', 'nome' => 'Maceió',         'codigo_ibge' => 2704302],
            ['uf' => 'AP', 'nome' => 'Macapá',         'codigo_ibge' => 1600303],
            ['uf' => 'AM', 'nome' => 'Manaus',         'codigo_ibge' => 1302603],
            ['uf' => 'BA', 'nome' => 'Salvador',       'codigo_ibge' => 2927408],
            ['uf' => 'CE', 'nome' => 'Fortaleza',      'codigo_ibge' => 2304400],
            ['uf' => 'DF', 'nome' => 'Brasília',       'codigo_ibge' => 5300108],
            ['uf' => 'ES', 'nome' => 'Vitória',        'codigo_ibge' => 3205309],
            ['uf' => 'GO', 'nome' => 'Goiânia',        'codigo_ibge' => 5208707],
            ['uf' => 'MA', 'nome' => 'São Luís',       'codigo_ibge' => 2111300],
            ['uf' => 'MT', 'nome' => 'Cuiabá',         'codigo_ibge' => 5103403],
            ['uf' => 'MS', 'nome' => 'Campo Grande',   'codigo_ibge' => 5002704],
            ['uf' => 'MG', 'nome' => 'Belo Horizonte', 'codigo_ibge' => 3106200],
            ['uf' => 'PA', 'nome' => 'Belém',          'codigo_ibge' => 1501402],
            ['uf' => 'PB', 'nome' => 'João Pessoa',    'codigo_ibge' => 2507507],
            ['uf' => 'PR', 'nome' => 'Curitiba',       'codigo_ibge' => 4106902],
            ['uf' => 'PE', 'nome' => 'Recife',         'codigo_ibge' => 2611606],
            ['uf' => 'PI', 'nome' => 'Teresina',       'codigo_ibge' => 2211001],
            ['uf' => 'RJ', 'nome' => 'Rio de Janeiro', 'codigo_ibge' => 3304557],
            ['uf' => 'RN', 'nome' => 'Natal',          'codigo_ibge' => 2408102],
            ['uf' => 'RS', 'nome' => 'Porto Alegre',   'codigo_ibge' => 4314902],
            ['uf' => 'RO', 'nome' => 'Porto Velho',    'codigo_ibge' => 1100205],
            ['uf' => 'RR', 'nome' => 'Boa Vista',      'codigo_ibge' => 1400100],
            ['uf' => 'SC', 'nome' => 'Florianópolis',  'codigo_ibge' => 4205407],
            ['uf' => 'SP', 'nome' => 'São Paulo',      'codigo_ibge' => 3550308],
            ['uf' => 'SE', 'nome' => 'Aracaju',        'codigo_ibge' => 2800308],
            ['uf' => 'TO', 'nome' => 'Palmas',         'codigo_ibge' => 1721000],
        ];

        $cidades = [];
        foreach ($capitais as $cap) {
            $cidades[] = [
                'estado_id' => $estadosMap[$cap['uf']],
                'nome' => $cap['nome'],
                'codigo_ibge' => $cap['codigo_ibge'],
                'capital' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Cidade do cliente: Itaú de Minas - MG
        $cidades[] = [
            'estado_id' => $estadosMap['MG'],
            'nome' => 'Itaú de Minas',
            'codigo_ibge' => 3133600,
            'capital' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('cidades')->insert($cidades);

        $this->command->info('  ✓ 6 países, 27 UFs, 28 cidades');
        $this->command->info('  ℹ Para popular todas as ~5.570 cidades, execute: php artisan etc:importar-cidades-ibge');
    }
}
