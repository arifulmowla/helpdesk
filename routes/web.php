<?php

use App\Http\Controllers\ConversationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\KnowledgeBaseController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

// Postmark webhook for inbound emails (no auth required)
Route::post('/webhooks/postmark/inbound', [App\Http\Controllers\PostmarkWebhookController::class, 'handleInbound'])
    ->name('postmark.webhook.inbound');

// Public Knowledge Base routes
Route::prefix('knowledge-base')->group(function () {
    Route::get('/', [KnowledgeBaseController::class, 'index'])->name('knowledge-base.index');
    Route::get('/{slug}', [KnowledgeBaseController::class, 'show'])->name('knowledge-base.show');
});

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


    // Admin Knowledge Base routes (requires manage-knowledge-base permission)
    Route::prefix('admin/knowledge-base')->group(function () {
        // Article management
        Route::get('/', [App\Http\Controllers\Admin\KnowledgeBaseController::class, 'index'])->name('admin.knowledge-base.index');
        Route::get('/create', [App\Http\Controllers\Admin\KnowledgeBaseController::class, 'create'])->name('admin.knowledge-base.create');
        Route::post('/', [App\Http\Controllers\Admin\KnowledgeBaseController::class, 'store'])->name('admin.knowledge-base.store');
        Route::get('/{article}', [App\Http\Controllers\Admin\KnowledgeBaseController::class, 'show'])->name('admin.knowledge-base.show');
        Route::get('/{article}/edit', [App\Http\Controllers\Admin\KnowledgeBaseController::class, 'edit'])->name('admin.knowledge-base.edit');
        Route::put('/{article}', [App\Http\Controllers\Admin\KnowledgeBaseController::class, 'update'])->name('admin.knowledge-base.update');
        Route::delete('/{article}', [App\Http\Controllers\Admin\KnowledgeBaseController::class, 'destroy'])->name('admin.knowledge-base.destroy');

        // Restore and force delete for soft-deleted articles
        Route::post('/{id}/restore', [App\Http\Controllers\Admin\KnowledgeBaseController::class, 'restore'])->name('admin.knowledge-base.restore');
        Route::delete('/{id}/force-delete', [App\Http\Controllers\Admin\KnowledgeBaseController::class, 'forceDelete'])->name('admin.knowledge-base.force-delete');

        // File upload endpoints for TipTap editor
        Route::post('/upload-image', [App\Http\Controllers\Admin\KnowledgeBaseController::class, 'uploadImage'])->name('admin.knowledge-base.upload-image');
        Route::post('/upload-file', [App\Http\Controllers\Admin\KnowledgeBaseController::class, 'uploadFile'])->name('admin.knowledge-base.upload-file');
    });

    // Admin Tag Management routes (requires manage-knowledge-base permission)
    Route::middleware(['can:manage-knowledge-base'])->prefix('admin/tags')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\TagController::class, 'index'])->name('admin.tags.index');
        Route::post('/', [App\Http\Controllers\Admin\TagController::class, 'store'])->name('admin.tags.store');
        Route::put('/{tag}', [App\Http\Controllers\Admin\TagController::class, 'update'])->name('admin.tags.update');
        Route::delete('/{tag}', [App\Http\Controllers\Admin\TagController::class, 'destroy'])->name('admin.tags.destroy');
        Route::get('/list', [App\Http\Controllers\Admin\TagController::class, 'list'])->name('admin.tags.list');
    });

    // AI Answer Generation routes
    Route::prefix('ai')->group(function () {
        Route::post('/answer', [App\Http\Controllers\AIAnswerController::class, 'generate'])->name('ai.answer.generate');
        Route::post('/answer/stream', [App\Http\Controllers\AIAnswerController::class, 'stream'])->name('ai.answer.stream');
        Route::post('/sources', [App\Http\Controllers\AIAnswerController::class, 'sources'])->name('ai.sources');
    });

});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
