<?php

namespace App\Data;

use App\Enums\Type;
use App\Models\Message;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class MessageData extends Data
{
    public function __construct(
        public string  $id,
        public string  $conversation_id,
        public Type    $type,
        public string  $content,
        public string  $created_at,
        public ?string $message_owner_name = null,
    ) {
    }

    public static function fromModel(Message $message): self
    {
        $messageOwnerName = null;
        if ($message->type === 'customer' && $message->conversation && $message->conversation->contact) {
            $messageOwnerName = $message->conversation->contact->name;
        }

        return new self(
            id: $message->id,
            conversation_id: $message->conversation_id,
            type: $message->type,
            content: $message->content,
            created_at: $message->created_at->format('Y-m-d H:i:s'),
            message_owner_name: $messageOwnerName,
        );
    }
}
