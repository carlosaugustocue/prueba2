<?php

use App\Modules\Authorizations\Controllers\AuthorizationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:atencion,supervisor,agent,admin'])->group(function () {
    Route::resource('authorizations', AuthorizationController::class)->except(['edit']);

    Route::prefix('authorizations/{authorization}')->group(function () {
        Route::post('detach-request', [AuthorizationController::class, 'detachRequest'])
            ->name('authorizations.detach-request');
        Route::post('documents', [AuthorizationController::class, 'storeDocument'])
            ->name('authorizations.documents.store');
        Route::get('documents/{document}/download', [AuthorizationController::class, 'downloadDocument'])
            ->name('authorizations.documents.download');
    });
});
