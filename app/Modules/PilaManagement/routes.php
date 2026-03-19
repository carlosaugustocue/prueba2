<?php

use Illuminate\Support\Facades\Route;
use App\Modules\PilaManagement\Controllers\AffiliateNoteController;
use App\Modules\PilaManagement\Controllers\PilaEmployerController;
use App\Modules\PilaManagement\Controllers\PilaAffiliationController;
use App\Modules\PilaManagement\Controllers\PilaCredentialController;

Route::middleware(['auth', 'role:atencion,supervisor,agent,admin,seguridad_social,cartera'])->group(function () {
    Route::prefix('pila')->group(function () {
        Route::resource('employers', PilaEmployerController::class);
        Route::resource('affiliations', PilaAffiliationController::class);
        Route::get('affiliations/export', [PilaAffiliationController::class, 'export'])->name('pila.affiliations.export');

        Route::post('affiliations/{affiliation}/notes', [AffiliateNoteController::class, 'store'])->name('pila.affiliations.notes.store');
        Route::put('affiliations/{affiliation}/notes/{note}', [AffiliateNoteController::class, 'update'])->name('pila.affiliations.notes.update');
        Route::delete('affiliations/{affiliation}/notes/{note}', [AffiliateNoteController::class, 'destroy'])->name('pila.affiliations.notes.destroy');

        Route::get('affiliations/{affiliation}/credentials', [PilaCredentialController::class, 'index'])->name('pila.affiliations.credentials.index');
        Route::get('affiliations/{affiliation}/credentials/audit-logs', [PilaCredentialController::class, 'auditLogs'])->name('pila.affiliations.credentials.audit-logs');
        Route::get('affiliations/{affiliation}/credential/{type}', [PilaCredentialController::class, 'revealCredentialByType'])->name('pila.affiliations.credential.reveal-by-type');
        Route::post('credentials/{kind}/{credentialId}/reveal', [PilaCredentialController::class, 'reveal'])->name('pila.credentials.reveal');
        Route::post('affiliations/{affiliation}/credentials/pila', [PilaCredentialController::class, 'upsertPila'])->name('pila.credentials.pila.upsert');
        Route::post('affiliations/{affiliation}/credentials/portal', [PilaCredentialController::class, 'upsertPortal'])->name('pila.credentials.portal.upsert');
        Route::post('credentials/{kind}/{credentialId}/update', [PilaCredentialController::class, 'updateCredential'])->name('pila.credentials.update');
    });
});

