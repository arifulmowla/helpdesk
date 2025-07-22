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
        
        // Create a single conversation
        $contact = $contacts->first();
        $createdAt = Carbon::now()->subDays(1);
        
        // Ensure $contact is a Contact model instance
        if ($contact instanceof Contact) {
            Conversation::create([
                'contact_id' => $contact->id,
                'subject' => 'Email integration test conversation',
                'status' => 'open',
                'priority' => 'high',
                'last_activity_at' => $createdAt->addHours(2),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }
    }
}
