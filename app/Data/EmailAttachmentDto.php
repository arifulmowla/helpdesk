<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class EmailAttachmentDto extends Data
{
    public function __construct(
        public string $name,
        public string $content_type,
        public int $content_length,
        public string $content,
        public string $content_id = '',
    ) {
    }
}
