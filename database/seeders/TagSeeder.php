<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create predefined support tags
        $supportTags = [
            'Getting Started',
            'Installation',
            'Configuration',
            'Troubleshooting',
            'API Documentation',
            'User Guide',
            'FAQ',
            'Best Practices',
            'Security',
            'Performance',
            'Billing',
            'Account Management',
            'Integration',
            'Updates',
            'Migration'
        ];

        foreach ($supportTags as $tagName) {
            Tag::factory()->withName($tagName)->create();
        }

        // Create technical tags
        $technicalTags = [
            'PHP',
            'Laravel',
            'MySQL',
            'Redis',
            'Docker',
            'AWS',
            'JavaScript',
            'Vue.js',
            'React',
            'Node.js',
            'API',
            'Database',
            'Authentication',
            'Authorization',
            'Email',
            'Notifications',
            'Webhooks',
            'SSL/TLS',
            'Backup',
            'Monitoring'
        ];

        foreach ($technicalTags as $tagName) {
            Tag::factory()->withName($tagName)->create();
        }

        // Create additional random tags for variety
        Tag::factory()->count(10)->create();
    }
}
