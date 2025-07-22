<?php

namespace Database\Factories;

use App\Models\Conversation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Message>
 */
class MessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'type' => $this->faker->randomElement(['customer', 'support', 'internal']),
            'content' => $this->faker->paragraphs(rand(1, 3), true),
            'message_id' => '<' . Str::uuid() . '@helpdesk.test>',
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Indicate that the message is from a customer.
     */
    public function fromCustomer(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'customer',
        ]);
    }

    /**
     * Indicate that the message is from support.
     */
    public function fromSupport(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'support',
        ]);
    }
}
