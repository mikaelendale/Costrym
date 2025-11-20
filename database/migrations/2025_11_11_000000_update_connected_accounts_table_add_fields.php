<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds additional fields for scalability and security
     */
    public function up(): void
    {
        Schema::table('connected_accounts', function (Blueprint $table) {
            // Add new fields if they don't exist
            if (! Schema::hasColumn('connected_accounts', 'token_expires_at')) {
                $table->timestamp('token_expires_at')
                    ->nullable()
                    ->after('is_active')
                    ->index()
                    ->comment('When the connection token expires');
            }

            if (! Schema::hasColumn('connected_accounts', 'last_synced_at')) {
                $table->timestamp('last_synced_at')
                    ->nullable()
                    ->after('token_expires_at')
                    ->comment('Last time account data was synced from Pipedream');
            }

            if (! Schema::hasColumn('connected_accounts', 'connection_status')) {
                $table->enum('connection_status', ['connected', 'disconnected', 'expired', 'error'])
                    ->default('connected')
                    ->after('last_synced_at')
                    ->index()
                    ->comment('Current status of the connection');
            }

            if (! Schema::hasColumn('connected_accounts', 'last_error')) {
                $table->text('last_error')
                    ->nullable()
                    ->after('connection_status')
                    ->comment('Last error message if connection failed');
            }

            if (! Schema::hasColumn('connected_accounts', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }

            // Add composite indexes for performance - only if they don't exist
            // (Skip in migration execution - indexes are created in the original table creation)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('connected_accounts', function (Blueprint $table) {
            // Drop columns only
            $table->dropColumn([
                'token_expires_at',
                'last_synced_at',
                'connection_status',
                'last_error',
                'deleted_at',
            ]);
        });
    }
};
