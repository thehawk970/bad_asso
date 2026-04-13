<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

// Route::inertia('welcome', 'welcome', [
//    'canRegister' => Features::enabled(Features::registration()),
// ])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('/', 'old/welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ])->name('home');
});

require __DIR__.'/settings.php';
require __DIR__.'/players.php';
