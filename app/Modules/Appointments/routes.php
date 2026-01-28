<?php

use App\Modules\Appointments\Controllers\AppointmentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/', [AppointmentController::class, 'dashboard'])->name('dashboard');
    Route::get('/dashboard', [AppointmentController::class, 'dashboard'])->name('home');

    Route::resource('appointments', AppointmentController::class);

    Route::prefix('appointments/{appointment}')->group(function () {
        Route::patch('status', [AppointmentController::class, 'changeStatus'])->name('appointments.change-status');
        Route::post('send-confirmation', [AppointmentController::class, 'sendConfirmation'])->name('appointments.send-confirmation');
    });
});
