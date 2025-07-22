<?php

namespace App\Http\Controllers;

use App\Data\ConversationData;
use App\Data\MessageData;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ConversationController extends Controller
{
    /**
     * Display the helpdesk with conversations and messages.
     * If no conversation ID is provided, it shows the first conversation or an empty state.
     */
    public function index(Conversation $conversation = null)
    {
        // Get all conversations for the sidebar
        $allConversations = Conversation::with('contact')
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
            // Load the conversation with its contact and messages
            $conversation->load('contact', 'messages');
            $conversationData = ConversationData::from($conversation);
            $messages = MessageData::collect($conversation->messages);
        }
        
        return Inertia::render('helpdesk/Show', [
            'conversation' => $conversationData,
            'messages' => $messages,
            'conversations' => ConversationData::collect($allConversations),
        ]);
    }
}
