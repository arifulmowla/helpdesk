<?php

use App\Http\Controllers\HelpdeskController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Helpdesk routes
Route::prefix('helpdesk')->name('helpdesk.')->middleware(['auth'])->group(function () {
    Route::get('/', [HelpdeskController::class, 'index'])->name('index');
    Route::get('/{conversation}', [HelpdeskController::class, 'show'])->name('show');
    Route::post('/{conversation}/messages', [HelpdeskController::class, 'storeMessage'])->name('messages.store');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
