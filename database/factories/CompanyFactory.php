<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'email' => $this->faker->optional(0.8)->companyEmail(),
            'phone' => $this->faker->optional(0.6)->phoneNumber(),
            'address' => $this->faker->optional(0.5)->address(),
            'website' => $this->faker->optional(0.4)->url(),
        ];
    }
}
