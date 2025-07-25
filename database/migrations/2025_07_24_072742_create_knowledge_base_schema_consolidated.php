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
        // Create tags table
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
            
            $table->index('slug');
        });

        // Create knowledge_base_articles table with all fields consolidated
        Schema::create('knowledge_base_articles', function (Blueprint $table) {
            $table->string('id', 26)->primary(); // ULID primary key
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->json('body'); // TipTap JSON content
            $table->longText('raw_body')->nullable(); // Plain text version for AI/search
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('view_count')->default(0);
            $table->string('created_by', 26); // ULID reference to users.id
            $table->string('updated_by', 26); // ULID reference to users.id
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['is_published', 'published_at']);
            $table->index('slug');
            $table->index('created_by');
            $table->index('updated_by');
            $table->index('view_count'); // For sorting by popularity
            $table->index('title'); // Regular index for title search
            $table->index('raw_body'); // Regular index for body search
        });

        // Create article_tag pivot table
        Schema::create('article_tag', function (Blueprint $table) {
            $table->string('article_id', 26);
            $table->foreignId('tag_id')->constrained('tags')->onDelete('cascade');
            $table->foreign('article_id')->references('id')->on('knowledge_base_articles')->onDelete('cascade');
            
            $table->primary(['article_id', 'tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_tag');
        Schema::dropIfExists('knowledge_base_articles');
        Schema::dropIfExists('tags');
    }
};
