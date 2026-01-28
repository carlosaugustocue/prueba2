<?php

use App\Modules\Patients\Controllers\PatientController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::resource('patients', PatientController::class);
    
    // API endpoints para AJAX
    Route::prefix('api/patients')->group(function () {
        Route::get('search', [PatientController::class, 'search'])->name('patients.search');
        Route::get('search-holders', [PatientController::class, 'searchHolders'])->name('patients.search.holders');
        Route::get('{patient}/beneficiaries', [PatientController::class, 'getBeneficiaries'])->name('patients.beneficiaries');
        Route::post('/', [PatientController::class, 'storeApi'])->name('patients.store.api');
    });
});
