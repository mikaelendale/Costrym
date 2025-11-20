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
        // For PostgreSQL, we need to alter the enum type
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE tasks DROP CONSTRAINT IF EXISTS tasks_status_check');
            DB::statement('ALTER TABLE tasks ALTER COLUMN status TYPE VARCHAR(255)');
            DB::statement("ALTER TABLE tasks ADD CONSTRAINT tasks_status_check CHECK (status IN ('pending', 'approved', 'running', 'completed', 'failed', 'cancelled'))");
        } else {
            // For SQLite or other databases
            Schema::table('tasks', function (Blueprint $table) {
                $table->string('status')->default('pending')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // For PostgreSQL
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE tasks DROP CONSTRAINT IF EXISTS tasks_status_check');
            DB::statement("ALTER TABLE tasks ADD CONSTRAINT tasks_status_check CHECK (status IN ('pending', 'running', 'completed', 'failed'))");
        } else {
            // For SQLite or other databases
            Schema::table('tasks', function (Blueprint $table) {
                $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending')->change();
            });
        }
    }
};
