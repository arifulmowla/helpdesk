<?php

namespace Database\Factories;

use App\Enums\Priority;
use App\Enums\Status;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Conversation>
 */
class ConversationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = ['open', 'closed', 'awaiting_customer', 'awaiting_agent', 'resolved', 'cancelled'];
        $priorities = ['low', 'medium', 'high', 'urgent'];
        
        return [
            'contact_id' => Contact::factory(),
            'subject' => $this->faker->sentence(6),
            'status' => $this->faker->randomElement($statuses),
            'priority' => $this->faker->randomElement($priorities),
            'unread' => $this->faker->boolean(70), // 70% chance of being unread
            'last_activity_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'created_at' => $this->faker->dateTimeBetween('-2 months', '-1 week'),
        ];
    }

    /**
     * Indicate that the conversation is unread.
     */
    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'unread' => true,
        ]);
    }

    /**
     * Indicate that the conversation is read.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'unread' => false,
        ]);
    }

    /**
     * Indicate that the conversation has high priority.
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'high',
        ]);
    }

    /**
     * Indicate that the conversation is open.
     */
    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'open',
        ]);
    }
}
