<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\RawEmail;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class PostmarkWebhookControllerSimple extends Controller
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

        return [
            'message_id' => $request->input('MessageID'),
            'from_email' => $fromParsed['email'],
            'from_name' => $fromParsed['name'],
            'subject' => $request->input('Subject'),
            'text_body' => $request->input('TextBody'),
            'html_body' => $request->input('HtmlBody'),
            'headers' => $request->input('Headers', []),
        ];
    }

    private function parseEmailAddress(string $emailHeader): array
    {
        if (preg_match('/^(.+?)\s*<(.+?)>$/', trim($emailHeader), $matches)) {
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
        $content = $this->getEmailContent($emailData);

        return Message::create([
            'conversation_id' => $conversation->id,
            'type' => 'customer',
            'content' => $content,
        ]);
    }

    private function getEmailContent(array $emailData): string
    {
        $htmlBody = $emailData['html_body'];
        $textBody = $emailData['text_body'];

        if (!empty($htmlBody)) {
            return $this->sanitizeHtml($htmlBody);
        }

        if (!empty($textBody)) {
            return nl2br(htmlspecialchars($textBody));
        }

        return 'Empty message';
    }

    private function sanitizeHtml(string $html): string
    {
        // Basic HTML sanitization
        $html = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $html);
        $html = preg_replace('/<style\b[^<]*(?:(?!<\/style>)<[^<]*)*<\/style>/mi', '', $html);
        $html = preg_replace('/<link[^>]*>/i', '', $html);
        
        return $html;
    }
}
