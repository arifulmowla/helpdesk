<?php

namespace App\Services\Email;

use App\Data\SentEmailDto;
use App\Models\Contact;
use App\Models\Message;

interface EmailService
{
    public function sendNewConversation(Contact $contact, string $subject, string $html, array $attachments = []): SentEmailDto;
    
    public function sendReply(Message $reply, array $attachments = []): SentEmailDto;
}
