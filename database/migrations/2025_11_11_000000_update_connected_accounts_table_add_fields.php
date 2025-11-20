<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds additional fields for scalability and security
     */
    public function up(): void
    {
        // Conditionally add new fields (safe no-ops if columns already exist)
        Schema::table('connected_accounts', function (Blueprint $table) {
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

            if (! Schema::hasColumn('connected_accounts', 'connection_status')) {
                $table->enum('connection_status', ['connected', 'disconnected', 'expired', 'error'])
                    ->default('connected')
                    ->after('last_synced_at')
                    ->index()
                    ->comment('Current status of the connection');
            }

            if (! Schema::hasColumn('connected_accounts', 'last_error')) {

            if (! Schema::hasColumn('connected_accounts', 'last_error')) {
                $table->text('last_error')
                    ->nullable()
                    ->after('connection_status')
                    ->comment('Last error message if connection failed');
            }
            // Note: Do NOT manage deleted_at here; it is created in the original table migration
        });

        // Ensure indexes exist without throwing on Postgres (uses IF NOT EXISTS)
        if (Schema::hasTable('connected_accounts')) {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_user_app_active ON connected_accounts (user_id, app_name, is_active)');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_user_active_status ON connected_accounts (user_id, is_active, connection_status)');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_app_active ON connected_accounts (app_name, is_active)');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_status_active ON connected_accounts (connection_status, is_active)');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_token_expires ON connected_accounts (token_expires_at, is_active)');
            // Match Laravel default index name for single-column index created via ->index()
            DB::statement('CREATE INDEX IF NOT EXISTS connected_accounts_external_user_id_index ON connected_accounts (external_user_id)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is designed to be idempotent and only add missing pieces.
        // We intentionally do not drop columns or indexes here to avoid removing
        // structures that may have been created by earlier migrations.
    }
};
