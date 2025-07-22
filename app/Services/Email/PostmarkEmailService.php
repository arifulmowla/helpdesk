<?php

namespace App\Services\Email;

use App\Data\SentEmailDto;
use App\Models\Contact;
use App\Models\Conversation;
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
    public function sendNewConversation(Conversation $conversation, string $subject, string $html, array $attachments = []): SentEmailDto
    {
        // Ensure contact is loaded
        if (!$conversation->relationLoaded('contact')) {
            $conversation->load('contact');
        }
        
        $contact = $conversation->contact;
        $messageId = $this->generateMessageId();
        $threadId = $this->generateThreadId($conversation->id);
        
        // Use the NewConversationMail mailable - now with conversation for threading
        $mailable = new NewConversationMail(
            contact: $contact,
            subject: $subject,
            htmlContent: $html,
            attachments: $attachments,
            messageId: $messageId,
            conversation: $conversation
        );
        
        Mail::send($mailable);
        
        return new SentEmailDto(
            message_id: $messageId,
            thread_id: $threadId,
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
        
        // Store message threading headers in database before sending
        $previousMessageIds = $conversation->messages()
            ->whereNotNull('message_id')
            ->where('id', '!=', $reply->id)
            ->orderBy('created_at')
            ->pluck('message_id')
            ->toArray();
            
        // Build references chain - start with thread ID, then add all previous message IDs
        $references = array_merge([$threadId], $previousMessageIds);
        $referencesString = implode(' ', array_unique($references));
        
        $reply->message_id = $messageId;
        $reply->in_reply_to = $threadId; // Reply to the thread root
        $reply->references = $referencesString;
        $reply->save();

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
     * Format: <uuid@domain.com> as per RFC standards
     */
    private function generateMessageId(): string
    {
        $domain = parse_url(config('app.url', 'localhost'), PHP_URL_HOST) ?: 'localhost';
        return '<' . Str::uuid() . '@' . $domain . '>';
    }
    
    /**
     * Generate a consistent thread ID based on conversation ID
     * Format: <thread-conversationId@domain.com> as per RFC standards
     */
    private function generateThreadId(string $conversationId): string
    {
        $domain = parse_url(config('app.url', 'localhost'), PHP_URL_HOST) ?: 'localhost';
        return '<thread-' . $conversationId . '@' . $domain . '>';
    }
}
