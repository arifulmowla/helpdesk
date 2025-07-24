<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contact>
 */
class ContactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Get or create a random company for this contact
        $companies = Company::all();
        
        if ($companies->isEmpty()) {
            // Create some initial companies if none exist
            $companyNames = ['Tech Corp', 'Business Solutions LLC', 'StartUp Inc', 'Enterprise Ltd', 'Innovation Group'];
            foreach ($companyNames as $name) {
                Company::factory()->create(['name' => $name]);
            }
            $companies = Company::all();
        }
        
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'company_id' => $this->faker->optional(0.7)->randomElement($companies->pluck('id')->toArray()),
        ];
    }
}
