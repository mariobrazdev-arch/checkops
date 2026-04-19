<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $inicio = microtime(true);

        $response = $next($request);

        $tempo = (int) ((microtime(true) - $inicio) * 1000);
        $status = $response->getStatusCode();

        // Loga apenas se lento (>500ms) ou status de erro (>=400)
        if ($tempo > 500 || $status >= 400) {
            Log::channel('api')->info('request', [
                'method'   => $request->method(),
                'path'     => $request->path(),
                'user_id'  => $request->user()?->id,
                'status'   => $status,
                'tempo_ms' => $tempo,
            ]);
        }

        return $response;
    }
}
