<?php

namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class MessageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all conversations
        $conversations = Conversation::all();
        
        if ($conversations->isEmpty()) {
            $this->command->info('No conversations found. Please run ConversationSeeder first.');
            return;
        }
        
        // Sample message content for different types
        $customerMessages = [
            'Hi, I need help with your product.',
            'I can\'t seem to log in to my account.',
            'Is there a way to reset my password?',
            'I\'m having trouble with the checkout process.',
            'Can you help me understand how this feature works?',
            'I\'d like to request a refund for my recent purchase.',
            'The app keeps crashing when I try to use it.',
            'Thank you for your help!',
            'I\'m still experiencing the same issue.',
            'Could you provide more details about your pricing plans?',
        ];
        
        $supportMessages = [
            'Thank you for reaching out to us. How can I help you today?',
            'I understand your concern. Let me look into this for you.',
            'Have you tried resetting your browser cache?',
            'Could you please provide more details about the issue you\'re experiencing?',
            'I\'ve checked your account and everything seems to be in order.',
            'I\'ve escalated this to our technical team for further investigation.',
            'Is there anything else I can help you with?',
            'Let me know if that solution worked for you.',
            'I\'m happy to assist you with this matter.',
            'We apologize for the inconvenience this has caused.',
        ];
        
        $internalNotes = [
            'Customer has reported this issue multiple times.',
            'Checked account history - no previous refunds.',
            'This appears to be a known bug in version 2.3.',
            'Customer is on the premium plan.',
            'Forwarded to engineering team for review.',
            'Customer may need to upgrade their subscription.',
            'Previous ticket: #45678',
            'Customer is using an outdated browser version.',
            'Account flagged for follow-up next week.',
            'Customer is a VIP - priority handling required.',
        ];
        
        // Create messages for each conversation
        foreach ($conversations as $conversation) {
            // Determine number of messages (2-8 per conversation)
            $messageCount = rand(2, 8);
            
            // Create initial customer message
            $createdAt = $conversation->created_at;
            
            // Ensure $conversation is a Conversation model instance
            if ($conversation instanceof Conversation) {
                // Initial customer message
                Message::create([
                    'conversation_id' => $conversation->id,
                    'type' => 'customer',
                    'content' => fake()->randomElement($customerMessages),
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
                
                // Create additional messages with increasing timestamps
                for ($i = 1; $i < $messageCount; $i++) {
                    // Advance time by 1-12 hours for each message
                    $createdAt = $createdAt->copy()->addHours(rand(1, 12));
                    
                    // Determine message type
                    $types = ['customer', 'support', 'internal'];
                    $weights = [30, 50, 20]; // 30% customer, 50% support, 20% internal
                    $type = $this->weightedRandom($types, $weights);
                    
                    // Select appropriate content based on type
                    $content = match ($type) {
                        'customer' => fake()->randomElement($customerMessages),
                        'support' => fake()->randomElement($supportMessages),
                        'internal' => fake()->randomElement($internalNotes),
                        default => fake()->sentence(),
                    };
                    
                    Message::create([
                        'conversation_id' => $conversation->id,
                        'type' => $type,
                        'content' => $content,
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);
                }
                
                // Update conversation's last_activity_at to match the last message
                $conversation->update([
                    'last_activity_at' => $createdAt,
                ]);
            }
        }
    }
    
    /**
     * Get a random item from an array with weighted probabilities.
     *
     * @param array $items Array of items to choose from
     * @param array $weights Array of weights corresponding to items
     * @return mixed Selected item
     */
    private function weightedRandom(array $items, array $weights): mixed
    {
        $totalWeight = array_sum($weights);
        $randomWeight = mt_rand(1, $totalWeight);
        
        $currentWeight = 0;
        foreach ($items as $index => $item) {
            $currentWeight += $weights[$index];
            if ($randomWeight <= $currentWeight) {
                return $item;
            }
        }
        
        return $items[0]; // Fallback to first item
    }
}
