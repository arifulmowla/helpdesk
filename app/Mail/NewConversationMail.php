<?php

namespace App\Mail;

use App\Models\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewConversationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Contact $contact,
        public string $subject,
        public string $htmlContent,
        public $attachments = [],
        public string $messageId = ''
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [$this->contact->email],
            subject: $this->subject,
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
            if ($this->messageId) {
                $message->getHeaders()->addTextHeader('Message-ID', $this->messageId);
            }
            $message->getHeaders()->addTextHeader('X-PM-Tag', 'new-conversation');
        });
    }
}
