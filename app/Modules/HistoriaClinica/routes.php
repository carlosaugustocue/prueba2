<?php

use App\Modules\HistoriaClinica\Controllers\HistoriaClinicaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:atencion,admin'])->group(function () {
    Route::get('affiliates/{affiliate}/historia-clinica', [HistoriaClinicaController::class, 'show'])
        ->name('affiliates.historia-clinica.show');

    Route::post('affiliates/{affiliate}/historia-clinica/encuentros', [HistoriaClinicaController::class, 'storeEncuentro'])
        ->name('affiliates.historia-clinica.encuentros.store');

    Route::get('affiliates/{affiliate}/historia-clinica/encuentros/{encuentro}', [HistoriaClinicaController::class, 'showEncuentro'])
        ->name('affiliates.historia-clinica.encuentros.show');

    Route::post('affiliates/{affiliate}/historia-clinica/encuentros/{encuentro}/examen-fisico', [HistoriaClinicaController::class, 'storeExamenFisico'])
        ->name('affiliates.historia-clinica.encuentros.examen-fisico.store');

    Route::post('affiliates/{affiliate}/historia-clinica/antecedentes', [HistoriaClinicaController::class, 'storeAntecedente'])
        ->name('affiliates.historia-clinica.antecedentes.store');

    Route::post('affiliates/{affiliate}/historia-clinica/documentos', [HistoriaClinicaController::class, 'storeDocumento'])
        ->name('affiliates.historia-clinica.documentos.store');

    Route::get('affiliates/{affiliate}/historia-clinica/documentos/{documento}/download', [HistoriaClinicaController::class, 'downloadDocumento'])
        ->name('affiliates.historia-clinica.documentos.download');
});
