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
        
        // Return the created message as a DTO
        return response()->json([
            'message' => MessageData::fromModel($message),
        ]);
    }
    
    /**
     * Display the helpdesk index page with conversations.
     */
    public function index()
    {
        // Get paginated conversations with their contacts
        $conversations = Conversation::with('contact')
            ->orderBy('last_activity_at', 'desc')
            ->paginate(20);
        
        // Transform conversations to data objects
        $conversationData = collect($conversations->items())->map(function ($conversation) {
            return [
                'id' => $conversation->id,
                'subject' => $conversation->subject,
                'status' => $conversation->status,
                'priority' => $conversation->priority,
                'contact' => [
                    'id' => $conversation->contact->id,
                    'name' => $conversation->contact->name,
                    'email' => $conversation->contact->email,
                    'company' => $conversation->contact->company,
                ],
                'last_activity_at' => $conversation->last_activity_at,
                'created_at' => $conversation->created_at,
            ];
        });
        
        // Get all messages grouped by conversation
        $messages = [];
        foreach ($conversations->items() as $conversation) {
            $conversationMessages = Message::where('conversation_id', $conversation->id)
                ->orderBy('created_at')
                ->get();
            
            $messages[$conversation->id] = MessageData::collection($conversationMessages);
        }
        
        return Inertia::render('helpdesk/Index', [
            'conversations' => [
                'data' => $conversationData,
                'links' => $conversations->linkCollection(),
                'meta' => [
                    'current_page' => $conversations->currentPage(),
                    'from' => $conversations->firstItem(),
                    'last_page' => $conversations->lastPage(),
                    'path' => $conversations->path(),
                    'per_page' => $conversations->perPage(),
                    'to' => $conversations->lastItem(),
                    'total' => $conversations->total(),
                ],
            ],
            'messages' => $messages,
        ]);
    }
    
    /**
     * Display a specific conversation.
     */
    public function show(Conversation $conversation)
    {
        // Load the conversation with its contact and messages
        $conversation->load('contact', 'messages');
        
        return Inertia::render('helpdesk/Show', [
            'conversation' => ConversationData::from($conversation),
            'messages' => MessageData::collection($conversation->messages),
        ]);
    }
}
