<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates connected_accounts table with indexes and constraints for scalability and security
     */
    public function up(): void
    {
        Schema::create('connected_accounts', function (Blueprint $table) {
            $table->id();
            
            // Foreign key with cascade delete for data integrity
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->comment('Reference to users table');
            
            // App identification
            $table->string('app_name', 50)
                ->index()
                ->comment('Application name (e.g., gmail, slack, stripe)');
            
            // Pipedream account identifier
            $table->string('pipedream_account_id', 255)
                ->unique()
                ->index()
                ->comment('Pipedream connected account ID');
            
            // External user identifier
            $table->string('external_user_id', 255)
                ->nullable()
                ->index()
                ->comment('User ID in the connected application');
            
            // Encrypted metadata for sensitive data
            $table->json('metadata')
                ->nullable()
                ->comment('Encrypted account metadata and connection details');
            
            // Connection status
            $table->boolean('is_active')
                ->default(true)
                ->index()
                ->comment('Whether the connection is currently active');
            
            // Token expiration tracking
            $table->timestamp('token_expires_at')
                ->nullable()
                ->index()
                ->comment('When the connection token expires');
            
            // Last sync timestamp
            $table->timestamp('last_synced_at')
                ->nullable()
                ->comment('Last time account data was synced from Pipedream');
            
            // Connection status tracking
            $table->enum('connection_status', ['connected', 'disconnected', 'expired', 'error'])
                ->default('connected')
                ->index()
                ->comment('Current status of the connection');
            
            // Error tracking
            $table->text('last_error')
                ->nullable()
                ->comment('Last error message if connection failed');
            
            // Audit timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Composite indexes for common query patterns
            $table->index(['user_id', 'app_name', 'is_active'], 'idx_user_app_active');
            $table->index(['user_id', 'is_active', 'connection_status'], 'idx_user_active_status');
            $table->index(['app_name', 'is_active'], 'idx_app_active');
            $table->index(['connection_status', 'is_active'], 'idx_status_active');
            $table->index(['token_expires_at', 'is_active'], 'idx_token_expires');
            
            // Unique constraint: one active connection per app per user
            $table->unique(['user_id', 'app_name'], 'unique_user_app');
            
            // Table comment
            $table->comment('Stores connected third-party accounts via Pipedream Connect');
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
