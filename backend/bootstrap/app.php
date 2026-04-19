<?php

use App\Exceptions\BusinessException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            RateLimiter::for('login', fn (Request $req) =>
                Limit::perMinute(10)->by($req->ip())
            );

            RateLimiter::for('api', fn (Request $req) =>
                Limit::perMinute(120)->by($req->user()?->id ?? $req->ip())
            );

            RateLimiter::for('foto', fn (Request $req) =>
                Limit::perMinute(30)->by($req->user()?->id ?? $req->ip())
            );
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'perfil'    => \App\Http\Middleware\CheckPerfil::class,
            'throttle'  => ThrottleRequests::class,
        ]);

        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->append(\App\Http\Middleware\LogRequests::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (BusinessException $e, Request $req) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 422);
        });

        $exceptions->render(function (AuthorizationException $e, Request $req) {
            return response()->json(['message' => 'Ação não autorizada.'], 403);
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $req) {
            $model = class_basename($e->getModel());
            return response()->json(['message' => "{$model} não encontrado."], 404);
        });
    })->create();
