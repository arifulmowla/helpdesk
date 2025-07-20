<?php

use App\Http\Controllers\HelpdeskController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::middleware(['auth:web', 'verified'])->group(function () {
    Route::get('/dashboard', fn () => Inertia::render('Dashboard'))->name('dashboard');

    // Helpdesk routes
    Route::prefix('helpdesk')->group(function () {
        Route::get('/', [HelpdeskController::class, 'index'])->name('helpdesk.index');
        Route::get('/{conversation}', [HelpdeskController::class, 'show'])->name('helpdesk.show');
        Route::get('/conversations/{conversation}/messages', [HelpdeskController::class, 'getMessages'])->name('helpdesk.messages.index');
        Route::post('/{conversation}/messages', [HelpdeskController::class, 'storeMessage'])->name('helpdesk.messages.store');
        Route::patch('/{conversation}/status', [HelpdeskController::class, 'updateStatus'])->name('helpdesk.status.update');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
