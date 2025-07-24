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
        $driver = config('database.connections.' . config('database.default') . '.driver');
        
        // Only add FULLTEXT index for MySQL
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE knowledge_base_articles ADD FULLTEXT fulltext_search_index (title, excerpt)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = config('database.connections.' . config('database.default') . '.driver');
        
        // Only drop FULLTEXT index for MySQL
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE knowledge_base_articles DROP INDEX fulltext_search_index');
        }
    }
};
