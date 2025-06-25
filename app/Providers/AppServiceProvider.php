<?php

namespace App\Providers;

use App\Models\Cobranca;
use App\Observers\CobrancaObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Registrar Observer da Cobrança
        Cobranca::observe(CobrancaObserver::class);
    }
}
