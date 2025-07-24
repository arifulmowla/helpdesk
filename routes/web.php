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
        Route::post('/conversations/{conversation}/read', [ConversationController::class, 'markAsRead'])->name('conversations.read');
        Route::post('/conversations/{conversation}/unread', [ConversationController::class, 'markAsUnread'])->name('conversations.unread');
        Route::post('/conversations/{conversation}/assign', [ConversationController::class, 'assign'])->name('conversations.assign');
    });
    
    // Contact and Company routes
    Route::prefix('contacts')->group(function () {
        Route::get('/', [App\Http\Controllers\ContactController::class, 'index'])->name('contacts.index');
        Route::get('/{contact}', [App\Http\Controllers\ContactController::class, 'show'])->name('contacts.show');
    });
    
    Route::prefix('companies')->group(function () {
        Route::get('/', [App\Http\Controllers\CompanyController::class, 'index'])->name('companies.index');
        Route::get('/{company}', [App\Http\Controllers\CompanyController::class, 'show'])->name('companies.show');
    });
    
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
