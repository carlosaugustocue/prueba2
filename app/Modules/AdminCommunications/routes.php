<?php

use Illuminate\Support\Facades\Route;
use App\Modules\AdminCommunications\Controllers\CommunicationsController;

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->group(function () {
        Route::get('comunicaciones', [CommunicationsController::class, 'index'])
            ->name('admin.comunicaciones');
    });

