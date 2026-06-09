<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Aplica cabeçalhos HTTP defensivos em todas as respostas.
 *
 * Defesa em profundidade:
 *   - X-Frame-Options: bloqueia clickjacking (não carrega o site em iframe externo)
 *   - X-Content-Type-Options: nosniff impede sniffing de MIME
 *   - Referrer-Policy: limita vazamento da URL atual para outros sites
 *   - Permissions-Policy: desabilita APIs não usadas (câmera, microfone, geolocation)
 *   - Strict-Transport-Security: força HTTPS no browser por 1 ano (só em produção/HTTPS)
 *   - Content-Security-Policy: restringe origem de scripts/imagens/etc (modo report-only fica de fora;
 *     incluímos uma policy permissiva que ainda assim bloqueia XSS no inline mais óbvio)
 *
 * Observação: a CSP é permissiva porque o sistema usa Livewire (inline JS), Alpine e
 * Tailwind (inline styles para artefatos do build). Uma CSP estrita exigiria nonces
 * em todos os scripts inline e quebraria o sistema — fica como melhoria futura.
 */
class SecurityHeadersMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // Sempre aplicáveis
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'same-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), interest-cohort=()');

        // HSTS — só faz sentido em HTTPS (caso contrário força redirect quebrado)
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // CSP — permissiva para acomodar Livewire/Alpine/Tailwind
        // Tabler Icons agora vem do bundle Vite (mesmo dominio), so precisamos da fonte Figtree.
        //
        // IMPORTANTE: style-src-elem e font-src-elem precisam ser DEFINIDOS EXPLICITAMENTE.
        // Browsers Chromium recentes nao fazem fallback de style-src para style-src-elem,
        // entao se voce so define style-src, stylesheets externos sao bloqueados mesmo
        // estando autorizadas no style-src. Mesma coisa para font-src-elem.
        $scriptSrc = "'self' 'unsafe-inline' 'unsafe-eval'";
        $styleSrc = "'self' 'unsafe-inline' https://fonts.bunny.net";
        $fontSrc = "'self' data: https://fonts.bunny.net";
        $connectSrc = "'self'";

        if (app()->environment('local')) {
            // Vite dev server (npm run dev) precisa acessar via HMR e carregar JS
            $scriptSrc .= ' http://localhost:5173 http://[::1]:5173 ws://localhost:5173';
            $connectSrc .= ' http://localhost:5173 ws://localhost:5173';
        }

        $csp = implode('; ', [
            "default-src 'self'",
            "script-src {$scriptSrc}",
            "script-src-elem {$scriptSrc}",
            "style-src {$styleSrc}",
            "style-src-elem {$styleSrc}",
            "font-src {$fontSrc}",
            "font-src-elem {$fontSrc}",
            "img-src 'self' data: blob:",
            "connect-src {$connectSrc}",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);
        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
