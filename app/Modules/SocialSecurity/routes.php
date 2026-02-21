<?php

use App\Modules\SocialSecurity\Controllers\PayerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:atencion,supervisor,agent,admin,seguridad_social'])->group(function () {
    Route::resource('payers', PayerController::class);
});
