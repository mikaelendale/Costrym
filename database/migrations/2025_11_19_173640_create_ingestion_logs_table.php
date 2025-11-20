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
        Schema::create('ingestion_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('integration_type'); // xero, zoho_books, quickbooks, etc.
            $table->string('status'); // pending, running, completed, failed

            // Stats
            $table->integer('records_fetched')->default(0);
            $table->integer('records_saved')->default(0);
            $table->integer('records_updated')->default(0);
            $table->integer('records_skipped')->default(0);

            // Error tracking
            $table->json('errors')->nullable(); // Array of error messages

            // Timestamps
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'integration_type']);
            $table->index(['user_id', 'status']);
            $table->index('started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingestion_logs');
    }
};
