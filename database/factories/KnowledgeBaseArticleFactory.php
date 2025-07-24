<?php

namespace Database\Factories;

use App\Models\KnowledgeBaseArticle;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\KnowledgeBaseArticle>
 */
class KnowledgeBaseArticleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(6, true);
        $slug = Str::slug($title);
        
        // Generate TipTap JSON content structure
        $tiptapContent = [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => fake()->paragraph(3)
                        ]
                    ]
                ],
                [
                    'type' => 'paragraph',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => fake()->paragraph(5)
                        ]
                    ]
                ]
            ]
        ];

        return [
            'title' => $title,
            'slug' => $slug,
            'excerpt' => fake()->paragraph(2),
            'body' => $tiptapContent,
            'is_published' => fake()->boolean(70), // 70% chance of being published
            'published_at' => fake()->boolean(70) ? fake()->dateTimeBetween('-1 year', 'now') : null,
            'view_count' => fake()->numberBetween(0, 1000),
            'created_by' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'updated_by' => function (array $attributes) {
                return $attributes['created_by'];
            },
        ];
    }

    /**
     * Create a published article
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
            'published_at' => fake()->dateTimeBetween('-6 months', 'now'),
        ]);
    }

    /**
     * Create a draft article
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
            'published_at' => null,
        ]);
    }

    /**
     * Create an article with a specific slug
     */
    public function withSlug(string $slug): static
    {
        return $this->state(fn (array $attributes) => [
            'slug' => $slug,
        ]);
    }

    /**
     * Create an article with specific view count
     */
    public function withViews(int $count): static
    {
        return $this->state(fn (array $attributes) => [
            'view_count' => $count,
        ]);
    }

    /**
     * Create an article with simple text body for testing
     */
    public function withTextBody(string $text = null): static
    {
        $content = $text ?? fake()->paragraphs(3, true);
        
        return $this->state(fn (array $attributes) => [
            'body' => [
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'paragraph',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => $content
                            ]
                        ]
                    ]
                ]
            ]
        ]);
    }

    /**
     * Create an article with complex TipTap content
     */
    public function withComplexContent(): static
    {
        return $this->state(fn (array $attributes) => [
            'body' => [
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'heading',
                        'attrs' => ['level' => 1],
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => fake()->sentence(4)
                            ]
                        ]
                    ],
                    [
                        'type' => 'paragraph',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => fake()->paragraph(3)
                            ]
                        ]
                    ],
                    [
                        'type' => 'bulletList',
                        'content' => [
                            [
                                'type' => 'listItem',
                                'content' => [
                                    [
                                        'type' => 'paragraph',
                                        'content' => [
                                            [
                                                'type' => 'text',
                                                'text' => fake()->sentence()
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                            [
                                'type' => 'listItem',
                                'content' => [
                                    [
                                        'type' => 'paragraph',
                                        'content' => [
                                            [
                                                'type' => 'text',
                                                'text' => fake()->sentence()
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]);
    }

    /**
     * Create an article authored by a specific user
     */
    public function authoredBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }
}
