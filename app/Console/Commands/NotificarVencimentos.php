<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\VencimentoDocumentoMail;
use App\Models\Colaborador;
use App\Models\User;
use App\Models\Veiculo;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

/**
 * Varre o sistema procurando documentos com vencimento próximo (dentro de N dias)
 * e dispara e-mails para os destinatários cadastrados.
 *
 * Agendado pra rodar diariamente.
 *
 * Janelas alertadas: 30 dias, 15 dias, 7 dias, 1 dia, e VENCIDO (apenas o dia seguinte ao vencimento).
 */
class NotificarVencimentos extends Command
{
    protected $signature = 'notificacoes:vencimentos
                            {--dias=30,15,7,1 : Janelas de antecedência (dias) a verificar}
                            {--dry-run : Não envia, apenas mostra}';

    protected $description = 'Notifica por e-mail vencimentos próximos (CNH, licenciamento, seguro)';

    public function handle(): int
    {
        $dias = collect(explode(',', (string) $this->option('dias')))
            ->map(fn ($d) => (int) trim($d))
            ->filter(fn ($d) => $d >= 0)
            ->values()
            ->all();

        $dryRun = (bool) $this->option('dry-run');

        $this->info('Varredura de vencimentos — janelas: ' . implode(', ', $dias) . ' dias');
        if ($dryRun) {
            $this->warn('Modo dry-run ativado (não dispara e-mails).');
        }

        $destinatarios = $this->destinatarios();
        if ($destinatarios->isEmpty()) {
            $this->warn('Nenhum destinatário (admin/gestor_frota) cadastrado. Abortando.');
            return self::SUCCESS;
        }

        $total = 0;
        $total += $this->processarVeiculos($dias, $destinatarios, $dryRun);
        $total += $this->processarCnh($dias, $destinatarios, $dryRun);

        $this->info("Concluído. {$total} notificação(ões) " . ($dryRun ? 'identificadas' : 'enviadas') . '.');

        return self::SUCCESS;
    }

    /**
     * Retorna usuários que devem receber alertas (admins + gestor_frota).
     */
    private function destinatarios(): \Illuminate\Support\Collection
    {
        return User::query()
            ->where('active', true)
            ->whereNotNull('email')
            ->whereHas('roles', function ($q): void {
                $q->whereIn('name', ['admin', 'gestor_frota']);
            })
            ->get(['id', 'name', 'email']);
    }

    /**
     * @param list<int> $dias
     */
    private function processarVeiculos(array $dias, \Illuminate\Support\Collection $destinatarios, bool $dryRun): int
    {
        $hoje = now()->startOfDay();
        $count = 0;

        foreach ($dias as $d) {
            $alvo = $hoje->copy()->addDays($d)->toDateString();

            // Licenciamento
            Veiculo::query()
                ->whereDate('licenciamento_vencimento', $alvo)
                ->get()
                ->each(function (Veiculo $v) use (&$count, $destinatarios, $dryRun): void {
                    $count += $this->enviar(
                        'Licenciamento',
                        $v->placa . ' (' . $v->marca . ' ' . $v->modelo . ')',
                        $v->licenciamento_vencimento,
                        $destinatarios,
                        $dryRun,
                    );
                });

            // Seguro
            Veiculo::query()
                ->whereDate('seguro_vencimento', $alvo)
                ->get()
                ->each(function (Veiculo $v) use (&$count, $destinatarios, $dryRun): void {
                    $count += $this->enviar(
                        'Seguro',
                        $v->placa . ' (' . $v->marca . ' ' . $v->modelo . ')',
                        $v->seguro_vencimento,
                        $destinatarios,
                        $dryRun,
                    );
                });
        }

        return $count;
    }

    /**
     * @param list<int> $dias
     */
    private function processarCnh(array $dias, \Illuminate\Support\Collection $destinatarios, bool $dryRun): int
    {
        $hoje = now()->startOfDay();
        $count = 0;

        foreach ($dias as $d) {
            $alvo = $hoje->copy()->addDays($d)->toDateString();

            Colaborador::query()
                ->whereDate('cnh_validade', $alvo)
                ->whereNotNull('cnh')
                ->whereNull('data_demissao')
                ->get()
                ->each(function (Colaborador $c) use (&$count, $destinatarios, $dryRun): void {
                    $count += $this->enviar(
                        'CNH',
                        $c->nome . ' (' . $c->cnh . ')',
                        $c->cnh_validade,
                        $destinatarios,
                        $dryRun,
                    );
                });
        }

        return $count;
    }

    private function enviar(
        string $tipo,
        string $referente,
        Carbon $vencimento,
        \Illuminate\Support\Collection $destinatarios,
        bool $dryRun,
    ): int {
        $dias = (int) now()->startOfDay()->diffInDays($vencimento->startOfDay(), false);
        $linkAcesso = config('app.url');

        $this->line("  → {$tipo} de '{$referente}' vence em {$dias} dia(s) ({$vencimento->format('d/m/Y')})");

        if ($dryRun) {
            return 1;
        }

        foreach ($destinatarios as $u) {
            Mail::to($u->email)->queue(new VencimentoDocumentoMail(
                tipoDocumento: $tipo,
                referente: $referente,
                dataVencimento: $vencimento,
                linkAcesso: $linkAcesso,
            ));
        }

        return 1;
    }
}
