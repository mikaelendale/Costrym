<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TaskApprovalController extends Controller
{
    /**
     * Approve a task for execution
     */
    public function approve(Request $request, Task $task)
    {
        // Ensure the task belongs to the authenticated user
        if ($task->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized to approve this task');
        }

        // Ensure the task is in pending status
        if ($task->status !== 'pending') {
            return back()->with('error', 'Only pending tasks can be approved');
        }

        // Update task status to approved
        $task->update([
            'status' => 'approved',
        ]);

        // Calculate scheduled time (4-5 days from now for initial execution)
        $scheduledAt = $this->getScheduledTime($task);

        // Move to general task queue for execution
        $queueEntry = \App\Models\TaskQueue::create([
            'task_id' => $task->id,
            'user_id' => $task->user_id,
            'agent_name' => null, // Agent will be selected dynamically
            'status' => 'queued',
            'priority' => $task->priority,
            'payload' => [
                'task_data' => $task->data,
                'task_type' => $task->data['task_type'] ?? 'one_time',
                'metadata' => $task->data['metadata'] ?? [],
            ],
            'max_attempts' => 3,
            'scheduled_at' => $scheduledAt,
        ]);

        Log::info('Task approved and queued for execution', [
            'task_id' => $task->id,
            'queue_id' => $queueEntry->id,
            'user_id' => $request->user()->id,
            'task_name' => $task->data['name'] ?? 'Unknown',
            'agent_selection' => 'dynamic',
            'scheduled_at' => $queueEntry->scheduled_at,
        ]);

        // Dispatch the processor job with delay
        \App\Jobs\ProcessTaskQueue::dispatch($queueEntry->id)
            ->onQueue('task_execution')
            ->delay($scheduledAt);

        return back()->with('success', 'Task approved successfully and queued for execution');
    }

    /**
     * Determine when the task should be scheduled
     * Distributes tasks across 4-5 days based on priority and existing queue
     */
    protected function getScheduledTime(Task $task): ?\Carbon\Carbon
    {
        // Get all pending queue entries for this user to distribute evenly
        $existingQueueCount = \App\Models\TaskQueue::where('user_id', $task->user_id)
            ->where('status', 'queued')
            ->where('scheduled_at', '>=', now())
            ->where('scheduled_at', '<=', now()->addDays(5))
            ->count();

        // Distribute across 4-5 days based on how many tasks already queued
        // This spreads the load: some tomorrow, some in 2 days, etc.
        $dayOffset = ($existingQueueCount % 5) + 1; // Cycles through 1-5 days

        // High priority tasks get earlier slots
        if ($task->priority === 1) {
            $dayOffset = min($dayOffset, 2); // High priority: 1-2 days
        } elseif ($task->priority === 2) {
            $dayOffset = min($dayOffset, 3); // Medium priority: 1-3 days
        }
        // Low priority: can be any day 1-5

        // Add some randomization within the day to avoid all tasks at same time
        $hours = rand(8, 18); // Between 8 AM and 6 PM
        $minutes = rand(0, 59);

        return now()->addDays($dayOffset)->setTime($hours, $minutes, 0);
    }

    /**
     * Reject a task
     */
    public function reject(Request $request, Task $task)
    {
        // Ensure the task belongs to the authenticated user
        if ($task->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized to reject this task');
        }

        // Ensure the task is in pending status
        if ($task->status !== 'pending') {
            return back()->with('error', 'Only pending tasks can be rejected');
        }

        // Update task status to cancelled
        $task->update([
            'status' => 'cancelled',
        ]);

        Log::info('Task rejected by user', [
            'task_id' => $task->id,
            'user_id' => $request->user()->id,
            'task_name' => $task->data['name'] ?? 'Unknown',
        ]);

        return back()->with('success', 'Task rejected');
    }

    /**
     * Get pending tasks for the authenticated user
     */
    public function index(Request $request)
    {
        $pendingTasks = Task::where('user_id', $request->user()->id)
            ->where('status', 'pending')
            ->orderBy('priority')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'tasks' => $pendingTasks,
        ]);
    }
}
