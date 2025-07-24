<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Database\Seeder;

class ConversationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating realistic conversation data...');
        
        // Create the specific contact cbottelet@gmail.com
        $contact = Contact::firstOrCreate(
            ['email' => 'cbottelet@gmail.com'],
            [
                'name' => 'Christian Bottelet',
                'company' => 'CrowdBook',
            ]
        );
        
        // Create exactly 3 conversations - one for each KB topic
        $this->createPrivacyPolicyConversation($contact);
        $this->createBookingConversation($contact);
        $this->createPasswordResetConversation($contact);
        
        $totalConversations = Conversation::count();
        $this->command->info("Created {$totalConversations} realistic conversations from cbottelet@gmail.com");
    }
    
    private function createPrivacyPolicyConversation(Contact $contact): void
    {
        $conversation = Conversation::create([
            'contact_id' => $contact->id,
            'subject' => 'How to update privacy policy settings?',
            'status' => 'resolved',
            'priority' => 'medium',
            'unread' => false,
            'last_activity_at' => now()->subDays(5),
            'created_at' => now()->subDays(5),
        ]);
        
        Message::create([
            'conversation_id' => $conversation->id,
            'type' => 'customer',
            'content' => 'Hi, I need to update our privacy policy in the system. I can\'t seem to find where to do this in the settings. Can you help me locate the privacy policy configuration?',
            'created_at' => now()->subDays(5),
        ]);
        
        Message::create([
            'conversation_id' => $conversation->id,
            'type' => 'agent',
            'content' => 'Hello Christian! I\'d be happy to help you with that. To update your privacy policy, navigate to Settings → Privacy Policy in your dashboard. There you can upload your privacy policy document and configure notification settings.',
            'created_at' => now()->subDays(5)->addHours(2),
        ]);
        
        Message::create([
            'conversation_id' => $conversation->id,
            'type' => 'customer',
            'content' => 'Perfect! Found it. Thank you so much for the quick help.',
            'created_at' => now()->subDays(5)->addHours(3),
        ]);
    }
    
    private function createBookingConversation(Contact $contact): void
    {
        $conversation = Conversation::create([
            'contact_id' => $contact->id,
            'subject' => 'Need help creating a booking',
            'status' => 'resolved',
            'priority' => 'medium',
            'unread' => false,
            'last_activity_at' => now()->subDays(7),
            'created_at' => now()->subDays(7),
        ]);
        
        Message::create([
            'conversation_id' => $conversation->id,
            'type' => 'customer',
            'content' => 'Hi, I\'m new to the system and need to create a booking for a client. I found the booking page but I\'m not sure about the process. Can you walk me through it?',
            'created_at' => now()->subDays(7),
        ]);
        
        Message::create([
            'conversation_id' => $conversation->id,
            'type' => 'agent',
            'content' => 'Hello Christian! I\'d be happy to help. Navigate to the Booking page and click "Create Booking". Fill in the customer information, service type, and date/time preferences. Then review and click "Confirm Booking". You\'ll receive a confirmation email.',
            'created_at' => now()->subDays(7)->addMinutes(30),
        ]);
        
        Message::create([
            'conversation_id' => $conversation->id,
            'type' => 'customer',
            'content' => 'That was much easier than I expected! The booking is now in my calendar. Thanks for the clear instructions.',
            'created_at' => now()->subDays(7)->addHours(1),
        ]);
    }
    
    private function createPasswordResetConversation(Contact $contact): void
    {
        $conversation = Conversation::create([
            'contact_id' => $contact->id,
            'subject' => 'Cannot reset password on app.crowdbook.com',
            'status' => 'resolved',
            'priority' => 'medium',
            'unread' => false,
            'last_activity_at' => now()->subDays(6),
            'created_at' => now()->subDays(6),
        ]);
        
        Message::create([
            'conversation_id' => $conversation->id,
            'type' => 'customer',
            'content' => 'I\'m trying to reset my password on app.crowdbook.com but I\'m not receiving the reset email. I\'ve checked my spam folder too. Can you help me with this?',
            'created_at' => now()->subDays(6),
        ]);
        
        Message::create([
            'conversation_id' => $conversation->id,
            'type' => 'agent',
            'content' => 'Hi Christian! Let me help you with that. First, make sure you\'re using the correct email address (cbottelet@gmail.com). Go to app.crowdbook.com, click "Forgot Password?", enter your email, and click "Send Reset Link". The email should arrive within a few minutes.',
            'created_at' => now()->subDays(6)->addMinutes(20),
        ]);
        
        Message::create([
            'conversation_id' => $conversation->id,
            'type' => 'customer',
            'content' => 'Got it! The email came through this time. I was able to reset my password successfully. Thank you!',
            'created_at' => now()->subDays(6)->addMinutes(35),
        ]);
    }
}
