<?php

namespace App\Providers;

use App\Foundation\Vite as AppVite;
use Illuminate\Foundation\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // En desarrollo, la URL de Vite usa el host de la petición para que el front
        // cargue al abrir la app desde otra máquina (no solo localhost).
        $this->app->bind(Vite::class, AppVite::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
