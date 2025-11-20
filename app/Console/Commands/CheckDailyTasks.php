<?php

namespace App\Console\Commands;

use App\Jobs\ProcessTaskQueue;
use App\Models\TaskQueue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckDailyTasks extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tasks:check-daily 
                            {--dispatch : Dispatch tasks immediately instead of showing summary}
                            {--user= : Check tasks for specific user ID}';

    /**
     * The console command description.
     */
    protected $description = 'Check and display tasks scheduled for today, optionally dispatch them for execution';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Checking tasks scheduled for today...');
        $this->newLine();

        // Get tasks scheduled for today
        $query = TaskQueue::where('status', 'queued')
            ->whereDate('scheduled_at', today())
            ->orderBy('priority', 'desc')
            ->orderBy('scheduled_at');

        // Filter by user if specified
        if ($userId = $this->option('user')) {
            $query->where('user_id', $userId);
        }

        $todaysTasks = $query->with(['task', 'user'])->get();

        if ($todaysTasks->isEmpty()) {
            $this->warn('📭 No tasks scheduled for today!');

            return self::SUCCESS;
        }

        // Display summary
        $this->displayTasksSummary($todaysTasks);

        // Dispatch if requested
        if ($this->option('dispatch')) {
            $this->newLine();
            $this->info('🚀 Dispatching tasks for execution...');
            $this->newLine();

            $dispatched = 0;
            foreach ($todaysTasks as $queueEntry) {
                try {
                    ProcessTaskQueue::dispatch($queueEntry->id)
                        ->onQueue('task_execution');

                    $this->line("✅ Dispatched: {$queueEntry->task->data['name']}");
                    $dispatched++;

                    Log::info('Daily task checker dispatched task', [
                        'queue_id' => $queueEntry->id,
                        'task_id' => $queueEntry->task_id,
                        'user_id' => $queueEntry->user_id,
                    ]);
                } catch (\Exception $e) {
                    $this->error("❌ Failed to dispatch task {$queueEntry->id}: {$e->getMessage()}");
                    Log::error('Daily task checker failed to dispatch', [
                        'queue_id' => $queueEntry->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->newLine();
            $this->info("✅ Dispatched {$dispatched} task(s) for execution");
        } else {
            $this->newLine();
            $this->comment('💡 Use --dispatch flag to execute these tasks');
        }

        return self::SUCCESS;
    }

    /**
     * Display tasks summary table
     */
    protected function displayTasksSummary($tasks): void
    {
        $this->info('📊 Tasks Scheduled for Today ('.today()->format('l, M d, Y').')');
        $this->newLine();

        $rows = [];
        foreach ($tasks as $queueEntry) {
            $task = $queueEntry->task;
            $priority = $this->getPriorityLabel($queueEntry->priority);
            $savings = $task->data['estimated_savings'] ?? 'N/A';
            $time = $queueEntry->scheduled_at->format('H:i');

            $rows[] = [
                $queueEntry->id,
                $queueEntry->user->name ?? "User {$queueEntry->user_id}",
                $task->data['name'],
                $priority,
                $time,
                $savings,
                $task->data['task_type'] ?? 'one_time',
            ];
        }

        $this->table(
            ['ID', 'User', 'Task', 'Priority', 'Time', 'Savings', 'Type'],
            $rows
        );

        // Summary stats
        $this->newLine();
        $totalSavings = 0;
        foreach ($tasks as $queueEntry) {
            $savings = $queueEntry->task->data['estimated_savings'] ?? '';
            if (preg_match('/\$(\d+)/', $savings, $matches)) {
                $totalSavings += (int) $matches[1];
            }
        }

        $highPriority = $tasks->where('priority', 1)->count();
        $mediumPriority = $tasks->where('priority', 2)->count();
        $lowPriority = $tasks->where('priority', 3)->count();

        $this->info('📈 Summary:');
        $this->line("   Total Tasks: {$tasks->count()}");
        $this->line("   High Priority: {$highPriority}");
        $this->line("   Medium Priority: {$mediumPriority}");
        $this->line("   Low Priority: {$lowPriority}");
        $this->line("   Total Potential Savings: \${$totalSavings}/month");
    }

    /**
     * Get priority label with color
     */
    protected function getPriorityLabel(int $priority): string
    {
        return match ($priority) {
            1 => '<fg=red>HIGH</>',
            2 => '<fg=yellow>MEDIUM</>',
            3 => '<fg=green>LOW</>',
            default => 'UNKNOWN',
        };
    }
}
