<?php

namespace Database\Seeders;

use App\Models\Contact;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a single contact with specific email
        Contact::create([
            'name' => 'Christian Bottelet',
            'email' => 'cbottelet@gmail.com',
            'company' => 'Test Company',
        ]);
    }
}
