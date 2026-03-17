<?php

use Illuminate\Support\Facades\Route;
use App\Modules\PilaManagement\Controllers\PilaEmployerController;
use App\Modules\PilaManagement\Controllers\PilaAffiliationController;

Route::middleware(['auth', 'role:atencion,supervisor,agent,admin,seguridad_social,cartera'])->group(function () {
    Route::prefix('pila')->group(function () {
        Route::resource('employers', PilaEmployerController::class);
        Route::resource('affiliations', PilaAffiliationController::class);
    });
});

