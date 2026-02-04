<?php

use Illuminate\Support\Facades\Route;
use App\Modules\AdminWhatsApp\Controllers\WhatsAppSendsController;

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->group(function () {
        Route::get('whatsapp-envios', [WhatsAppSendsController::class, 'index'])
            ->name('admin.whatsapp-envios.index');
        Route::post('whatsapp-envios/{reminder}/cancel', [WhatsAppSendsController::class, 'cancel'])
            ->name('admin.whatsapp-envios.cancel');
    });
