<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For SQLite, we need to recreate the table to add a primary key properly
        // First, let's check if we need to add the primary key
        $users = DB::table('users')->get();
        
        // Drop and recreate the users table with proper primary key
        Schema::dropIfExists('users_backup');
        
        // Create backup table
        DB::statement('CREATE TABLE users_backup AS SELECT * FROM users');
        
        // Drop original table
        Schema::dropIfExists('users');
        
        // Recreate with proper primary key
        Schema::create('users', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
        
        // Restore data
        DB::statement('INSERT INTO users SELECT * FROM users_backup');
        
        // Drop backup
        DB::statement('DROP TABLE users_backup');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This is a destructive change, we can't easily reverse it
        // The down migration would need to recreate the table structure
    }
};
