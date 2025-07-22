<?php

namespace App\Services\Email;

use App\Data\SentEmailDto;
use App\Models\Contact;
use App\Models\Message;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Message as MailMessage;
use Illuminate\Support\Str;
use App\Mail\NewConversationMail;
use App\Mail\ReplyMail;
use Carbon\Carbon;

class PostmarkEmailService implements EmailService
{
    public function sendNewConversation(Contact $contact, string $subject, string $html, array $attachments = []): SentEmailDto
    {
        $messageId = $this->generateMessageId();
        
        // Use the NewConversationMail mailable
        $mailable = new NewConversationMail(
            contact: $contact,
            subject: $subject,
            htmlContent: $html,
            attachments: $attachments,
            messageId: $messageId
        );
        
        Mail::send($mailable);
        
        return new SentEmailDto(
            message_id: $messageId,
            thread_id: null, // New conversation doesn't have a thread ID yet
            timestamp: Carbon::now()->toISOString()
        );
    }
    
    public function sendReply(Message $reply, array $attachments = []): SentEmailDto
    {
        // Ensure relationships are loaded but don't reload if already loaded
        if (!$reply->relationLoaded('conversation')) {
            $reply->load('conversation');
        }
        if (!$reply->conversation->relationLoaded('contact')) {
            $reply->conversation->load('contact');
        }
        
        $conversation = $reply->conversation;
        $contact = $conversation->contact;
        $messageId = $this->generateMessageId();
        
        // Generate thread ID based on conversation ID for consistent threading
        $threadId = $this->generateThreadId($conversation->id);
        
        // Use the ReplyMail mailable
        $mailable = new ReplyMail(
            contact: $contact,
            conversation: $conversation,
            reply: $reply,
            attachments: $attachments,
            messageId: $messageId,
            threadId: $threadId
        );

        Mail::send($mailable);
        
        return new SentEmailDto(
            message_id: $messageId,
            thread_id: $threadId,
            timestamp: Carbon::now()->toISOString()
        );
    }
    
    /**
     * Generate a unique message ID for email tracking
     */
    private function generateMessageId(): string
    {
        return '<' . Str::uuid() . '@' . config('app.url', 'localhost') . '>';
    }
    
    /**
     * Generate a consistent thread ID based on conversation ID
     */
    private function generateThreadId(string $conversationId): string
    {
        return '<thread-' . $conversationId . '@' . config('app.url', 'localhost') . '>';
    }
}
