<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PostmarkWebhookController extends Controller
{
    /**
     * Handle inbound email webhook from Postmark.
     * 
     * @see https://postmarkapp.com/developer/user-guide/inbound/parse-an-email
     */
    public function handleInbound(Request $request): Response
    {
        try {
            // Log the incoming webhook for debugging
            Log::info('Postmark inbound webhook received', [
                'headers' => $request->headers->all(),
                'payload' => $request->all()
            ]);

            // Validate that this is a valid Postmark webhook
            $this->validatePostmarkWebhook($request);

            // Parse the email data
            $emailData = $this->parseInboundEmail($request);

            // Find or create contact
            $contact = $this->findOrCreateContact($emailData);

            // Find existing conversation or create new one
            $conversation = $this->findOrCreateConversation($contact, $emailData);

            // Create the message
            $message = $this->createMessage($conversation, $emailData);

            Log::info('Successfully processed inbound email', [
                'contact_id' => $contact->id,
                'conversation_id' => $conversation->id,
                'message_id' => $message->id,
                'subject' => $emailData['subject']
            ]);

            return response('OK', 200);

        } catch (\Exception $e) {
            Log::error('Failed to process Postmark inbound webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all()
            ]);

            // Return 200 to prevent Postmark from retrying
            // Log the error for manual investigation
            return response('Error logged', 200);
        }
    }

    /**
     * Validate the Postmark webhook request.
     */
    private function validatePostmarkWebhook(Request $request): void
    {
        // Basic validation - check for required Postmark fields
        $requiredFields = ['MessageID', 'From', 'Subject', 'TextBody', 'HtmlBody'];
        
        foreach ($requiredFields as $field) {
            if (!$request->has($field)) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }
    }

    /**
     * Parse the inbound email data from Postmark.
     */
    private function parseInboundEmail(Request $request): array
    {
        // Extract sender information
        $fromHeader = $request->input('From');
        $fromParsed = $this->parseEmailAddress($fromHeader);

        return [
            'message_id' => $request->input('MessageID'),
            'from_email' => $fromParsed['email'],
            'from_name' => $fromParsed['name'],
            'to' => $request->input('To'),
            'cc' => $request->input('Cc'),
            'bcc' => $request->input('Bcc'),
            'subject' => $request->input('Subject'),
            'text_body' => $request->input('TextBody'),
            'html_body' => $request->input('HtmlBody'),
            'reply_to' => $request->input('ReplyTo'),
            'date' => $request->input('Date'),
            'mailbox_hash' => $request->input('MailboxHash'),
            'attachments' => $request->input('Attachments', []),
            'headers' => $request->input('Headers', []),
        ];
    }

    /**
     * Parse an email address header (e.g., "John Doe <john@example.com>").
     */
    private function parseEmailAddress(string $emailHeader): array
    {
        // Handle format: "Name <email@domain.com>" or just "email@domain.com"
        if (preg_match('/^(.+?)\s*<(.+?)>$/', trim($emailHeader), $matches)) {
            return [
                'name' => trim($matches[1], '"'),
                'email' => trim($matches[2])
            ];
        }

        // Just email address without name
        return [
            'name' => null,
            'email' => trim($emailHeader)
        ];
    }

    /**
     * Find existing contact or create a new one.
     */
    private function findOrCreateContact(array $emailData): Contact
    {
        $contact = Contact::where('email', $emailData['from_email'])->first();

        if (!$contact) {
            $contact = Contact::create([
                'email' => $emailData['from_email'],
                'name' => $emailData['from_name'] ?? $emailData['from_email'],
                'company' => null, // Could be extracted from email domain if needed
            ]);

            Log::info('Created new contact', [
                'contact_id' => $contact->id,
                'email' => $contact->email,
                'name' => $contact->name
            ]);
        }

        return $contact;
    }

    /**
     * Find existing conversation or create a new one.
     */
    private function findOrCreateConversation(Contact $contact, array $emailData): Conversation
    {
        $subject = $emailData['subject'];

        // Try to find existing conversation by subject (for email threads)
        // Remove common reply prefixes
        $cleanSubject = preg_replace('/^(Re:|RE:|Fwd:|FWD:|Fw:)\s*/i', '', $subject);
        
        $conversation = Conversation::where('contact_id', $contact->id)
            ->where(function ($query) use ($subject, $cleanSubject) {
                $query->where('subject', $subject)
                      ->orWhere('subject', $cleanSubject);
            })
            ->where('status', '!=', 'closed') // Don't reopen closed conversations
            ->orderBy('last_activity_at', 'desc')
            ->first();

        if (!$conversation) {
            $conversation = Conversation::create([
                'contact_id' => $contact->id,
                'subject' => $cleanSubject,
                'status' => 'open',
                'priority' => 'medium',
                'last_activity_at' => now(),
            ]);

            Log::info('Created new conversation', [
                'conversation_id' => $conversation->id,
                'contact_id' => $contact->id,
                'subject' => $conversation->subject
            ]);
        } else {
            // Update last activity
            $conversation->update([
                'last_activity_at' => now(),
                'status' => $conversation->status === 'closed' ? 'open' : $conversation->status,
            ]);

            Log::info('Found existing conversation', [
                'conversation_id' => $conversation->id,
                'subject' => $conversation->subject
            ]);
        }

        return $conversation;
    }

    /**
     * Create a new message from the email.
     */
    private function createMessage(Conversation $conversation, array $emailData): Message
    {
        // Determine the best content to use (HTML if available, otherwise text)
        $content = $this->getEmailContent($emailData);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'type' => 'customer',
            'content' => $content,
        ]);

        Log::info('Created new message', [
            'message_id' => $message->id,
            'conversation_id' => $conversation->id,
            'type' => $message->type,
            'content_length' => strlen($content)
        ]);

        return $message;
    }

    /**
     * Get the best content representation from the email.
     */
    private function getEmailContent(array $emailData): string
    {
        $htmlBody = $emailData['html_body'];
        $textBody = $emailData['text_body'];

        // If we have HTML content, use it (assuming frontend can render HTML)
        if (!empty($htmlBody)) {
            // Clean up HTML - remove scripts, styles, etc. for security
            $cleanHtml = $this->sanitizeHtml($htmlBody);
            if (!empty($cleanHtml)) {
                return $cleanHtml;
            }
        }

        // Fall back to text body
        if (!empty($textBody)) {
            // Convert line breaks to HTML for better display
            return nl2br(htmlspecialchars($textBody));
        }

        return 'Empty message';
    }

    /**
     * Basic HTML sanitization (you might want to use a proper library like HTMLPurifier).
     */
    private function sanitizeHtml(string $html): string
    {
        // Remove potentially dangerous elements
        $html = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $html);
        $html = preg_replace('/<style\b[^<]*(?:(?!<\/style>)<[^<]*)*<\/style>/mi', '', $html);
        $html = preg_replace('/<link[^>]*>/i', '', $html);
        
        // Remove javascript: and data: URLs
        $html = preg_replace('/\s*javascript\s*:[^\'"]*/i', '', $html);
        $html = preg_replace('/\s*data\s*:[^\'"]*/i', '', $html);
        
        return $html;
    }
}
