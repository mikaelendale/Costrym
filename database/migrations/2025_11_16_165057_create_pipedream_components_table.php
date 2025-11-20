<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates pipedream_components table to store available Pipedream components (actions/triggers)
     */
    public function up(): void
    {
        Schema::create('pipedream_components', function (Blueprint $table) {
            $table->id();

            // Component identification
            $table->string('component_key', 255)
                ->unique()
                ->index()
                ->comment('Unique component key from Pipedream (e.g., slack-send-message-to-channel)');

            $table->string('component_name', 255)
                ->index()
                ->comment('Human-readable component name');

            $table->enum('component_type', ['action', 'trigger'])
                ->index()
                ->comment('Type of component: action or trigger');

            // App association
            $table->string('app_name', 100)
                ->index()
                ->comment('Associated app name (e.g., slack, gmail)');

            $table->string('app_id', 255)
                ->nullable()
                ->index()
                ->comment('Pipedream app ID');

            // Component metadata
            $table->string('version', 50)
                ->nullable()
                ->comment('Component version');

            $table->json('component_data')
                ->nullable()
                ->comment('Full component data from Pipedream API');

            $table->text('description')
                ->nullable()
                ->comment('Component description');

            // Caching metadata
            $table->timestamp('last_synced_at')
                ->nullable()
                ->index()
                ->comment('Last time component data was synced from Pipedream');

            $table->boolean('is_active')
                ->default(true)
                ->index()
                ->comment('Whether the component is currently available');

            // Audit timestamps
            $table->timestamps();

            // Composite indexes for common queries
            $table->index(['app_name', 'component_type', 'is_active'], 'idx_pc_app_type_active');
            $table->index(['component_type', 'is_active'], 'idx_pc_type_active');
            $table->index(['app_name', 'is_active'], 'idx_pc_app_active');

            // Table comment
            $table->comment('Stores Pipedream Connect components (actions and triggers)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pipedream_components');
    }
};
