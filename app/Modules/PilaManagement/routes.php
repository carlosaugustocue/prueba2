<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:atencion,supervisor,agent,admin,seguridad_social,cartera'])->group(function () {
    // Sprint 1: solo estructura base (sin UI aún).
});

