<?php

use App\Http\Controllers\ConversationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\StatusController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

// Postmark webhook for inbound emails (no auth required)
Route::post('/webhooks/postmark/inbound', [App\Http\Controllers\PostmarkWebhookController::class, 'handleInbound'])
    ->name('postmark.webhook.inbound');

Route::middleware(['auth:web', 'verified'])->group(function () {
    Route::get('/dashboard', fn () => Inertia::render('Dashboard'))->name('dashboard');

    // Helpdesk routes
    Route::prefix('helpdesk')->group(function () {
        Route::get('/', [ConversationController::class, 'index'])->name('helpdesk.index');
        Route::get('/{conversation}', [ConversationController::class, 'index'])->name('helpdesk.show');
        Route::post('/{conversation}/messages', [MessageController::class, 'store'])->name('helpdesk.messages.store');
        Route::patch('/{conversation}/status', [StatusController::class, 'update'])->name('helpdesk.status.update');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
