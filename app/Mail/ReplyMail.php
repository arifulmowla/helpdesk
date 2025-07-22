<?php

namespace App\Mail;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Contact $contact,
        public Conversation $conversation,
        public Message $reply,
        public $attachments = [],
        public string $messageId = '',
        public string $threadId = ''
    ) {
    }

    public function envelope(): Envelope
    {
        // Build enhanced Reply-To address with conversation and contact IDs
        $inboundEmail = $this->buildThreadedReplyToAddress();
        
        return new Envelope(
            to: [$this->contact->email],
            subject: 'Re: ' . $this->conversation->subject,
            replyTo: [$inboundEmail],
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->buildThreadedEmailContent(),
        );
    }

    /**
     * Build the threaded email content with full conversation history
     */
    private function buildThreadedEmailContent(): string
    {
        // Start with the new reply content
        $newReplyContent = $this->reply->content;
        
        // Get all previous messages in this conversation (excluding the current reply)
        $previousMessages = $this->conversation
            ->messages()
            ->where('id', '!=', $this->reply->id)
            ->orderBy('created_at', 'desc')
            ->get();
        
        if ($previousMessages->isEmpty()) {
            // No previous messages, just return the reply content
            return $newReplyContent;
        }
        
        // Build the threaded email content
        $threadContent = $newReplyContent;
        
        // Add separator and previous messages
        $threadContent .= "\n\n";
        $threadContent .= '<div style="border-top: 1px solid #ccc; padding-top: 20px; margin-top: 20px;">';
        
        // Get the most recent previous message for the "On [date], [person] wrote:" header
        $lastMessage = $previousMessages->first();
        $senderName = $lastMessage->type === 'customer' 
            ? $this->contact->name 
            : 'Support Team';
        $senderEmail = $lastMessage->type === 'customer' 
            ? $this->contact->email 
            : config('mail.from.address', 'support@example.com');
        $messageDate = $lastMessage->created_at->format('M j, Y \\a\\t g:i A');
        
        // Add thread header
        $threadContent .= '<p style="color: #666; font-size: 14px; margin-bottom: 15px;">';
        $threadContent .= "On {$messageDate}, {$senderName} &lt;{$senderEmail}&gt; wrote:";
        $threadContent .= '</p>';
        
        // Add all previous messages as quoted content
        $threadContent .= '<blockquote style="border-left: 3px solid #ccc; margin: 0 0 15px 0; padding-left: 15px; color: #666;">';
        
        foreach ($previousMessages as $message) {
            $messageSender = $message->type === 'customer' 
                ? $this->contact->name 
                : 'Support Team';
            $messageTime = $message->created_at->format('M j, Y \\a\\t g:i A');
            
            $threadContent .= '<div style="margin-bottom: 15px;">';
            $threadContent .= '<div style="font-weight: bold; font-size: 12px; color: #888; margin-bottom: 5px;">';
            $threadContent .= "{$messageSender} - {$messageTime}";
            $threadContent .= '</div>';
            $threadContent .= '<div style="padding-left: 10px;">';
            $threadContent .= $message->content;
            $threadContent .= '</div>';
            $threadContent .= '</div>';
        }
        
        $threadContent .= '</blockquote>';
        $threadContent .= '</div>';
        
        return $threadContent;
    }

    public function attachments(): array
    {
        $attachmentArray = [];
        
        foreach ($this->attachments as $attachment) {
            if (is_array($attachment) && isset($attachment['path'])) {
                $attachmentArray[] = \Illuminate\Mail\Mailables\Attachment::fromPath($attachment['path'])
                    ->withMime($attachment['mime'] ?? null)
                    ->as($attachment['name'] ?? null);
            } elseif (is_string($attachment)) {
                $attachmentArray[] = \Illuminate\Mail\Mailables\Attachment::fromPath($attachment);
            }
        }
        
        return $attachmentArray;
    }

    public function build()
    {
        return $this->withSwiftMessage(function ($message) {
            // Set Message-ID for this email
            if ($this->messageId) {
                $message->getHeaders()->addTextHeader('Message-ID', $this->messageId);
            }
            
            // Set In-Reply-To (reply to thread root)
            if ($this->threadId) {
                $message->getHeaders()->addTextHeader('In-Reply-To', $this->threadId);
            }
            
            // Set References header with full chain of message IDs
            $references = $this->buildReferencesHeader();
            if ($references) {
                $message->getHeaders()->addTextHeader('References', $references);
            }
            
            // Enhanced Reply-To address with conversation and contact IDs for reliable threading
            $inboundEmail = $this->buildThreadedReplyToAddress();
            $message->getHeaders()->addTextHeader('Reply-To', $inboundEmail);
            
            $message->getHeaders()->addTextHeader('X-PM-Tag', 'reply');
        });
    }
    
    /**
     * Build References header from all previous message IDs in the conversation
     */
    private function buildReferencesHeader(): string
    {
        // Use the references already stored in the reply model
        if (!empty($this->reply->references)) {
            return $this->reply->references;
        }
        
        // Fallback: just use thread ID if no references stored
        return $this->threadId ?: '';
    }
    
    /**
     * Build enhanced Reply-To address with conversation and contact IDs
     * Format: reply+contactID_conversationID@inbound.postmarkapp.com
     */
    private function buildThreadedReplyToAddress(): string
    {
        $baseAddress = config('mail.postmark_inbound_address', '83321b7bb57f3cf0c5c7815d938bd5dc@inbound.postmarkapp.com');
        
        // Split the email address
        [$localPart, $domain] = explode('@', $baseAddress, 2);
        
        // Add contact and conversation IDs to the local part using + addressing
        $threadingInfo = $this->contact->id . '_' . $this->conversation->id;
        $enhancedLocalPart = $localPart . '+' . $threadingInfo;
        
        return $enhancedLocalPart . '@' . $domain;
    }
}
