<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\RawEmail;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class PostmarkWebhookController extends Controller
{
    public function handleInbound(Request $request): Response
    {
        try {
            Log::info('Postmark webhook received', $request->all());
            
            $emailData = $this->parseEmailData($request);
            $contact = $this->findOrCreateContact($emailData);
            $conversation = $this->findOrCreateConversation($contact, $emailData);
            $message = $this->createMessage($conversation, $emailData);
            
            // Save raw email
            RawEmail::create([
                'message_id' => $emailData['message_id'],
                'message_id_ref' => $message->id,
                'headers' => $emailData['headers'],
                'payload' => $emailData,
                'raw_content' => $emailData['text_body'] ?? $emailData['html_body'],
            ]);

            return response('OK', 200);

        } catch (\Exception $e) {
            Log::error('Webhook error: ' . $e->getMessage(), $request->all());
            return response('Error logged', 200);
        }
    }

    private function parseEmailData(Request $request): array
    {
        $fromHeader = $request->input('From');
        $fromParsed = $this->parseEmailAddress($fromHeader);
        $headers = $request->input('Headers', []);

        return [
            'message_id' => $request->input('MessageID'),
            'from_email' => $fromParsed['email'],
            'from_name' => $fromParsed['name'],
            'subject' => $request->input('Subject'),
            'text_body' => $request->input('TextBody'),
            'html_body' => $request->input('HtmlBody'),
            'headers' => $headers,
            'in_reply_to' => $this->extractHeaderValue($headers, 'In-Reply-To'),
            'references' => $this->extractHeaderValue($headers, 'References'),
        ];
    }

    private function parseEmailAddress(?string $emailHeader): array
    {
        if (empty($emailHeader)) {
            return [
                'name' => null,
                'email' => 'unknown@example.com'
            ];
        }

        if (preg_match('/^(.+?)\s*\<(.+?)\>$/', trim($emailHeader), $matches)) {
            return [
                'name' => trim($matches[1], '"'),
                'email' => trim($matches[2])
            ];
        }

        return [
            'name' => null,
            'email' => trim($emailHeader)
        ];
    }

    private function findOrCreateContact(array $emailData): Contact
    {
        $contact = Contact::where('email', $emailData['from_email'])->first();

        if (!$contact) {
            $contact = Contact::create([
                'email' => $emailData['from_email'],
                'name' => $emailData['from_name'] ?? $emailData['from_email'],
                'company' => null,
            ]);
        }

        return $contact;
    }

    private function findOrCreateConversation(Contact $contact, array $emailData): Conversation
    {
        // Try to find conversation by threading headers first
        $conversation = $this->findConversationByThreading($contact, $emailData);
        
        if ($conversation) {
            // Update last activity for existing conversation
            $conversation->update([
                'last_activity_at' => now(),
                'status' => $conversation->status === 'closed' ? 'open' : $conversation->status,
            ]);
            return $conversation;
        }
        
        // Fallback to subject-based matching
        $subject = $emailData['subject'];
        $cleanSubject = preg_replace('/^(Re:|RE:|Fwd:|FWD:|Fw:)\s*/i', '', $subject);
        
        $conversation = Conversation::where('contact_id', $contact->id)
            ->where('subject', $cleanSubject)
            ->where('status', '!=', 'closed')
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
        } else {
            $conversation->update([
                'last_activity_at' => now(),
                'status' => $conversation->status === 'closed' ? 'open' : $conversation->status,
            ]);
        }

        return $conversation;
    }

    private function createMessage(Conversation $conversation, array $emailData): Message
    {
        // Store raw body without any cleaning applied
        $content = $this->getRawEmailContent($emailData);

        return Message::create([
            'conversation_id' => $conversation->id,
            'type' => 'customer',
            'content' => $content,
            'message_id' => $emailData['message_id'],
            'in_reply_to' => $emailData['in_reply_to'],
            'references' => $emailData['references'],
        ]);
    }

    private function getRawEmailContent(array $emailData): string
    {
        $htmlBody = $emailData['html_body'];
        $textBody = $emailData['text_body'];

        // Return raw content without any cleaning/sanitization
        if (!empty($htmlBody)) {
            return $htmlBody;
        }

        if (!empty($textBody)) {
            return $textBody;
        }

        return 'Empty message';
    }

    /**
     * Extract header value from Postmark headers array
     */
    private function extractHeaderValue(array $headers, string $headerName): ?string
    {
        foreach ($headers as $header) {
            if (isset($header['Name']) && strtolower($header['Name']) === strtolower($headerName)) {
                return $header['Value'] ?? null;
            }
        }
        return null;
    }

    /**
     * Find conversation by email threading headers (In-Reply-To, References)
     */
    private function findConversationByThreading(Contact $contact, array $emailData): ?Conversation
    {
        $inReplyTo = $emailData['in_reply_to'];
        $references = $emailData['references'];
        
        if (empty($inReplyTo) && empty($references)) {
            return null;
        }
        
        // Extract thread ID from In-Reply-To or References
        $threadId = $this->extractThreadId($inReplyTo) ?: $this->extractThreadId($references);
        
        if (!$threadId) {
            return null;
        }
        
        // Find conversation by thread ID pattern
        return Conversation::where('id', $threadId)
            ->where('contact_id', $contact->id)
            ->first();
    }

    /**
     * Extract conversation/thread ID from threading header
     * Looks for patterns like <thread-01k0s33nacy20e3hc0mkpp31ax@domain.com>
     */
    private function extractThreadId(?string $header): ?string
    {
        if (empty($header)) {
            return null;
        }
        
        // Match pattern: <thread-{conversationId}@domain.com>
        if (preg_match('/<thread-([^@]+)@/', $header, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
}
