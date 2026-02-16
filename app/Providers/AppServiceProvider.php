<?php

namespace App\Providers;

use App\Foundation\Vite as AppVite;
use Illuminate\Foundation\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

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
        // En local, la URL base se toma del host de la petición para que la app
        // funcione en cualquier equipo/IP sin fijar APP_URL a una IP concreta.
        if (app()->environment('local') && $this->app->runningInConsole() === false) {
            $request = request();
            if ($request && $request->getHttpHost()) {
                URL::forceRootUrl($request->getScheme() . '://' . $request->getHttpHost());
            }
        }
    }
}
