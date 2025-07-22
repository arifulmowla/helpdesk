<?php

namespace App\Services\InboundEmail;

use App\Data\InboundEmailDto;
use App\Models\Conversation;

interface InboundEmailService
{
    /**
     * Process an inbound email and create/update conversation
     */
    public function processInboundEmail(InboundEmailDto $emailData): Conversation;
    
    /**
     * Find existing conversation by thread ID or subject
     */
    public function findOrCreateConversation(InboundEmailDto $emailData): Conversation;
    
    /**
     * Store attachments from inbound email
     */
    public function storeAttachments(array $attachments, Conversation $conversation): array;
}
