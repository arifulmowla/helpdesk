<?php

namespace App\Mail;

use App\Models\Contact;
use App\Models\Conversation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewConversationMail extends Mailable
{
    use Queueable, SerializesModels;
    
    public string $emailSubject;
    public string $htmlContent;
    public array $emailAttachments;
    public string $messageId;
    public ?Conversation $conversation;

    public function __construct(
        public Contact $contact,
        $subject,
        $htmlContent,
        $attachments = [],
        $messageId = '',
        ?Conversation $conversation = null
    ) {
        $this->emailSubject = (string)$subject;
        $this->htmlContent = (string)$htmlContent;
        $this->emailAttachments = $attachments;
        $this->messageId = (string)$messageId;
        $this->conversation = $conversation;
    }

    public function envelope(): Envelope
    {
        // Enhanced Reply-To address for threading (if conversation is available)
        $replyToAddress = $this->conversation ? 
            $this->buildThreadedReplyToAddress() : 
            config('mail.postmark_inbound_address', '83321b7bb57f3cf0c5c7815d938bd5dc@inbound.postmarkapp.com');
        
        return new Envelope(
            to: [$this->contact->email],
            subject: $this->emailSubject,
            replyTo: [$replyToAddress],
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->htmlContent,
        );
    }

    public function attachments(): array
    {
        $attachmentArray = [];
        
        foreach ($this->emailAttachments as $attachment) {
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
            if ($this->messageId) {
                $message->getHeaders()->addTextHeader('Message-ID', $this->messageId);
            }
            
            // Enhanced Reply-To address for threading (if conversation is available)
            if ($this->conversation) {
                $inboundEmail = $this->buildThreadedReplyToAddress();
                $message->getHeaders()->addTextHeader('Reply-To', $inboundEmail);
            } else {
                // Fallback to basic inbound address
                $baseAddress = config('mail.postmark_inbound_address', '83321b7bb57f3cf0c5c7815d938bd5dc@inbound.postmarkapp.com');
                $message->getHeaders()->addTextHeader('Reply-To', $baseAddress);
            }
            
            $message->getHeaders()->addTextHeader('X-PM-Tag', 'new-conversation');
        });
    }
    
    /**
     * Build enhanced Reply-To address with conversation and contact IDs
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
