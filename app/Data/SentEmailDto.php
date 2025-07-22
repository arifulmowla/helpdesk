<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class SentEmailDto extends Data
{
    public function __construct(
        public string $message_id,
        public ?string $thread_id,
        public string $timestamp,
    ) {
    }
}
