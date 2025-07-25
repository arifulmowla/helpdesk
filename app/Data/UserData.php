<?php

namespace App\Data;

use App\Models\User;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class UserData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
    ) {
    }

    public static function fromModel(?User $user): ?self
    {
        if (!$user) {
            return null;
        }

        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
        );
    }
}
