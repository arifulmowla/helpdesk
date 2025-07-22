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
        Schema::create('raw_emails', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('message_id')->unique(); // Postmark MessageID
            $table->ulid('message_id_ref')->nullable(); // Reference to our message
            $table->json('headers')->nullable(); // All email headers
            $table->json('payload'); // Complete Postmark payload
            $table->text('raw_content')->nullable(); // Raw email content if available
            $table->timestamps();
            
            $table->foreign('message_id_ref')->references('id')->on('messages')->onDelete('set null');
            $table->index('message_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raw_emails');
    }
};
