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

        // Create knowledge_base_articles table
        Schema::create('knowledge_base_articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->json('body'); // TipTap JSON content
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->string('created_by');
            $table->string('updated_by');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['is_published', 'published_at']);
            $table->index('slug');
            $table->index('created_by');
            $table->index('updated_by');
        });

        // Create article_tag pivot table
        Schema::create('article_tag', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained('knowledge_base_articles')->onDelete('cascade');
            $table->foreignId('tag_id')->constrained('tags')->onDelete('cascade');
            
            $table->primary(['article_id', 'tag_id']);
        });

        // Create full-text search virtual table for SQLite
        // This creates a virtual FTS5 table for full-text search
        DB::statement('
            CREATE VIRTUAL TABLE knowledge_base_articles_fts USING fts5(
                title,
                body,
                content_rowid,
                content=knowledge_base_articles
            )
        ');
        
        // Create triggers to keep FTS table in sync
        DB::statement('
            CREATE TRIGGER knowledge_base_articles_fts_insert AFTER INSERT ON knowledge_base_articles BEGIN
                INSERT INTO knowledge_base_articles_fts(rowid, title, body) 
                VALUES (new.id, new.title, json_extract(new.body, "$.content"));
            END
        ');
        
        DB::statement('
            CREATE TRIGGER knowledge_base_articles_fts_delete AFTER DELETE ON knowledge_base_articles BEGIN
                DELETE FROM knowledge_base_articles_fts WHERE rowid = old.id;
            END
        ');
        
        DB::statement('
            CREATE TRIGGER knowledge_base_articles_fts_update AFTER UPDATE ON knowledge_base_articles BEGIN
                DELETE FROM knowledge_base_articles_fts WHERE rowid = old.id;
                INSERT INTO knowledge_base_articles_fts(rowid, title, body) 
                VALUES (new.id, new.title, json_extract(new.body, "$.content"));
            END
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop FTS table and triggers first
        DB::statement('DROP TRIGGER IF EXISTS knowledge_base_articles_fts_update');
        DB::statement('DROP TRIGGER IF EXISTS knowledge_base_articles_fts_delete');
        DB::statement('DROP TRIGGER IF EXISTS knowledge_base_articles_fts_insert');
        DB::statement('DROP TABLE IF EXISTS knowledge_base_articles_fts');
        
        Schema::dropIfExists('article_tag');
        Schema::dropIfExists('knowledge_base_articles');
        Schema::dropIfExists('tags');
    }
};
