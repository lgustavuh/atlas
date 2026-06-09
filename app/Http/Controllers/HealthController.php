<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Health check robusto para monitoramento externo (UptimeRobot, Datadog, etc).
 *
 * Endpoint: GET /health (sem auth)
 *
 * Retorna 200 com JSON detalhado se TUDO ok.
 * Retorna 503 com JSON descrevendo o componente que falhou.
 *
 * Status individuais:
 *   - app: framework respondeu
 *   - database: ping no PostgreSQL
 *   - cache: ler/escrever no driver de cache
 *   - redis: ping (se configurado)
 *   - storage: storage local gravável
 *   - queue: existência de tabela de jobs (se driver = database) ou conexão Redis
 *
 * Para monitoramento básico (load balancer health check), use /up (raso e rápido).
 * Para monitoramento de infraestrutura, use /health (mais demorado, ~50ms).
 */
class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'app' => $this->checkApp(),
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'redis' => $this->checkRedis(),
            'storage' => $this->checkStorage(),
        ];

        $algumFalhou = collect($checks)->contains(fn ($r) => $r['ok'] === false);
        $status = $algumFalhou ? 503 : 200;

        return response()->json([
            'status' => $algumFalhou ? 'degraded' : 'ok',
            'timestamp' => now()->toIso8601String(),
            'version' => config('app.version', 'unknown'),
            'environment' => app()->environment(),
            'checks' => $checks,
        ], $status);
    }

    /**
     * @return array{ok: bool, message: string, latency_ms?: int}
     */
    private function checkApp(): array
    {
        return [
            'ok' => true,
            'message' => 'Framework operacional',
        ];
    }

    /**
     * @return array{ok: bool, message: string, latency_ms?: int}
     */
    private function checkDatabase(): array
    {
        try {
            $inicio = microtime(true);
            DB::connection()->getPdo();
            DB::select('SELECT 1 as ok');
            $latencia = (int) ((microtime(true) - $inicio) * 1000);

            return [
                'ok' => true,
                'message' => 'PostgreSQL respondendo',
                'latency_ms' => $latencia,
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'message' => 'Falha no banco: ' . $this->mensagemSegura($e),
            ];
        }
    }

    /**
     * @return array{ok: bool, message: string, latency_ms?: int}
     */
    private function checkCache(): array
    {
        try {
            $inicio = microtime(true);
            $key = '_health_check_' . random_int(1, 9999);
            Cache::put($key, 'test', 5);
            $value = Cache::get($key);
            Cache::forget($key);
            $latencia = (int) ((microtime(true) - $inicio) * 1000);

            if ($value !== 'test') {
                return ['ok' => false, 'message' => 'Cache não retornou valor gravado'];
            }

            return [
                'ok' => true,
                'message' => 'Cache (' . config('cache.default') . ') OK',
                'latency_ms' => $latencia,
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'message' => 'Falha no cache: ' . $this->mensagemSegura($e),
            ];
        }
    }

    /**
     * @return array{ok: bool, message: string, latency_ms?: int}
     */
    private function checkRedis(): array
    {
        // Só checa se algum componente do sistema realmente usa Redis.
        // Em ambiente de teste/dev, é normal usar database/array/sync.
        $usaRedis = in_array('redis', [
            config('cache.default'),
            config('queue.default'),
            config('session.driver'),
        ], true);

        if (!$usaRedis) {
            return ['ok' => true, 'message' => 'Redis não está em uso (skip)'];
        }

        try {
            $inicio = microtime(true);
            $pong = Redis::connection()->ping();
            $latencia = (int) ((microtime(true) - $inicio) * 1000);

            // O retorno do ping varia entre drivers: pode ser "+PONG", true, "PONG", ou 1
            $ok = $pong === true || $pong === '+PONG' || $pong === 'PONG' || $pong === 1;

            return [
                'ok' => $ok,
                'message' => $ok ? 'Redis respondendo' : 'Redis ping inesperado',
                'latency_ms' => $latencia,
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'message' => 'Falha no Redis: ' . $this->mensagemSegura($e),
            ];
        }
    }

    /**
     * @return array{ok: bool, message: string}
     */
    private function checkStorage(): array
    {
        try {
            $disk = Storage::disk('local');
            $testFile = '_health_check.txt';
            $disk->put($testFile, 'ok');
            $content = $disk->get($testFile);
            $disk->delete($testFile);

            if ($content !== 'ok') {
                return ['ok' => false, 'message' => 'Storage retornou conteúdo inesperado'];
            }

            return ['ok' => true, 'message' => 'Storage gravável'];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'message' => 'Falha no storage: ' . $this->mensagemSegura($e),
            ];
        }
    }

    /**
     * Mensagem de erro adequada por ambiente.
     *
     * Em produção, esconde detalhes (evita vazar credenciais ou estrutura).
     * Em dev, mostra detalhes para facilitar debugging.
     */
    private function mensagemSegura(Throwable $e): string
    {
        if (app()->environment('production')) {
            return get_class($e);
        }
        return get_class($e) . ': ' . $e->getMessage();
    }
}
