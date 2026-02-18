<?php

use App\Modules\Patients\Controllers\AffiliateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    // Redirecciones de rutas antiguas (patients) a las nuevas (affiliates)
    Route::get('patients', fn () => redirect()->route('affiliates.index', request()->query(), 301));
    Route::get('patients/create', fn () => redirect()->route('affiliates.create', request()->query(), 301));
    Route::get('patients/{id}', fn ($id) => redirect()->route('affiliates.show', $id, 301))->whereNumber('id');
    Route::get('patients/{id}/edit', fn ($id) => redirect()->route('affiliates.edit', $id, 301))->whereNumber('id');

    Route::resource('affiliates', AffiliateController::class);

    Route::prefix('api/affiliates')->group(function () {
        Route::get('search', [AffiliateController::class, 'search'])->name('affiliates.search');
        Route::get('search-holders', [AffiliateController::class, 'searchHolders'])->name('affiliates.search.holders');
        Route::get('{affiliate}/beneficiaries', [AffiliateController::class, 'getBeneficiaries'])->name('affiliates.beneficiaries');
        Route::post('/', [AffiliateController::class, 'storeApi'])->name('affiliates.store.api');
    });
});
