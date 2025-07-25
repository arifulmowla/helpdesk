<?php

namespace App\Http\Controllers;

use App\Data\MessageData;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\Email\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
    public function __construct(
        private EmailService $emailService
    ) {}
    public function store(Request $request, Conversation $conversation)
    {
        $validated = $request->validate([
            'type' => 'required|in:customer,agent,internal',
            'content' => 'required|string',
        ]);

        return DB::transaction(function () use ($validated, $conversation) {
            $message = Message::create([
                'conversation_id' => $conversation->getKey(),
                'type' => $validated['type'],
                'content' => $validated['content'],
            ]);

            $conversation->touch('last_activity_at');
            
            $emailSent = $this->handleEmailNotification($message, $conversation, $validated['type']);
            $message->load('conversation.contact');
            
            $successMessage = 'Message sent successfully';
            if ($validated['type'] === 'agent') {
                $successMessage .= $emailSent ? ' and email notification sent' : ' but email notification failed';
            }
            
            return redirect()->back()->with([
                $emailSent === false ? 'warning' : 'success' => $successMessage,
                'message' => MessageData::fromModel($message),
            ]);
        });
    }
    
    private function handleEmailNotification(Message $message, Conversation $conversation, string $type): ?bool
    {
        if ($type !== 'agent') {
            return null;
        }
        
        try {
            $message->setRelation('conversation', $conversation);
            if (!$conversation->relationLoaded('contact')) {
                $conversation->load('contact');
            }
            
            $this->emailService->sendReply($message);
            return true;
        } catch (\Exception $e) {
            Log::warning('Failed to send email notification: ' . $e->getMessage());
            return false;
        }
    }
}
