<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create a test user
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'vendor@vendor.com',
            'password' => '123vendor',
        ]);
        
        // Call our custom seeders in the correct order
        $this->call([
            ContactSeeder::class,
            ConversationSeeder::class,
            MessageSeeder::class,
        ]);
    }
}
