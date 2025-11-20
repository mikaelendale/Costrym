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
        Schema::create('task_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('agent_name'); // Which agent will execute this
            $table->integer('priority')->default(0); // Higher = more important
            $table->json('payload'); // Task data and parameters
            $table->integer('attempts')->default(0); // Number of execution attempts
            $table->integer('max_attempts')->default(3); // Max retry attempts
            $table->timestamp('scheduled_at')->nullable(); // For scheduled tasks
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('result')->nullable(); // Execution result
            $table->text('error')->nullable(); // Error message if failed
            $table->timestamps();

            $table->index(['user_id', 'scheduled_at']);
            $table->index(['priority', 'scheduled_at']);
            $table->index(['agent_name', 'scheduled_at']);
        });

        // Add status column with constraint
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE task_queue ADD COLUMN status VARCHAR(255) DEFAULT \'queued\'');
            DB::statement("ALTER TABLE task_queue ADD CONSTRAINT task_queue_status_check CHECK (status IN ('queued', 'processing', 'completed', 'failed', 'retrying'))");
        } else {
            Schema::table('task_queue', function (Blueprint $table) {
                $table->string('status')->default('queued');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_queue');
    }
};
