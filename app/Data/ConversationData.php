<?php

namespace App\Data;

use App\Models\Conversation;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ConversationData extends Data
{
    public function __construct(
        public string $id,
        public string $subject,
        public string $status,
        public string $priority,
        public ?string $last_activity_at,
        public string $created_at,
        public ContactData $contact,
        public Lazy|array $messages,
    ) {
    }

    public static function fromModel(Conversation $conversation): self
    {
        return new self(
            id: $conversation->getKey(),
            subject: $conversation->getAttribute('subject'),
            status: $conversation->getAttribute('status'),
            priority: $conversation->getAttribute('priority'),
            last_activity_at: $conversation->last_activity_at?->format('Y-m-d H:i:s'),
            created_at: $conversation->created_at->format('Y-m-d H:i:s'),
            contact: ContactData::fromModel($conversation->contact),
            messages: Lazy::whenLoaded('messages', $conversation, fn () => MessageData::collection($conversation->messages)),
        );
    }
}
