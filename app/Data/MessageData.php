<?php

namespace App\Data;

use App\Models\Message;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class MessageData extends Data
{
    public function __construct(
        public string $id,
        public string $conversation_id,
        public string $type,
        public string $content,
        public string $created_at,
    ) {
    }

    public static function fromModel(Message $message): self
    {
        return new self(
            id: $message->id,
            conversation_id: $message->conversation_id,
            type: $message->type,
            content: $message->content,
            created_at: $message->created_at->format('Y-m-d H:i:s'),
        );
    }
    
    /**
     * Create a collection of MessageData from a collection of Message models.
     *
     * @param Collection $messages
     * @return array
     */
    public static function collection(Collection $messages): array
    {
        return $messages->map(function (Message $message) {
            return self::fromModel($message);
        })->toArray();
    }
}
