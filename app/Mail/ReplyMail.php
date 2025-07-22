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
            htmlString: $this->reply->content,
        );
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
            
            // Enhanced Reply-To address with conversation and contact IDs for reliable threading
            $inboundEmail = $this->buildThreadedReplyToAddress();
            $message->getHeaders()->addTextHeader('Reply-To', $inboundEmail);
            
            $message->getHeaders()->addTextHeader('X-PM-Tag', 'reply');
        });
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
