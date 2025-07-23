<?php

namespace App\Data;

use App\Enums\Priority;
use App\Enums\Status;
use App\Models\Conversation;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ConversationData extends Data
{
    public function __construct(
        public string $id,
        public string $subject,
        public array $status,
        public array $priority,
        public ?string $last_activity_at,
        public string $created_at,
        public bool $unread,
        public ?string $read_at,
        public ContactData $contact,
        public Lazy|array $messages,
    ) {
    }

    public static function fromModel(Conversation $conversation): self
    {
        return new self(
            id: $conversation->getKey(),
            subject: $conversation->getAttribute('subject'),
            status: $conversation->status->toArray(),
            priority: $conversation->priority->toArray(),
            last_activity_at: $conversation->last_activity_at?->format('Y-m-d H:i:s'),
            created_at: $conversation->created_at->format('Y-m-d H:i:s'),
            unread: $conversation->getAttribute('unread'),
            read_at: $conversation->read_at?->format('Y-m-d H:i:s'),
            contact: ContactData::fromModel($conversation->contact),
            messages: Lazy::whenLoaded('messages', $conversation, fn () => MessageData::collect($conversation->messages)),
        );
    }
}
