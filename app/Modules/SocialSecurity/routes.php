<?php

use App\Modules\SocialSecurity\Controllers\DashboardController;
use App\Modules\SocialSecurity\Controllers\PayrollController;
use App\Modules\SocialSecurity\Controllers\PayerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:atencion,supervisor,agent,admin,seguridad_social'])->group(function () {
    Route::get('dashboard-ss', DashboardController::class)->name('dashboard-ss');
    Route::resource('payers', PayerController::class);
    Route::post('payrolls/preview', [PayrollController::class, 'preview'])->name('payrolls.preview');
    Route::post('payrolls/batch-generate', [PayrollController::class, 'batchGenerate'])->name('payrolls.batch-generate');
    Route::post('payrolls/batch-settle', [PayrollController::class, 'batchSettle'])->name('payrolls.batch-settle');
    Route::post('payrolls/{payroll}/settle', [PayrollController::class, 'settle'])->name('payrolls.settle');
    Route::post('payrolls/{payroll}/mark-sent', [PayrollController::class, 'markSent'])->name('payrolls.mark-sent');
    Route::post('payrolls/{payroll}/mark-paid', [PayrollController::class, 'markPaid'])->name('payrolls.mark-paid');
    Route::resource('payrolls', PayrollController::class)->only(['index', 'create', 'store', 'show']);
});
