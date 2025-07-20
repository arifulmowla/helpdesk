<?php

namespace App\Data;

use App\Models\Contact;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ContactData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public ?string $company,
        public string $created_at,
    ) {
    }

    public static function fromModel(Contact $contact): self
    {
        return new self(
            id: $contact->id,
            name: $contact->name,
            email: $contact->email,
            company: $contact->company,
            created_at: $contact->created_at->format('Y-m-d H:i:s'),
        );
    }
}
