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
        Schema::create('conversations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('contact_id');
            $table->string('subject');
            $table->string('status')->default('open');
            $table->string('priority')->default('low');
            $table->timestamp('last_activity_at')->nullable();
            $table->boolean('unread')->default(true);
            $table->timestamp('read_at')->nullable();
            $table->string('case_number', 8)->unique();
            $table->ulid('assigned_to')->nullable();
            $table->timestamps();

            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            // Foreign key constraint for assigned_to will be added in a separate migration
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
