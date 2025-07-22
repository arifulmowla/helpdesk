<?php

namespace App\Http\Controllers;

use App\Data\MessageData;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    /**
     * Store a new message for a conversation.
     */
    public function store(Request $request, Conversation $conversation)
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
}
