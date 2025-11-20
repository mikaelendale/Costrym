<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE tasks ALTER COLUMN agent_name DROP NOT NULL');
            DB::statement('ALTER TABLE task_queue ALTER COLUMN agent_name DROP NOT NULL');
        } else {
            Schema::table('tasks', function (Blueprint $table) {
                $table->string('agent_name')->nullable()->change();
            });

            Schema::table('task_queue', function (Blueprint $table) {
                $table->string('agent_name')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: Cannot safely revert to NOT NULL without data cleanup
        // Leaving as nullable to prevent data loss
    }
};
