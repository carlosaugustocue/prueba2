<?php

use App\Modules\AppointmentRequests\Controllers\AppointmentRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::resource('appointment-requests', AppointmentRequestController::class)
        ->except(['edit', 'update']);
    
    // Acciones especiales
    Route::prefix('appointment-requests/{appointment_request}')->group(function () {
        Route::post('start', [AppointmentRequestController::class, 'start'])
            ->name('appointment-requests.start');
        
        Route::get('create-appointment', [AppointmentRequestController::class, 'createAppointment'])
            ->name('appointment-requests.create-appointment');
        
        Route::post('mark-failed', [AppointmentRequestController::class, 'markFailed'])
            ->name('appointment-requests.mark-failed');
        
        Route::post('cancel', [AppointmentRequestController::class, 'cancel'])
            ->name('appointment-requests.cancel');
    });
});
