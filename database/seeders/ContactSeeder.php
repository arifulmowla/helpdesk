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
        // Create 10 fake contacts
        $companies = ['Acme Inc.', 'Globex Corp', 'Initech', 'Massive Dynamic', 'Stark Industries', 'Wayne Enterprises', 'Umbrella Corp', 'Cyberdyne Systems', 'Soylent Corp', 'Hooli'];
        
        for ($i = 0; $i < 10; $i++) {
            Contact::create([
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
                'company' => fake()->randomElement($companies),
            ]);
        }
    }
}
