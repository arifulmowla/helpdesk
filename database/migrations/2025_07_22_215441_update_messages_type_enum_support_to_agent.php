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
        // For SQLite, we need to recreate the table with the new enum values
        if (DB::connection()->getDriverName() === 'sqlite') {
            // Create a new table with the updated enum
            Schema::create('messages_new', function (Blueprint $table) {
                $table->ulid('id')->primary();
                $table->ulid('conversation_id');
                $table->enum('type', ['customer', 'agent', 'internal'])->default('customer');
                $table->text('content');
                $table->string('message_id')->nullable();
                $table->timestamps();
                
                $table->foreign('conversation_id')->references('id')->on('conversations')->onDelete('cascade');
            });
            
            // Copy data, converting 'support' to 'agent'
            DB::statement("INSERT INTO messages_new (id, conversation_id, type, content, message_id, created_at, updated_at)
                          SELECT id, conversation_id, 
                                 CASE WHEN type = 'support' THEN 'agent' ELSE type END,
                                 content, message_id, created_at, updated_at 
                          FROM messages");
            
            // Drop old table and rename new one
            Schema::dropIfExists('messages');
            Schema::rename('messages_new', 'messages');
        } else {
            // For MySQL/PostgreSQL, use ALTER TABLE
            DB::statement("UPDATE messages SET type = 'agent' WHERE type = 'support'");
            DB::statement("ALTER TABLE messages MODIFY COLUMN type ENUM('customer', 'agent', 'internal') NOT NULL DEFAULT 'customer'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // For SQLite, recreate the table with the old enum values
        if (DB::connection()->getDriverName() === 'sqlite') {
            // Create a new table with the old enum
            Schema::create('messages_new', function (Blueprint $table) {
                $table->ulid('id')->primary();
                $table->ulid('conversation_id');
                $table->enum('type', ['customer', 'support', 'internal'])->default('customer');
                $table->text('content');
                $table->string('message_id')->nullable();
                $table->timestamps();
                
                $table->foreign('conversation_id')->references('id')->on('conversations')->onDelete('cascade');
            });
            
            // Copy data, converting 'agent' to 'support'
            DB::statement("INSERT INTO messages_new (id, conversation_id, type, content, message_id, created_at, updated_at)
                          SELECT id, conversation_id, 
                                 CASE WHEN type = 'agent' THEN 'support' ELSE type END,
                                 content, message_id, created_at, updated_at 
                          FROM messages");
            
            // Drop old table and rename new one
            Schema::dropIfExists('messages');
            Schema::rename('messages_new', 'messages');
        } else {
            // For MySQL/PostgreSQL
            DB::statement("ALTER TABLE messages MODIFY COLUMN type ENUM('customer', 'support', 'internal') NOT NULL DEFAULT 'customer'");
            DB::statement("UPDATE messages SET type = 'support' WHERE type = 'agent'");
        }
    }
};
