<?php

use App\Modules\AdminConfig\Controllers\ArlRiskClassController;
use App\Modules\AdminConfig\Controllers\CatalogController;
use App\Modules\SocialSecurity\Controllers\ContributionParameterController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->group(function () {
        Route::get('configuracion', [CatalogController::class, 'index'])->name('admin.configuracion.index');

        Route::get('configuracion/eps', [CatalogController::class, 'catalogIndex'])->name('admin.configuracion.eps.index')->defaults('type', 'eps');
        Route::get('configuracion/eps/create', [CatalogController::class, 'catalogCreate'])->name('admin.configuracion.eps.create')->defaults('type', 'eps');
        Route::post('configuracion/eps', [CatalogController::class, 'catalogStore'])->name('admin.configuracion.eps.store')->defaults('type', 'eps');
        Route::get('configuracion/eps/{id}/edit', [CatalogController::class, 'catalogEdit'])->name('admin.configuracion.eps.edit')->defaults('type', 'eps');
        Route::put('configuracion/eps/{id}', [CatalogController::class, 'catalogUpdate'])->name('admin.configuracion.eps.update')->defaults('type', 'eps');
        Route::delete('configuracion/eps/{id}', [CatalogController::class, 'catalogDestroy'])->name('admin.configuracion.eps.destroy')->defaults('type', 'eps');

        Route::get('configuracion/afps', [CatalogController::class, 'catalogIndex'])->name('admin.configuracion.afps.index')->defaults('type', 'afps');
        Route::get('configuracion/afps/create', [CatalogController::class, 'catalogCreate'])->name('admin.configuracion.afps.create')->defaults('type', 'afps');
        Route::post('configuracion/afps', [CatalogController::class, 'catalogStore'])->name('admin.configuracion.afps.store')->defaults('type', 'afps');
        Route::get('configuracion/afps/{id}/edit', [CatalogController::class, 'catalogEdit'])->name('admin.configuracion.afps.edit')->defaults('type', 'afps');
        Route::put('configuracion/afps/{id}', [CatalogController::class, 'catalogUpdate'])->name('admin.configuracion.afps.update')->defaults('type', 'afps');
        Route::delete('configuracion/afps/{id}', [CatalogController::class, 'catalogDestroy'])->name('admin.configuracion.afps.destroy')->defaults('type', 'afps');

        Route::get('configuracion/arps', [CatalogController::class, 'catalogIndex'])->name('admin.configuracion.arps.index')->defaults('type', 'arps');
        Route::get('configuracion/arps/create', [CatalogController::class, 'catalogCreate'])->name('admin.configuracion.arps.create')->defaults('type', 'arps');
        Route::post('configuracion/arps', [CatalogController::class, 'catalogStore'])->name('admin.configuracion.arps.store')->defaults('type', 'arps');
        Route::get('configuracion/arps/{id}/edit', [CatalogController::class, 'catalogEdit'])->name('admin.configuracion.arps.edit')->defaults('type', 'arps');
        Route::put('configuracion/arps/{id}', [CatalogController::class, 'catalogUpdate'])->name('admin.configuracion.arps.update')->defaults('type', 'arps');
        Route::delete('configuracion/arps/{id}', [CatalogController::class, 'catalogDestroy'])->name('admin.configuracion.arps.destroy')->defaults('type', 'arps');

        Route::get('configuracion/ccfs', [CatalogController::class, 'catalogIndex'])->name('admin.configuracion.ccfs.index')->defaults('type', 'ccfs');
        Route::get('configuracion/ccfs/create', [CatalogController::class, 'catalogCreate'])->name('admin.configuracion.ccfs.create')->defaults('type', 'ccfs');
        Route::post('configuracion/ccfs', [CatalogController::class, 'catalogStore'])->name('admin.configuracion.ccfs.store')->defaults('type', 'ccfs');
        Route::get('configuracion/ccfs/{id}/edit', [CatalogController::class, 'catalogEdit'])->name('admin.configuracion.ccfs.edit')->defaults('type', 'ccfs');
        Route::put('configuracion/ccfs/{id}', [CatalogController::class, 'catalogUpdate'])->name('admin.configuracion.ccfs.update')->defaults('type', 'ccfs');
        Route::delete('configuracion/ccfs/{id}', [CatalogController::class, 'catalogDestroy'])->name('admin.configuracion.ccfs.destroy')->defaults('type', 'ccfs');

        Route::get('configuracion/operadores-pago', [CatalogController::class, 'catalogIndex'])->name('admin.configuracion.operadores-pago.index')->defaults('type', 'operadores-pago');
        Route::get('configuracion/operadores-pago/create', [CatalogController::class, 'catalogCreate'])->name('admin.configuracion.operadores-pago.create')->defaults('type', 'operadores-pago');
        Route::post('configuracion/operadores-pago', [CatalogController::class, 'catalogStore'])->name('admin.configuracion.operadores-pago.store')->defaults('type', 'operadores-pago');
        Route::get('configuracion/operadores-pago/{id}/edit', [CatalogController::class, 'catalogEdit'])->name('admin.configuracion.operadores-pago.edit')->defaults('type', 'operadores-pago');
        Route::put('configuracion/operadores-pago/{id}', [CatalogController::class, 'catalogUpdate'])->name('admin.configuracion.operadores-pago.update')->defaults('type', 'operadores-pago');
        Route::delete('configuracion/operadores-pago/{id}', [CatalogController::class, 'catalogDestroy'])->name('admin.configuracion.operadores-pago.destroy')->defaults('type', 'operadores-pago');

        Route::get('configuracion/client-types', [CatalogController::class, 'catalogIndex'])->name('admin.configuracion.client-types.index')->defaults('type', 'client-types');
        Route::get('configuracion/client-types/create', [CatalogController::class, 'catalogCreate'])->name('admin.configuracion.client-types.create')->defaults('type', 'client-types');
        Route::post('configuracion/client-types', [CatalogController::class, 'catalogStore'])->name('admin.configuracion.client-types.store')->defaults('type', 'client-types');
        Route::get('configuracion/client-types/{id}/edit', [CatalogController::class, 'catalogEdit'])->name('admin.configuracion.client-types.edit')->defaults('type', 'client-types');
        Route::put('configuracion/client-types/{id}', [CatalogController::class, 'catalogUpdate'])->name('admin.configuracion.client-types.update')->defaults('type', 'client-types');
        Route::delete('configuracion/client-types/{id}', [CatalogController::class, 'catalogDestroy'])->name('admin.configuracion.client-types.destroy')->defaults('type', 'client-types');

        Route::get('configuracion/contributor-types', [CatalogController::class, 'catalogIndex'])->name('admin.configuracion.contributor-types.index')->defaults('type', 'contributor-types');
        Route::get('configuracion/contributor-types/create', [CatalogController::class, 'catalogCreate'])->name('admin.configuracion.contributor-types.create')->defaults('type', 'contributor-types');
        Route::post('configuracion/contributor-types', [CatalogController::class, 'catalogStore'])->name('admin.configuracion.contributor-types.store')->defaults('type', 'contributor-types');
        Route::get('configuracion/contributor-types/{id}/edit', [CatalogController::class, 'catalogEdit'])->name('admin.configuracion.contributor-types.edit')->defaults('type', 'contributor-types');
        Route::put('configuracion/contributor-types/{id}', [CatalogController::class, 'catalogUpdate'])->name('admin.configuracion.contributor-types.update')->defaults('type', 'contributor-types');
        Route::delete('configuracion/contributor-types/{id}', [CatalogController::class, 'catalogDestroy'])->name('admin.configuracion.contributor-types.destroy')->defaults('type', 'contributor-types');

        Route::get('configuracion/novelty-types', [CatalogController::class, 'catalogIndex'])->name('admin.configuracion.novelty-types.index')->defaults('type', 'novelty-types');
        Route::get('configuracion/novelty-types/create', [CatalogController::class, 'catalogCreate'])->name('admin.configuracion.novelty-types.create')->defaults('type', 'novelty-types');
        Route::post('configuracion/novelty-types', [CatalogController::class, 'catalogStore'])->name('admin.configuracion.novelty-types.store')->defaults('type', 'novelty-types');
        Route::get('configuracion/novelty-types/{id}/edit', [CatalogController::class, 'catalogEdit'])->name('admin.configuracion.novelty-types.edit')->defaults('type', 'novelty-types');
        Route::put('configuracion/novelty-types/{id}', [CatalogController::class, 'catalogUpdate'])->name('admin.configuracion.novelty-types.update')->defaults('type', 'novelty-types');
        Route::delete('configuracion/novelty-types/{id}', [CatalogController::class, 'catalogDestroy'])->name('admin.configuracion.novelty-types.destroy')->defaults('type', 'novelty-types');

        Route::get('configuracion/accounting-registries', [CatalogController::class, 'catalogIndex'])->name('admin.configuracion.accounting-registries.index')->defaults('type', 'accounting-registries');
        Route::get('configuracion/accounting-registries/create', [CatalogController::class, 'catalogCreate'])->name('admin.configuracion.accounting-registries.create')->defaults('type', 'accounting-registries');
        Route::post('configuracion/accounting-registries', [CatalogController::class, 'catalogStore'])->name('admin.configuracion.accounting-registries.store')->defaults('type', 'accounting-registries');
        Route::get('configuracion/accounting-registries/{id}/edit', [CatalogController::class, 'catalogEdit'])->name('admin.configuracion.accounting-registries.edit')->defaults('type', 'accounting-registries');
        Route::put('configuracion/accounting-registries/{id}', [CatalogController::class, 'catalogUpdate'])->name('admin.configuracion.accounting-registries.update')->defaults('type', 'accounting-registries');
        Route::delete('configuracion/accounting-registries/{id}', [CatalogController::class, 'catalogDestroy'])->name('admin.configuracion.accounting-registries.destroy')->defaults('type', 'accounting-registries');

        Route::get('configuracion/clases-riesgo-arl', [ArlRiskClassController::class, 'index'])->name('admin.configuracion.risk-classes.index');
        Route::get('configuracion/clases-riesgo-arl/create', [ArlRiskClassController::class, 'create'])->name('admin.configuracion.risk-classes.create');
        Route::post('configuracion/clases-riesgo-arl', [ArlRiskClassController::class, 'store'])->name('admin.configuracion.risk-classes.store');
        Route::get('configuracion/clases-riesgo-arl/{riskClass}/edit', [ArlRiskClassController::class, 'edit'])->name('admin.configuracion.risk-classes.edit');
        Route::put('configuracion/clases-riesgo-arl/{riskClass}', [ArlRiskClassController::class, 'update'])->name('admin.configuracion.risk-classes.update');
        Route::delete('configuracion/clases-riesgo-arl/{riskClass}', [ArlRiskClassController::class, 'destroy'])->name('admin.configuracion.risk-classes.destroy');

        Route::get('configuracion/contribution-parameters', [ContributionParameterController::class, 'index'])->name('admin.configuracion.contribution-parameters.index');
        Route::get('configuracion/contribution-parameters/create', [ContributionParameterController::class, 'create'])->name('admin.configuracion.contribution-parameters.create');
        Route::post('configuracion/contribution-parameters', [ContributionParameterController::class, 'store'])->name('admin.configuracion.contribution-parameters.store');
        Route::get('configuracion/contribution-parameters/{contributionParameter}/edit', [ContributionParameterController::class, 'edit'])->name('admin.configuracion.contribution-parameters.edit');
        Route::put('configuracion/contribution-parameters/{contributionParameter}', [ContributionParameterController::class, 'update'])->name('admin.configuracion.contribution-parameters.update');
        Route::delete('configuracion/contribution-parameters/{contributionParameter}', [ContributionParameterController::class, 'destroy'])->name('admin.configuracion.contribution-parameters.destroy');
    });
