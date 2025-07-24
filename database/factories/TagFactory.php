<?php

namespace Database\Factories;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tag>
 */
class TagFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(fake()->numberBetween(1, 3), true);
        
        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }

    /**
     * Create a tag with a specific name
     */
    public function withName(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'slug' => Str::slug($name),
        ]);
    }

    /**
     * Create a tag with a specific slug
     */
    public function withSlug(string $slug): static
    {
        return $this->state(fn (array $attributes) => [
            'slug' => $slug,
        ]);
    }

    /**
     * Create common support tags
     */
    public function supportTag(): static
    {
        $supportTags = [
            'Getting Started',
            'Troubleshooting',
            'Installation',
            'Configuration',
            'API',
            'Database',
            'Security',
            'Performance',
            'Billing',
            'Account Setup'
        ];

        $name = fake()->randomElement($supportTags);
        
        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'slug' => Str::slug($name),
        ]);
    }

    /**
     * Create technical tags
     */
    public function technicalTag(): static
    {
        $techTags = [
            'PHP',
            'Laravel',
            'MySQL',
            'Redis',
            'Docker',
            'AWS',
            'JavaScript',
            'Vue.js',
            'React',
            'Node.js'
        ];

        $name = fake()->randomElement($techTags);
        
        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'slug' => Str::slug($name),
        ]);
    }
}
