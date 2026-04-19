<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Model::preventLazyLoading(app()->isProduction());
        Model::preventSilentlyDiscardingAttributes();

        RateLimiter::for('login', fn (Request $req) =>
            Limit::perMinute(10)->by($req->ip())
        );

        RateLimiter::for('api', fn (Request $req) =>
            Limit::perMinute(120)->by($req->user()?->id ?? $req->ip())
        );

        RateLimiter::for('foto', fn (Request $req) =>
            Limit::perMinute(30)->by($req->user()?->id ?? $req->ip())
        );
    }
}
