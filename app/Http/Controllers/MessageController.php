<?php

namespace App\Http\Controllers;

use App\Data\MessageData;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\Email\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
    public function __construct(
        private EmailService $emailService
    ) {}
    /**
     * Store a new message for a conversation.
     */
    public function store(Request $request, Conversation $conversation)
    {
        // Validate the request
        $validated = $request->validate([
            'type' => 'required|in:customer,agent,internal',
            'content' => 'required|string',
        ]);

        return DB::transaction(function () use ($validated, $conversation) {
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

            // Send email if this is an agent reply to a customer
            if ($validated['type'] === 'agent') {
                try {
                    // Ensure the message has the conversation relationship loaded
                    $message->setRelation('conversation', $conversation);
                    // Also ensure contact is loaded on conversation
                    if (!$conversation->relationLoaded('contact')) {
                        $conversation->load('contact');
                    }

                    $sentEmail = $this->emailService->sendReply($message);
                } catch (\Exception $e) {
                    // Load conversation and contact relationships for the DTO
                    $message->load('conversation.contact');
                    
                    return redirect()->back()->with([
                        'warning' => 'Message sent but email notification failed to send',
                        'message' => MessageData::fromModel($message),
                    ]);
                }
            }

            // Load conversation and contact relationships for the DTO
            $message->load('conversation.contact');
            
            // Return a redirect response for Inertia
            return redirect()->back()->with([
                'success' => 'Message sent successfully' . ($validated['type'] === 'agent' ? ' and email notification sent' : ''),
                'message' => MessageData::fromModel($message),
            ]);
        });
    }
}
