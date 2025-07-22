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
        // Create an admin user
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@admin.com',
            'password' => '123admin',
        ]);
        
        // Call our custom seeders in the correct order
        $this->call([
            ContactSeeder::class,
            ConversationSeeder::class,
            MessageSeeder::class,
        ]);
    }
}
