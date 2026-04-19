<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPerfil
{
    public function handle(Request $request, Closure $next, string ...$perfis): Response
    {
        if (! $request->user() || ! in_array($request->user()->perfil, $perfis)) {
            return response()->json(['message' => 'Acesso não autorizado.'], 403);
        }

        return $next($request);
    }
}
