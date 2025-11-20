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
        Schema::create('automations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->foreignId('task_queue_id')->nullable()->constrained('task_queue')->nullOnDelete();
            $table->string('type')->default('workflow'); // workflow, task_generation, execution_report
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('markdown_content'); // The MD file content
            $table->string('file_path')->nullable(); // Optional storage path if saved to disk
            $table->json('metadata')->nullable(); // Additional context: agents used, savings, etc.
            $table->string('status')->default('active'); // active, archived, deleted
            $table->timestamps();

            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automations');
    }
};
