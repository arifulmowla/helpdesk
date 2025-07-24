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

        // Create demo articles with realistic content
        $demoArticles = [
            [
                'title' => 'Getting Started with the Helpdesk System',
                'excerpt' => 'A comprehensive guide to help you get started with our helpdesk platform.',
                'content' => $this->createTiptapContent([
                    'Welcome to our helpdesk system! This guide will walk you through the essential features and help you get started quickly.',
                    'Our platform is designed to streamline customer support operations and improve team collaboration. Whether you\'re an agent handling tickets or an administrator managing the system, this guide covers the basics you need to know.',
                    'Key features include ticket management, knowledge base articles, customer communication tools, and comprehensive reporting capabilities.'
                ]),
                'tags' => ['Getting Started', 'User Guide'],
                'published' => true,
                'views' => 150
            ],
            [
                'title' => 'How to Create and Manage Support Tickets',
                'excerpt' => 'Learn how to efficiently create, assign, and resolve customer support tickets.',
                'content' => $this->createTiptapContent([
                    'Support tickets are the core of any helpdesk system. This article explains how to effectively manage the ticket lifecycle.',
                    'Creating a new ticket is straightforward - you can either create one manually or allow the system to automatically generate tickets from customer emails.',
                    'Best practices include: setting appropriate priorities, assigning tickets to the right team members, and maintaining clear communication with customers throughout the resolution process.'
                ]),
                'tags' => ['User Guide', 'Best Practices', 'Troubleshooting'],
                'published' => true,
                'views' => 203
            ],
            [
                'title' => 'API Integration Guide',
                'excerpt' => 'Complete documentation for integrating with our helpdesk API.',
                'content' => $this->createTiptapContent([
                    'Our RESTful API allows you to integrate the helpdesk system with your existing tools and workflows.',
                    'Authentication is handled via API tokens. You can generate these tokens from your account settings page.',
                    'Common use cases include: automated ticket creation from third-party systems, synchronizing customer data, and generating custom reports.'
                ]),
                'tags' => ['API Documentation', 'Integration', 'PHP', 'Laravel'],
                'published' => true,
                'views' => 89
            ],
            [
                'title' => 'Security Best Practices',
                'excerpt' => 'Essential security measures to protect your helpdesk instance.',
                'content' => $this->createTiptapContent([
                    'Security should be a top priority when running a helpdesk system. This guide covers the essential security practices.',
                    'Always use strong passwords and enable two-factor authentication for all team members. Regularly update your system and monitor access logs.',
                    'Configure proper user permissions to ensure team members only have access to the features they need for their role.'
                ]),
                'tags' => ['Security', 'Best Practices', 'Authentication'],
                'published' => true,
                'views' => 67
            ],
            [
                'title' => 'Database Configuration and Optimization',
                'excerpt' => 'How to configure and optimize your database for best performance.',
                'content' => $this->createTiptapContent([
                    'Proper database configuration is crucial for optimal helpdesk performance, especially as your ticket volume grows.',
                    'We recommend using MySQL 8.0 or higher with proper indexing on frequently queried columns.',
                    'Regular maintenance tasks include: optimizing tables, monitoring slow queries, and setting up appropriate backup strategies.'
                ]),
                'tags' => ['Database', 'Performance', 'MySQL', 'Configuration'],
                'published' => true,
                'views' => 45
            ],
            [
                'title' => 'Email Configuration Setup',
                'excerpt' => 'Step-by-step guide to configure email settings for ticket notifications.',
                'content' => $this->createTiptapContent([
                    'Email integration is essential for seamless customer communication. This guide covers SMTP configuration.',
                    'You\'ll need to configure both incoming mail (for creating tickets from emails) and outgoing mail (for notifications).',
                    'Supported email providers include Gmail, Outlook, SendGrid, and any SMTP-compatible service.'
                ]),
                'tags' => ['Email', 'Configuration', 'Notifications'],
                'published' => true,
                'views' => 112
            ],
            [
                'title' => 'Backup and Recovery Procedures',
                'excerpt' => 'Essential backup strategies to protect your helpdesk data.',
                'content' => $this->createTiptapContent([
                    'Regular backups are critical for data protection. This article outlines recommended backup procedures.',
                    'Implement both database backups and file system backups. Test your recovery procedures regularly.',
                    'Consider automated backup solutions and off-site storage for additional protection.'
                ]),
                'tags' => ['Backup', 'Security', 'Database', 'Best Practices'],
                'published' => true,
                'views' => 33
            ],
            [
                'title' => 'Troubleshooting Common Issues',
                'excerpt' => 'Solutions to frequently encountered problems and how to resolve them.',
                'content' => $this->createTiptapContent([
                    'This troubleshooting guide addresses the most common issues users encounter.',
                    'Issues covered include: login problems, email delivery failures, performance issues, and database connection errors.',
                    'For each issue, we provide step-by-step resolution instructions and preventive measures.'
                ]),
                'tags' => ['Troubleshooting', 'FAQ', 'User Guide'],
                'published' => true,
                'views' => 178
            ],
            [
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

        // Create the articles
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

        // Create additional random articles for variety
        for ($i = 0; $i < 15; $i++) {
            $user = $users->random();
            $article = KnowledgeBaseArticle::factory()
                ->published()
                ->create([
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);
            
            // Attach 1-4 random tags to each article
            $randomTags = $tags->random(rand(1, 4));
            $article->tags()->attach($randomTags->pluck('id'));
        }

        // Create some draft articles
        for ($i = 0; $i < 5; $i++) {
            $user = $users->random();
            $article = KnowledgeBaseArticle::factory()
                ->draft()
                ->create([
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);
            
            $randomTags = $tags->random(rand(1, 3));
            $article->tags()->attach($randomTags->pluck('id'));
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
