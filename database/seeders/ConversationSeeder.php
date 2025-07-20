<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Conversation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ConversationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all contacts
        $contacts = Contact::all();
        
        if ($contacts->isEmpty()) {
            $this->command->info('No contacts found. Please run ContactSeeder first.');
            return;
        }
        
        // Create 20 fake conversations
        $subjects = [
            'Need help with my account',
            'Payment issue',
            'How do I reset my password?',
            'Feature request',
            'Bug report',
            'Subscription inquiry',
            'Billing question',
            'Technical support needed',
            'Account access problem',
            'Product feedback',
        ];
        
        $statuses = ['open', 'pending', 'closed'];
        $priorities = ['low', 'medium', 'high'];
        
        for ($i = 0; $i < 20; $i++) {
            $contact = $contacts->random();
            $createdAt = Carbon::now()->subDays(rand(1, 30))->subHours(rand(1, 24));
            
            // Ensure $contact is a Contact model instance
            if ($contact instanceof Contact) {
                Conversation::create([
                    'contact_id' => $contact->id,
                    'subject' => fake()->randomElement($subjects),
                    'status' => fake()->randomElement($statuses),
                    'priority' => fake()->randomElement($priorities),
                    'last_activity_at' => $createdAt->addHours(rand(1, 48)),
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }
        }
    }
}
