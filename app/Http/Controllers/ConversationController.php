<?php

namespace App\Http\Controllers;

use App\Data\ConversationData;
use App\Data\ConversationFilterData;
use App\Data\MessageData;
use App\Filters\ConversationFilter;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;

class ConversationController extends Controller
{
    /**
     * Display the helpdesk with conversations and messages.
     * If no conversation ID is provided, it shows the first conversation or an empty state.
     */
    public function index(Request $request, Conversation $conversation = null)
    {
        // Get filter parameters
        $filters = $request->only(['status', 'priority', 'unread', 'search', 'contact_id']);
        
        // Get all conversations for the sidebar with filters applied
        $allConversations = Conversation::with('contact')
            ->filter($filters)
            ->orderBy('last_activity_at', 'desc')
            ->paginate(20);
        
        // If no specific conversation was requested, try to get the first one
        if ($conversation === null || !$conversation->exists) {
            $conversation = Conversation::orderBy('last_activity_at', 'desc')->first();
        }
        
        // Prepare the conversation data and messages if a conversation exists
        $conversationData = null;
        $messages = [];
        
        if ($conversation) {
            // Load the conversation with its contact and messages with nested conversation.contact
            $conversation->load('contact', 'messages.conversation.contact');
            $conversationData = ConversationData::from($conversation);
            $messages = MessageData::collect($conversation->messages);
        }
        
        return Inertia::render('helpdesk/Show', [
            'conversation' => $conversationData,
            'messages' => $messages,
            'conversations' => [
                'data' => ConversationData::collect($allConversations->items()),
                'links' => $allConversations->linkCollection()->toArray(),
                'meta' => [
                    'current_page' => $allConversations->currentPage(),
                    'from' => $allConversations->firstItem(),
                    'last_page' => $allConversations->lastPage(),
                    'per_page' => $allConversations->perPage(),
                    'to' => $allConversations->lastItem(),
                    'total' => $allConversations->total(),
                ],
            ],
            'filters' => [
                'current' => $filters,
                'options' => ConversationFilterData::create(),
            ],
        ]);
    }

    /**
     * Mark a conversation as read
     */
    public function markAsRead(Conversation $conversation): JsonResponse
    {
        $conversation->markAsRead();
        
        return response()->json([
            'success' => true,
            'message' => 'Conversation marked as read',
            'conversation' => ConversationData::from($conversation->fresh(['contact']))
        ]);
    }

    /**
     * Mark a conversation as unread
     */
    public function markAsUnread(Conversation $conversation): JsonResponse
    {
        $conversation->markAsUnread();
        
        return response()->json([
            'success' => true,
            'message' => 'Conversation marked as unread',
            'conversation' => ConversationData::from($conversation->fresh(['contact']))
        ]);
    }
}
