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
        $this->command->info('Creating test conversation data...');
        
        // Create 20 contacts with conversations
        $contacts = Contact::factory(20)->create();
        
        // Create conversations with various statuses and priorities
        $statuses = ['open', 'closed', 'awaiting_customer', 'awaiting_agent', 'resolved', 'cancelled'];
        $priorities = ['low', 'medium', 'high', 'urgent'];
        
        $conversations = collect();
        
        // Create a variety of conversations
        foreach ($contacts as $contact) {
            // Each contact gets 1-3 conversations
            $conversationCount = rand(1, 3);
            
            for ($i = 0; $i < $conversationCount; $i++) {
                $conversation = Conversation::factory()
                    ->for($contact)
                    ->create();
                
                // Create 1-5 messages per conversation
                $messageCount = rand(1, 5);
                $messages = [];
                
                for ($j = 0; $j < $messageCount; $j++) {
                    $isFromCustomer = $j === 0 || rand(0, 1); // First message is always from customer
                    
                    $messages[] = Message::factory()
                        ->for($conversation)
                        ->state([
                            'type' => $isFromCustomer ? 'customer' : 'support',
                            'created_at' => now()->subDays(rand(0, 30))->subHours(rand(0, 23)),
                        ])
                        ->create();
                }
                
                // Update conversation's last_activity_at to match the latest message
                $latestMessage = collect($messages)->sortByDesc('created_at')->first();
                $conversation->update([
                    'last_activity_at' => $latestMessage->created_at,
                ]);
                
                $conversations->push($conversation);
            }
        }
        
        // Create some specific test scenarios
        $this->createTestScenarios($contacts);
        
        $this->command->info('Created ' . $conversations->count() . ' conversations with messages');
        $this->command->info('Status distribution:');
        
        foreach ($statuses as $status) {
            $count = Conversation::where('status', $status)->count();
            $this->command->info("  {$status}: {$count}");
        }
        
        $this->command->info('Priority distribution:');
        foreach ($priorities as $priority) {
            $count = Conversation::where('priority', $priority)->count();
            $this->command->info("  {$priority}: {$count}");
        }
        
        $unreadCount = Conversation::where('unread', true)->count();
        $this->command->info("Unread conversations: {$unreadCount}");
    }
    
    private function createTestScenarios($contacts): void
    {
        // Create some specific scenarios for testing filters
        
        // 1. High priority urgent issue (unread)
        Conversation::factory()
            ->for($contacts->random())
            ->state([
                'subject' => 'URGENT: System Down - Need Immediate Help',
                'status' => 'open',
                'priority' => 'urgent',
                'unread' => true,
                'last_activity_at' => now()->subMinutes(5),
            ])
            ->has(
                Message::factory()
                    ->fromCustomer()
                    ->state(['content' => 'Our entire system is down and we need immediate assistance. This is affecting all our users!'])
            )
            ->create();
        
        // 2. Resolved low priority issue (read)
        Conversation::factory()
            ->for($contacts->random())
            ->state([
                'subject' => 'Question about billing',
                'status' => 'resolved',
                'priority' => 'low',
                'unread' => false,
                'last_activity_at' => now()->subDays(2),
            ])
            ->has(
                Message::factory()
                    ->fromCustomer()
                    ->state(['content' => 'I have a question about my last invoice.']),
                'messages'
            )
            ->has(
                Message::factory()
                    ->fromSupport()
                    ->state([
                        'content' => 'Thanks for reaching out! I can help you with that billing question.',
                        'created_at' => now()->subDays(2)->addHours(1)
                    ]),
                'messages'
            )
            ->create();
        
        // 3. Awaiting customer response (read)
        Conversation::factory()
            ->for($contacts->random())
            ->state([
                'subject' => 'Feature request follow-up',
                'status' => 'awaiting_customer',
                'priority' => 'medium',
                'unread' => false,
                'last_activity_at' => now()->subHours(6),
            ])
            ->create();
        
        // 4. Multiple conversations from same contact
        $frequentContact = $contacts->random();
        for ($i = 0; $i < 3; $i++) {
            Conversation::factory()
                ->for($frequentContact)
                ->state([
                    'subject' => 'Issue #' . ($i+1) . ' - Various problems',
                    'status' => collect(['open', 'awaiting_agent', 'closed'])->random(),
                    'priority' => collect(['medium', 'high'])->random(),
                ])
                ->create();
        }
    }
}
