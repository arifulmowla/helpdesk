<?php

namespace Database\Seeders;

use App\Models\KnowledgeBaseArticle;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class KnowledgeBaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks for seeding
        DB::statement('PRAGMA foreign_keys = OFF');
        
        // Ensure we have users and tags to work with
        $users = User::all();
        $tags = Tag::all();

        if ($users->isEmpty()) {
            $this->command->warn('No users found. Creating a demo user for KB articles.');
            $user = User::factory()->create([
                'name' => 'KB Admin',
                'email' => 'kb-admin@example.com',
            ]);
            $users = collect([$user]);
        }

        if ($tags->isEmpty()) {
            $this->command->warn('No tags found. Please run TagSeeder first.');
            return;
        }

        // Create realistic knowledge base articles
        $demoArticles = [
            [
                'title' => 'How to Set Your Privacy Policy',
                'excerpt' => 'Learn how to configure your privacy policy settings in the application.',
                'content' => $this->createTiptapContent([
                    'Setting up your privacy policy is essential for compliance and transparency with your users.',
                    'To access the privacy policy settings, navigate to Settings → Privacy Policy in your dashboard.',
                    'Here you can upload your privacy policy document, set the effective date, and configure notification settings.',
                    'Make sure to review your privacy policy regularly and update it when your data handling practices change.',
                    'Users will be automatically notified when you update your privacy policy, and they will need to acknowledge the changes.'
                ]),
                'tags' => ['Privacy Policy', 'Settings', 'Compliance'],
                'published' => true,
                'views' => 245
            ],
            [
                'title' => 'Creating a New Booking',
                'excerpt' => 'Step-by-step guide to create bookings in the system.',
                'content' => $this->createTiptapContent([
                    'Creating a booking is simple and can be done in just a few steps.',
                    'Navigate to the Booking page from your main dashboard or use the quick access menu.',
                    'Click on "Create Booking" to open the booking form.',
                    'Fill in the required details: customer information, service type, date and time preferences.',
                    'Review the booking details and click "Confirm Booking" to finalize.',
                    'You will receive a confirmation email, and the booking will appear in your booking calendar.',
                    'To modify or cancel a booking, find it in your bookings list and use the edit or cancel options.'
                ]),
                'tags' => ['Booking', 'User Guide', 'Tutorial'],
                'published' => true,
                'views' => 189
            ],
            [
                'title' => 'How to Reset Your Password',
                'excerpt' => 'Complete guide for resetting your password on app.crowdbook.com.',
                'content' => $this->createTiptapContent([
                    'If you\'ve forgotten your password, you can easily reset it using our secure password reset process.',
                    'Go to app.crowdbook.com and click on the "Forgot Password?" link on the login page.',
                    'Enter your email address associated with your account and click "Send Reset Link".',
                    'Check your email inbox for a password reset message from our system.',
                    'Click the reset link in the email (it\'s valid for 60 minutes for security reasons).',
                    'Enter your new password twice to confirm it meets our security requirements.',
                    'Click "Reset Password" to complete the process.',
                    'You can now log in with your new password. If you encounter any issues, contact our support team.'
                ]),
                'tags' => ['Password Reset', 'Account', 'Security'],
                'published' => true,
                'views' => 312
            ]
        ];

        // Create only the 3 specific articles
                'title' => 'Advanced Search Features',
                'excerpt' => 'Learn how to use advanced search capabilities to find tickets and articles quickly.',
                'content' => $this->createTiptapContent([
                    'Our search system supports both full-text search and advanced filtering options.',
                    'You can search across tickets, customers, and knowledge base articles using various criteria.',
                    'Advanced operators include date ranges, status filters, and tag-based searches for precise results.'
                ]),
                'tags' => ['User Guide', 'API', 'Performance'],
                'published' => true,
                'views' => 56
            ],
            [
                'title' => 'Webhook Integration',
                'excerpt' => 'How to set up webhooks for real-time event notifications.',
                'content' => $this->createTiptapContent([
                    'Webhooks allow you to receive real-time notifications when events occur in your helpdesk system.',
                    'Common webhook events include: new ticket creation, status changes, and customer replies.',
                    'Set up webhook endpoints in your application to process these events automatically.'
                ]),
                'tags' => ['Webhooks', 'Integration', 'API', 'JavaScript'],
                'published' => true,
                'views' => 41
            ],
            [
                'title' => 'Performance Monitoring and Analytics',
                'excerpt' => 'How to monitor system performance and analyze support metrics.',
                'content' => $this->createTiptapContent([
                    'Monitoring your helpdesk performance helps identify bottlenecks and improve customer satisfaction.',
                    'Key metrics to track include: average response time, ticket resolution rate, and customer satisfaction scores.',
                    'Use the built-in analytics dashboard or integrate with external monitoring tools for deeper insights.'
                ]),
                'tags' => ['Monitoring', 'Performance', 'Best Practices'],
                'published' => true,
                'views' => 72
            ],
            [
                'title' => 'Docker Deployment Guide',
                'excerpt' => 'Complete guide for deploying the helpdesk system using Docker containers.',
                'content' => $this->createTiptapContent([
                    'Docker deployment simplifies the installation and scaling of your helpdesk system.',
                    'This guide covers Docker Compose configuration, environment variables, and production deployment considerations.',
                    'Benefits include consistent environments, easy scaling, and simplified maintenance procedures.'
                ]),
                'tags' => ['Docker', 'Installation', 'Configuration', 'AWS'],
                'published' => false, // Draft article
                'views' => 0
            ]
        ];

        // Create only the 3 specific articles
        foreach ($demoArticles as $articleData) {
            $user = $users->random();
            
            $article = KnowledgeBaseArticle::create([
                'title' => $articleData['title'],
                'slug' => Str::slug($articleData['title']),
                'excerpt' => $articleData['excerpt'],
                'body' => $articleData['content'],
                'is_published' => $articleData['published'],
                'published_at' => $articleData['published'] ? now()->subDays(rand(1, 30)) : null,
                'view_count' => $articleData['views'],
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            // Attach tags
            $articleTags = $tags->whereIn('name', $articleData['tags']);
            if ($articleTags->isNotEmpty()) {
                $article->tags()->attach($articleTags->pluck('id'));
            }
        }

        // Re-enable foreign key checks
        DB::statement('PRAGMA foreign_keys = ON');
        
        $this->command->info('Knowledge Base seeded with ' . KnowledgeBaseArticle::count() . ' articles and ' . Tag::count() . ' tags');
    }

    /**
     * Create TipTap JSON content structure from paragraphs
     */
    private function createTiptapContent(array $paragraphs): array
    {
        $content = [];
        
        foreach ($paragraphs as $paragraph) {
            $content[] = [
                'type' => 'paragraph',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $paragraph
                    ]
                ]
            ];
        }

        return [
            'type' => 'doc',
            'content' => $content
        ];
    }
}
