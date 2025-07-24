<?php

namespace App\Data;

use App\Models\Company;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class CompanyData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $email,
        public ?string $phone,
        public ?string $address,
        public ?string $website,
        public string $created_at,
        public string $updated_at,
    ) {
    }

    public static function fromModel(Company $company): self
    {
        return new self(
            id: $company->getKey(),
            name: $company->getAttribute('name'),
            email: $company->getAttribute('email'),
            phone: $company->getAttribute('phone'), 
            address: $company->getAttribute('address'),
            website: $company->getAttribute('website'),
            created_at: $company->created_at->format('Y-m-d H:i:s'),
            updated_at: $company->updated_at->format('Y-m-d H:i:s'),
        );
    }
}
