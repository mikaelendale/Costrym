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
        Schema::create('connected_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('app_name'); // e.g., 'gmail', 'stripe', 'quickbooks'
            $table->string('pipedream_account_id')->unique(); // Pipedream's connected account ID
            $table->string('external_user_id')->nullable(); // User's ID in the connected app
            $table->json('metadata')->nullable(); // Store additional info
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Ensure one connection per app per user
            $table->unique(['user_id', 'app_name']);
            $table->index('user_id');
            $table->index('app_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('connected_accounts');
    }
};
