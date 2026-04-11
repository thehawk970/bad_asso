<?php

use App\Http\Controllers\PlayerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    // Pages Inertia
    Route::get('/players', [PlayerController::class, 'index'])->name('players.index');
    Route::get('/players/{player}', [PlayerController::class, 'show'])->name('players.show');

    // Actions rapides (boutons dans la fiche joueur)
    Route::post('/payments/{payment}/validate', [PlayerController::class, 'validatePayment'])
        ->name('payments.validate');

    Route::post('/licenses/{license}/validate', [PlayerController::class, 'validateLicense'])
        ->name('licenses.validate');
});
