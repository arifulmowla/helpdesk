<?php

namespace App\Http\Controllers;

use App\Data\ConversationData;
use App\Data\MessageData;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class HelpdeskController extends Controller
{
    /**
     * Update the status of a conversation.
     */
    public function updateStatus(Request $request, Conversation $conversation)
    {
        // Validate the request
        $validated = $request->validate([
            'status' => 'required|in:open,pending,closed',
        ]);
        
        // Update the conversation status
        $conversation->status = $validated['status'];
        $conversation->last_activity_at = now();
        $conversation->save();
        
        // For Inertia requests, return a redirect response
        if ($request->wantsJson()) {
            return redirect()->back()
                ->with('success', 'Status updated successfully');
        }
        
        return back()->with('success', 'Status updated successfully');
    }
    
    /**
     * Store a new message for a conversation.
     */
    public function storeMessage(Request $request, Conversation $conversation)
    {
        // Validate the request
        $validated = $request->validate([
            'type' => 'required|in:customer,support,internal',
            'content' => 'required|string',
        ]);
        
        // Create the message
        $message = new Message([
            'conversation_id' => $conversation->getKey(),
            'type' => $validated['type'],
            'content' => $validated['content'],
        ]);
        
        $message->save();
        
        // Update the conversation's last activity timestamp
        $conversation->last_activity_at = now();
        $conversation->save();
        
        // Return a redirect response for Inertia
        return redirect()->back()->with([
            'success' => 'Message sent successfully',
            'message' => MessageData::fromModel($message),
        ]);
    }
    
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
