<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class InboundEmailDto extends Data
{
    public function __construct(
        public string $from_email,
        public string $from_name,
        public string $to_email,
        public string $subject,
        public string $html_body,
        public string $text_body,
        public string $message_id,
        public ?string $reply_to = null,
        public array $attachments = [],
        public ?string $in_reply_to = null,
        public ?string $references = null,
        public string $received_at = '',
    ) {
    }
}
