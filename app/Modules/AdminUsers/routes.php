<?php

use App\Modules\AdminUsers\Controllers\UsersController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->group(function () {
        Route::get('usuarios', [UsersController::class, 'index'])->name('admin.usuarios.index');
        Route::get('usuarios/create', [UsersController::class, 'create'])->name('admin.usuarios.create');
        Route::post('usuarios', [UsersController::class, 'store'])->name('admin.usuarios.store');
        Route::get('usuarios/{user}/edit', [UsersController::class, 'edit'])->name('admin.usuarios.edit');
        Route::put('usuarios/{user}', [UsersController::class, 'update'])->name('admin.usuarios.update');
        Route::patch('usuarios/{user}/toggle-active', [UsersController::class, 'toggleActive'])->name('admin.usuarios.toggle-active');
        Route::delete('usuarios/{user}', [UsersController::class, 'destroy'])->name('admin.usuarios.destroy');
    });

