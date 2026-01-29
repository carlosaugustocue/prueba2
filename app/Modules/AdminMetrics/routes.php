<?php

use Illuminate\Support\Facades\Route;
use App\Modules\AdminMetrics\Controllers\MetricsController;

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->group(function () {
        Route::get('metricas/operadores', [MetricsController::class, 'operators'])
            ->name('admin.metricas.operadores');

        Route::get('metricas/tiempos', [MetricsController::class, 'times'])
            ->name('admin.metricas.tiempos');

        Route::get('anotaciones', [MetricsController::class, 'annotations'])
            ->name('admin.anotaciones');
    });

