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
        public ?array $company,
    ) {
    }

    public static function fromModel(Contact $contact): self
    {
        return new self(
            id: $contact->getKey(),
            name: $contact->name,
            email: $contact->email,
            company: $contact->company ? [
                'id' => $contact->company->id,
                'name' => $contact->company->name,
            ] : null,
        );
    }
}
