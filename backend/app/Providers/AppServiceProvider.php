<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Lazy loading proibido em produção
        Model::preventLazyLoading(app()->isProduction());

        // Descarte silencioso de atributos proibido em todos os ambientes
        Model::preventSilentlyDiscardingAttributes();
    }
}
