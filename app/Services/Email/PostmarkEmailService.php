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
        // For new conversations, just create a temp message and use sendReply
        $tempMessage = new Message([
            'conversation_id' => $conversation->id,
            'type' => 'support',
            'content' => $html,
        ]);
        
        return $this->sendReply($tempMessage, $attachments);
    }
    
    public function sendReply(Message $reply, array $attachments = []): SentEmailDto
    {
        // Load relationships
        if (!$reply->relationLoaded('conversation')) {
            $reply->load('conversation');
        }
        if (!$reply->conversation->relationLoaded('contact')) {
            $reply->conversation->load('contact');
        }
        
        $conversation = $reply->conversation;
        $contact = $conversation->contact;
        $messageId = $this->generateMessageId();
        
        // Simple message storage
        $reply->message_id = $messageId;
        $reply->save();

        // Send the reply
        $mailable = new ReplyMail(
            contact: $contact,
            conversation: $conversation,
            reply: $reply,
            attachments: $attachments,
            messageId: $messageId,
            threadId: ''
        );

        Mail::send($mailable);
        
        // Mark conversation as read since support agent is responding
        $conversation->markAsRead();
        
        return new SentEmailDto(
            message_id: $messageId,
            thread_id: $messageId,
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
