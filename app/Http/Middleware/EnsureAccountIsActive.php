<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifica em CADA request se o usuário autenticado ainda pode usar o sistema.
 *
 * Por quê: alguém pode estar logado quando o admin desativa a conta.
 * Sem este middleware, a sessão continua válida até expirar.
 * Com ele, ações futuras são bloqueadas imediatamente.
 */
class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && !$user->podeAutenticar()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'Sua conta foi desativada ou bloqueada. Contate o administrador.');
        }

        return $next($request);
    }
}
