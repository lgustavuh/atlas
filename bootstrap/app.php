<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\SecurityHeadersMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Apelido para nosso middleware customizado
        $middleware->alias([
            'account.active' => EnsureAccountIsActive::class,
        ]);

        // Cabeçalhos de segurança em TODAS as respostas web
        $middleware->web(append: [
            SecurityHeadersMiddleware::class,
        ]);

        // Confiar em proxies (necessário se estiver atrás de Nginx/load balancer)
        $middleware->trustProxies(at: '*');

        // Redireciona não-autenticados para a rota de login
        $middleware->redirectGuestsTo(fn () => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Em produção, esconde detalhes técnicos do usuário final
        $exceptions->dontReport([
            \Illuminate\Auth\AuthenticationException::class,
            \Illuminate\Validation\ValidationException::class,
        ]);
    })
    ->create();
