<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Company;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a test company first
        $company = Company::create([
            'name' => 'Test Company',
            'email' => 'info@testcompany.com',
            'phone' => '+1-555-123-4567',
            'website' => 'https://testcompany.com',
        ]);

        // Create a single contact with specific email
        Contact::create([
            'name' => 'Christian Bottelet',
            'email' => 'cbottelet@gmail.com',
            'company_id' => $company->id,
        ]);
    }
}
