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
        Schema::create('financial_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Integration info
            $table->string('integration_type'); // xero, zoho_books, quickbooks, sevdesk, expensify
            $table->string('integration_record_id'); // ID from the integration
            $table->string('record_type'); // transaction, expense, invoice, payment, contact

            // Quick access fields (for fast filtering/searching)
            $table->decimal('amount', 15, 2)->nullable(); // Stored in cents equivalent
            $table->string('currency', 3)->default('USD'); // ISO 4217 currency code
            $table->date('date')->nullable(); // Transaction/record date
            $table->text('description')->nullable(); // Searchable description

            // AI categorization (soft reference - no foreign key constraint for flexibility)
            $table->unsignedBigInteger('category_id')->nullable();

            // JSON storage (the magic!)
            $table->json('raw_data'); // Original data from integration (unchanged)
            $table->json('normalized_data')->nullable(); // AI-cleaned/normalized data
            $table->json('metadata')->nullable(); // Additional info (sync status, flags, etc.)

            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'integration_type']);
            $table->index(['user_id', 'record_type']);
            $table->index(['user_id', 'date']);
            $table->index(['user_id', 'category_id']);
            $table->unique(['integration_type', 'integration_record_id'], 'unique_integration_record');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_records');
    }
};
