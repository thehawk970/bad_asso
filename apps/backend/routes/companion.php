<?php

declare(strict_types=1);

use App\Http\Controllers\CompanionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('companion')->name('companion.')->group(function () {
    // Pages Inertia
    Route::get('/order', [CompanionController::class, 'showOrderWizard'])->name('order');
    Route::get('/player/{player}', [CompanionController::class, 'showPlayer'])->name('player');

    // API JSON
    Route::post('/api/orders', [CompanionController::class, 'createOrder'])->name('api.orders.store');
    Route::get('/api/players/{player}', [CompanionController::class, 'getPlayer'])->name('api.players.show');
    Route::patch('/api/licenses/{license}/conditions', [CompanionController::class, 'updateLicenseConditions'])->name('api.licenses.conditions');
});
