<?php

namespace App\Services\InboundEmail;

use App\Data\InboundEmailDto;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PostmarkInboundEmailService implements InboundEmailService
{
    public function processInboundEmail(InboundEmailDto $emailData): Conversation
    {
        // Find or create contact
        $contact = $this->findOrCreateContact($emailData);
        
        // Find or create conversation
        $conversation = $this->findOrCreateConversation($emailData);
        
        // Update contact association if needed
        if ($conversation->contact_id !== $contact->id) {
            $conversation->update(['contact_id' => $contact->id]);
        }
        
        // Store attachments
        $attachmentPaths = $this->storeAttachments($emailData->attachments, $conversation);
        
        // Create message
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'type' => 'customer',
            'content' => $this->getEmailContent($emailData),
        ]);
        
        // Update conversation last activity
        $conversation->update([
            'last_activity_at' => Carbon::now(),
        ]);
        
        return $conversation;
    }
    
    public function findOrCreateConversation(InboundEmailDto $emailData): Conversation
    {
        // Try to find by thread ID first
        if ($emailData->in_reply_to) {
            $threadId = $this->extractThreadId($emailData->in_reply_to);
            if ($threadId) {
                $conversation = Conversation::where('id', $threadId)->first();
                if ($conversation) {
                    return $conversation;
                }
            }
        }
        
        // Try to find by references header
        if ($emailData->references) {
            $threadId = $this->extractThreadId($emailData->references);
            if ($threadId) {
                $conversation = Conversation::where('id', $threadId)->first();
                if ($conversation) {
                    return $conversation;
                }
            }
        }
        
        // Try to find by subject (Re: Subject)
        $cleanSubject = $this->cleanSubject($emailData->subject);
        $contact = $this->findOrCreateContact($emailData);
        
        $conversation = Conversation::where('contact_id', $contact->id)
            ->where('subject', $cleanSubject)
            ->where('status', '!=', 'closed')
            ->first();
            
        if ($conversation) {
            return $conversation;
        }
        
        // Create new conversation
        return Conversation::create([
            'contact_id' => $contact->id,
            'subject' => $cleanSubject,
            'status' => 'open',
            'priority' => 'medium',
            'last_activity_at' => Carbon::now(),
        ]);
    }
    
    public function storeAttachments(array $attachments, Conversation $conversation): array
    {
        $attachmentPaths = [];
        
        foreach ($attachments as $attachment) {
            if (empty($attachment['content'])) {
                continue;
            }
            
            // Generate filename
            $filename = Str::uuid() . '_' . $attachment['name'];
            $path = 'attachments/' . $conversation->id . '/' . $filename;
            
            // Decode base64 content
            $content = base64_decode($attachment['content']);
            
            // Store file
            Storage::disk('local')->put($path, $content);
            
            $attachmentPaths[] = [
                'name' => $attachment['name'],
                'path' => $path,
                'size' => $attachment['content_length'],
                'mime_type' => $attachment['content_type'],
            ];
        }
        
        return $attachmentPaths;
    }
    
    private function findOrCreateContact(InboundEmailDto $emailData): Contact
    {
        $contact = Contact::where('email', $emailData->from_email)->first();
        
        if (!$contact) {
            $contact = Contact::create([
                'email' => $emailData->from_email,
                'name' => $emailData->from_name ?: $this->extractNameFromEmail($emailData->from_email),
                'company' => null, // Could be extracted from email signature later
            ]);
        }
        
        return $contact;
    }
    
    private function getEmailContent(InboundEmailDto $emailData): string
    {
        // Prefer HTML content, fallback to text
        return !empty($emailData->html_body) ? $emailData->html_body : $emailData->text_body;
    }
    
    private function extractThreadId(string $header): ?string
    {
        // Extract thread ID from headers like <thread-01k0rneedmmaezcd4wwgjxx7np@domain.com>
        if (preg_match('/<thread-([^@]+)@/', $header, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    private function cleanSubject(string $subject): string
    {
        // Remove Re:, Fwd:, etc. from subject
        return preg_replace('/^(re:|fwd?:|aw:)\s*/i', '', trim($subject));
    }
    
    private function extractNameFromEmail(string $email): string
    {
        $parts = explode('@', $email);
        return ucwords(str_replace(['.', '_', '-'], ' ', $parts[0]));
    }
}
