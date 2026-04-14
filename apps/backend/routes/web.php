<?php

declare(strict_types=1);

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');
});

require __DIR__.'/settings.php';
require __DIR__.'/players.php';
require __DIR__.'/companion.php';
