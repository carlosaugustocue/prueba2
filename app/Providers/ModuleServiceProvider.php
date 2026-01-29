<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Lista de módulos activos
     */
    protected array $modules = [
        'Auth',
        'Patients',
        'AppointmentRequests',
        'Appointments',
        'AdminMetrics',
        'Integrations',
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerModuleBindings();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadModuleRoutes();
        $this->loadModuleMigrations();
    }

    /**
     * Registrar bindings de servicios de cada módulo
     */
    protected function registerModuleBindings(): void
    {
        $this->app->bind(
            \App\Modules\Core\Contracts\NotificationChannelInterface::class,
            \App\Modules\Integrations\WhatsApp\WhatsAppService::class
        );
    }

    /**
     * Cargar rutas de cada módulo
     */
    protected function loadModuleRoutes(): void
    {
        foreach ($this->modules as $module) {
            $routesPath = app_path("Modules/{$module}/routes.php");

            if (file_exists($routesPath)) {
                Route::middleware(['web'])
                    ->group($routesPath);
            }

            $apiRoutesPath = app_path("Modules/{$module}/routes-api.php");

            if (file_exists($apiRoutesPath)) {
                Route::prefix('api')
                    ->middleware(['api'])
                    ->group($apiRoutesPath);
            }
        }
    }

    /**
     * Cargar migraciones de cada módulo
     */
    protected function loadModuleMigrations(): void
    {
        foreach ($this->modules as $module) {
            $migrationsPath = app_path("Modules/{$module}/Database/Migrations");

            if (is_dir($migrationsPath)) {
                $this->loadMigrationsFrom($migrationsPath);
            }
        }
    }
}
