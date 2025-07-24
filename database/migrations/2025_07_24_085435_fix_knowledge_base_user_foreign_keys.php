<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('knowledge_base_articles', function (Blueprint $table) {
            // Drop existing indexes first
            $table->dropIndex(['created_by']);
            $table->dropIndex(['updated_by']);
            
            // Drop existing foreign key constraints
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            
            // Drop the existing columns
            $table->dropColumn(['created_by', 'updated_by']);
        });
        
        Schema::table('knowledge_base_articles', function (Blueprint $table) {
            // Add new columns with ULID (string) type
            $table->string('created_by')->nullable()->after('published_at');
            $table->string('updated_by')->nullable()->after('created_by');
            
            // Add foreign key constraints for ULID columns
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
            
            // Add indexes
            $table->index('created_by');
            $table->index('updated_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('knowledge_base_articles', function (Blueprint $table) {
            // Drop foreign key constraints and indexes
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropIndex(['created_by']);
            $table->dropIndex(['updated_by']);
            
            // Drop the ULID columns
            $table->dropColumn(['created_by', 'updated_by']);
        });
        
        Schema::table('knowledge_base_articles', function (Blueprint $table) {
            // Restore original integer foreign key columns
            $table->foreignId('created_by')->constrained('users')->after('published_at');
            $table->foreignId('updated_by')->constrained('users')->after('created_by');
        });
    }
};
