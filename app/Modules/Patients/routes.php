<?php

use App\Modules\Patients\Controllers\AffiliateController;
use App\Modules\Patients\Controllers\AffiliatePaymentController;
use App\Modules\Patients\Controllers\AffiliateTaskController;
use App\Modules\SocialSecurity\Controllers\NoveltyController;
use App\Modules\SocialSecurity\Controllers\OperatorCredentialController;
use App\Modules\SocialSecurity\Controllers\SupportDocumentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:atencion,supervisor,agent,admin,seguridad_social,cartera'])->group(function () {
    // Redirecciones de rutas antiguas (patients) a las nuevas (affiliates)
    Route::get('patients', fn () => redirect()->route('affiliates.index', request()->query(), 301));
    Route::get('patients/create', fn () => redirect()->route('affiliates.create', request()->query(), 301));
    Route::get('patients/{id}', fn ($id) => redirect()->route('affiliates.show', $id, 301))->whereNumber('id');
    Route::get('patients/{id}/edit', fn ($id) => redirect()->route('affiliates.edit', $id, 301))->whereNumber('id');

    Route::resource('affiliates', AffiliateController::class);
    Route::post('affiliates/{affiliate}/novelties', [NoveltyController::class, 'store'])->name('affiliates.novelties.store');

    Route::post('affiliates/{affiliate}/operator-credentials', [OperatorCredentialController::class, 'store'])->name('affiliates.operator-credentials.store');
    Route::get('affiliates/{affiliate}/operator-credentials/{operatorCredential}', [OperatorCredentialController::class, 'show'])->name('affiliates.operator-credentials.show');
    Route::put('affiliates/{affiliate}/operator-credentials/{operatorCredential}', [OperatorCredentialController::class, 'update'])->name('affiliates.operator-credentials.update');
    Route::delete('affiliates/{affiliate}/operator-credentials/{operatorCredential}', [OperatorCredentialController::class, 'destroy'])->name('affiliates.operator-credentials.destroy');

    Route::post('affiliates/{affiliate}/support-documents', [SupportDocumentController::class, 'store'])->name('affiliates.support-documents.store');
    Route::get('affiliates/{affiliate}/support-documents/{supportDocument}/download', [SupportDocumentController::class, 'download'])->name('affiliates.support-documents.download');
    Route::delete('affiliates/{affiliate}/support-documents/{supportDocument}', [SupportDocumentController::class, 'destroy'])->name('affiliates.support-documents.destroy');

    // Pagos registrados por afiliado (cartera / seguridad social)
    Route::get('affiliates/{affiliate}/payments', [AffiliatePaymentController::class, 'index'])->name('affiliates.payments.index');
    Route::post('affiliates/{affiliate}/payments', [AffiliatePaymentController::class, 'store'])->name('affiliates.payments.store');

    Route::prefix('api/affiliates')->group(function () {
        Route::get('search', [AffiliateController::class, 'search'])->name('affiliates.search');
        Route::get('search-holders', [AffiliateController::class, 'searchHolders'])->name('affiliates.search.holders');
        Route::get('{affiliate}/beneficiaries', [AffiliateController::class, 'getBeneficiaries'])->name('affiliates.beneficiaries');
        Route::get('{affiliate}/authorizations', [AffiliateController::class, 'authorizations'])->name('affiliates.authorizations');
        Route::post('/', [AffiliateController::class, 'storeApi'])->name('affiliates.store.api');
    });

    // Tareas internas por afiliado (cartera, seguridad social, etc.)
    Route::prefix('api')->group(function () {
        Route::get('affiliate-tasks/my-pending', [AffiliateTaskController::class, 'myPending'])->name('affiliate-tasks.my-pending');
        Route::post('affiliate-tasks/{task}/complete', [AffiliateTaskController::class, 'complete'])->name('affiliate-tasks.complete');
    });
});
