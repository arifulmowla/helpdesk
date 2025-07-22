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
        return new Envelope(
            to: [$this->contact->email],
            subject: 'Re: ' . $this->conversation->subject,
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
            if ($this->messageId) {
                $message->getHeaders()->addTextHeader('Message-ID', $this->messageId);
            }
            if ($this->threadId) {
                $message->getHeaders()->addTextHeader('In-Reply-To', $this->threadId);
                $message->getHeaders()->addTextHeader('References', $this->threadId);
            }
            $message->getHeaders()->addTextHeader('X-PM-Tag', 'reply');
        });
    }
}
