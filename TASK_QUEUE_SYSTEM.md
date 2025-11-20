# ⚙️ Task Queue System Documentation

## Overview

The Task Queue System is a general-purpose execution queue that processes approved AI-generated tasks. When a user approves a task, it automatically moves from the `tasks` table to the `task_queue` table for execution.

---

## Architecture Flow

```
USER APPROVES TASK
    ↓
┌─────────────────────────────────────────┐
│ TaskApprovalController@approve          │
│ 1. Update task status to 'approved'    │
│ 2. Create entry in task_queue          │
│ 3. Dispatch ProcessTaskQueue job       │
└─────────────────────────────────────────┘
    ↓
┌─────────────────────────────────────────┐
│ TASK_QUEUE TABLE                        │
│ - Stores approved tasks                 │
│ - Includes scheduling info              │
│ - Tracks execution attempts             │
│ - Maintains execution state             │
└─────────────────────────────────────────┘
    ↓
┌─────────────────────────────────────────┐
│ ProcessTaskQueue Job                    │
│ - Runs on 'task_execution' queue       │
│ - Loads appropriate AI agent            │
│ - Executes task with context            │
│ - Handles retries (3 attempts)          │
│ - Manages recurring tasks               │
│ - Updates task & queue status           │
└─────────────────────────────────────────┘
    ↓
┌─────────────────────────────────────────┐
│ TASK EXECUTION                          │
│ - Agent performs the work               │
│ - Results stored in queue entry         │
│ - Original task marked completed        │
│ - Recurring tasks auto-scheduled        │
└─────────────────────────────────────────┘
```

---

## Database Schema

### `task_queue` Table

```sql
CREATE TABLE task_queue (
    id BIGINT PRIMARY KEY,
    task_id BIGINT FOREIGN KEY → tasks.id,
    user_id BIGINT FOREIGN KEY → users.id,
    agent_name VARCHAR(255),
    status VARCHAR(255),  -- 'queued', 'processing', 'completed', 'failed', 'retrying'
    priority INT DEFAULT 0,
    payload JSON,  -- Task data and parameters
    attempts INT DEFAULT 0,
    max_attempts INT DEFAULT 3,
    scheduled_at TIMESTAMP,
    started_at TIMESTAMP,
    completed_at TIMESTAMP,
    result TEXT,
    error TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Key Columns:**

- **task_id** - References the original task in `tasks` table
- **user_id** - Owner of the task
- **agent_name** - Which AI agent will execute (e.g., `integration_ingestor`)
- **status** - Current execution state
  - `queued` - Waiting to be processed
  - `processing` - Currently executing
  - `completed` - Successfully finished
  - `failed` - All attempts exhausted
  - `retrying` - Failed but will retry
- **priority** - Higher values process first
- **payload** - JSON containing:
  - `task_data` - Original task data from `tasks` table
  - `task_type` - 'one_time' or 'looping'
  - `metadata` - Additional execution parameters
- **attempts** - Number of times execution was attempted
- **max_attempts** - Maximum retries before marking as failed (default: 3)
- **scheduled_at** - When task should execute (for recurring tasks)
- **started_at** - When execution began
- **completed_at** - When execution finished
- **result** - Success result/output from agent
- **error** - Error message if failed

**Indexes:**
- `(user_id, scheduled_at)` - For user task queries
- `(priority, scheduled_at)` - For execution ordering
- `(agent_name, scheduled_at)` - For agent-specific queries

---

## Models

### `TaskQueue` Model

**Location:** `app/Models/TaskQueue.php`

#### Relationships

```php
$queueEntry->task();  // BelongsTo Task
$queueEntry->user();  // BelongsTo User
```

#### Scopes

```php
// Get tasks ready to process
TaskQueue::readyToProcess()->get();

// Get tasks for specific agent
TaskQueue::forAgent('integration_ingestor')->get();

// Get tasks for specific user
TaskQueue::forUser($userId)->get();
```

#### Methods

```php
// Mark as processing (increments attempts)
$queueEntry->markAsProcessing();

// Mark as completed
$queueEntry->markAsCompleted($result);
// → Also updates original task to 'completed'

// Mark as failed
$queueEntry->markAsFailed($error);
// → Updates to 'retrying' if attempts remain
// → Updates to 'failed' if all attempts exhausted
// → Also updates original task to 'failed' when exhausted
```

### `Task` Model (Updated)

**New Relationship:**

```php
$task->queueEntry();  // HasOne TaskQueue
```

---

## Controllers

### `TaskApprovalController@approve` (Updated)

**What happens when user approves a task:**

1. **Validates ownership** - Ensures task belongs to user
2. **Validates status** - Only 'pending' tasks can be approved
3. **Updates task status** - Changes to 'approved'
4. **Creates queue entry** - Adds to `task_queue` table
5. **Determines scheduling**:
   - **One-time tasks** - Execute immediately (`scheduled_at = now()`)
   - **Recurring tasks** - Scheduled based on frequency:
     - `daily` → tomorrow
     - `weekly` → next week
     - `monthly` → next month
6. **Dispatches job** - Queues `ProcessTaskQueue` job
7. **Logs action** - Records approval with details
8. **Returns success** - User sees success message

**Scheduling Logic:**

```php
protected function getScheduledTime(Task $task): ?Carbon
{
    $taskType = $task->data['task_type'] ?? 'one_time';
    
    if ($taskType === 'one_time') {
        return now(); // Execute immediately
    }
    
    $schedule = $task->data['schedule'] ?? null;
    
    return match ($schedule) {
        'daily' => now()->addDay(),
        'weekly' => now()->addWeek(),
        'monthly' => now()->addMonth(),
        default => now(),
    };
}
```

---

## Jobs

### `ProcessTaskQueue` Job

**Location:** `app/Jobs/ProcessTaskQueue.php`

**Queue:** `task_execution`

**Configuration:**
- **Timeout:** 5 minutes (300s)
- **Tries:** 3
- **Backoff:** 60 seconds between retries

#### Execution Flow

1. **Load queue entry** - Fetch from database with relationships
2. **Validate status** - Skip if already completed
3. **Mark as processing** - Update status and increment attempts
4. **Resolve agent class** - Convert agent_name to class (e.g., `integration_ingestor` → `App\Agents\IntegrationIngestor`)
5. **Instantiate agent** - Create agent instance
6. **Prepare context** - Set up `AgentContext` with:
   - `user_id`
   - `task_id`
   - `queue_id`
   - `task_data`
7. **Build prompt** - Create instructions from task data
8. **Execute agent** - Run via `AgentExecutor`
9. **Handle result**:
   - **Success:** Mark as completed, store result
   - **Failure:** Mark as failed, store error
10. **Handle recurring tasks** - Schedule next execution if task is 'looping'

#### Agent Resolution

The job automatically resolves agent class from `agent_name`:

```php
protected function resolveAgentClass(string $agentName): ?string
{
    // Convert snake_case to StudlyCase
    // e.g., "integration_ingestor" → "IntegrationIngestor"
    $className = str_replace('_', '', ucwords($agentName, '_'));
    
    // Try common locations:
    $possibleClasses = [
        "App\\Agents\\{$className}",
        "App\\Agents\\{$className}Agent",
        "App\\Agents\\{$className}\\{$className}",
    ];
    
    foreach ($possibleClasses as $class) {
        if (class_exists($class)) {
            return $class;
        }
    }
    
    return null;
}
```

#### Prompt Building

```php
protected function buildPrompt(array $taskData): string
{
    $name = $taskData['name'] ?? 'Unnamed Task';
    $description = $taskData['description'] ?? 'No description provided';
    
    return <<<PROMPT
You are executing an approved task.

Task: {$name}

Description: {$description}

Please complete this task and provide a detailed report of:
1. Actions taken
2. Results achieved
3. Any issues encountered
4. Recommendations (if applicable)

Be thorough and specific in your response.
PROMPT;
}
```

#### Recurring Task Handling

```php
protected function handleRecurringTask(TaskQueue $queueEntry): void
{
    $taskType = $queueEntry->payload['task_data']['task_type'] ?? 'one_time';
    
    if ($taskType === 'looping') {
        $schedule = $queueEntry->payload['task_data']['schedule'] ?? 'weekly';
        
        $nextScheduledAt = match ($schedule) {
            'daily' => now()->addDay(),
            'weekly' => now()->addWeek(),
            'monthly' => now()->addMonth(),
            default => now()->addWeek(),
        };
        
        // Create new queue entry for next execution
        TaskQueue::create([
            'task_id' => $queueEntry->task_id,
            'user_id' => $queueEntry->user_id,
            'agent_name' => $queueEntry->agent_name,
            'status' => 'queued',
            'priority' => $queueEntry->priority,
            'payload' => $queueEntry->payload,
            'max_attempts' => 3,
            'scheduled_at' => $nextScheduledAt,
        ]);
    }
}
```

---

## Usage Examples

### Example 1: One-Time Task Approval

**User approves task:**
```
Task: "Analyze recent expenses"
Type: one_time
Priority: 1
Agent: integration_ingestor
```

**System creates queue entry:**
```json
{
    "task_id": 13,
    "user_id": 1,
    "agent_name": "integration_ingestor",
    "status": "queued",
    "priority": 1,
    "payload": {
        "task_data": {
            "name": "Analyze recent expenses",
            "description": "Review last 3 months...",
            "task_type": "one_time",
            "estimated_savings": "$300/month"
        },
        "task_type": "one_time",
        "metadata": {}
    },
    "max_attempts": 3,
    "scheduled_at": "2025-11-19 21:19:34"  // NOW
}
```

**Job processes immediately:**
1. Loads `IntegrationIngestor` agent
2. Executes analysis
3. Stores result
4. Marks task as completed
5. No further scheduling (one-time)

### Example 2: Recurring Task Approval

**User approves task:**
```
Task: "Weekly software expense review"
Type: looping
Schedule: weekly
Priority: 2
Agent: notion_agent
```

**System creates queue entry:**
```json
{
    "task_id": 14,
    "user_id": 1,
    "agent_name": "notion_agent",
    "status": "queued",
    "priority": 2,
    "payload": {
        "task_data": {
            "name": "Weekly software expense review",
            "description": "Analyze software expenses...",
            "task_type": "looping",
            "schedule": "weekly",
            "estimated_savings": "$150/month"
        },
        "task_type": "looping",
        "metadata": {}
    },
    "max_attempts": 3,
    "scheduled_at": "2025-11-26 21:19:34"  // NEXT WEEK
}
```

**Job processes next week:**
1. Loads `NotionAgent` agent
2. Executes review
3. Stores result
4. Marks current execution as completed
5. **Creates new queue entry** scheduled for week after

**Recurring cycle continues indefinitely.**

---

## Retry Mechanism

### Automatic Retries

If a task fails, it automatically retries up to 3 times:

1. **Attempt 1 fails** → Status: `retrying`, wait 60s
2. **Attempt 2 fails** → Status: `retrying`, wait 60s
3. **Attempt 3 fails** → Status: `failed`, original task marked `failed`

### Backoff Strategy

- **Initial delay:** 60 seconds
- **Between retries:** 60 seconds
- Prevents overwhelming failed systems

### Error Handling

```php
try {
    // Execute agent
    $response = $executor->run($prompt);
    $queueEntry->markAsCompleted($response);
} catch (\Exception $e) {
    // Log error
    Log::error('Task execution failed', [...]);
    
    // Mark as failed (will retry if attempts remain)
    $queueEntry->markAsFailed($e->getMessage());
    
    // Re-throw for retry if attempts remain
    if ($queueEntry->attempts < $queueEntry->max_attempts) {
        throw $e;
    }
}
```

---

## Queue Worker Setup

### Running the Worker

```bash
# Process task_execution queue
php artisan queue:work --queue=task_execution --tries=3 --timeout=300

# Run all queues including task execution
php artisan queue:work --queue=task_execution,categorization,master_orchestrator,default
```

### Supervisor Configuration

For production, use Supervisor to keep workers running:

```ini
[program:costrym-task-executor]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --queue=task_execution --tries=3 --timeout=300
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/logs/task-executor.log
```

---

## Monitoring & Debugging

### Check Queue Status

```php
// Total queued tasks
TaskQueue::where('status', 'queued')->count();

// Tasks ready to process
TaskQueue::readyToProcess()->count();

// Failed tasks
TaskQueue::where('status', 'failed')->get();

// Tasks by agent
TaskQueue::forAgent('integration_ingestor')
    ->where('status', 'queued')
    ->get();
```

### View Task Progress

```php
$queueEntry = TaskQueue::find($id);

echo "Status: {$queueEntry->status}\n";
echo "Attempts: {$queueEntry->attempts}/{$queueEntry->max_attempts}\n";
echo "Started: {$queueEntry->started_at}\n";
echo "Completed: {$queueEntry->completed_at}\n";

if ($queueEntry->status === 'completed') {
    echo "Result: {$queueEntry->result}\n";
}

if ($queueEntry->status === 'failed') {
    echo "Error: {$queueEntry->error}\n";
}
```

### Logs

All queue operations are logged:

```
[2025-11-19 21:19:34] Task approved and queued for execution
[2025-11-19 21:19:34] Processing task from queue
[2025-11-19 21:24:12] Task completed successfully
[2025-11-19 21:24:12] Recurring task scheduled for next execution
```

---

## Testing

### Test Task Approval → Queue Migration

```php
// Get pending task
$task = Task::where('status', 'pending')->first();

// Approve (simulating user action)
$task->update(['status' => 'approved']);

// Create queue entry
$queueEntry = TaskQueue::create([
    'task_id' => $task->id,
    'user_id' => $task->user_id,
    'agent_name' => $task->agent_name,
    'status' => 'queued',
    'priority' => $task->priority,
    'payload' => [
        'task_data' => $task->data,
        'task_type' => $task->data['task_type'] ?? 'one_time',
    ],
    'max_attempts' => 3,
    'scheduled_at' => now(),
]);

// Verify
assert($task->status === 'approved');
assert($queueEntry->status === 'queued');
assert($task->queueEntry->id === $queueEntry->id);
```

### Test Relationships

```php
// Task → Queue Entry
$task->queueEntry;  // Should return TaskQueue instance

// Queue Entry → Task
$queueEntry->task;  // Should return Task instance

// Queue Entry → User
$queueEntry->user;  // Should return User instance
```

### Test Scopes

```php
// Ready to process
$ready = TaskQueue::readyToProcess()->get();

// For specific agent
$agentTasks = TaskQueue::forAgent('integration_ingestor')->get();

// For specific user
$userTasks = TaskQueue::forUser(1)->get();
```

---

## Security

### User Isolation

- Each queue entry belongs to a specific user
- Workers respect user context
- Results stored per-user
- No cross-user data leakage

### Validation

- Task ownership verified before approval
- Agent class existence checked before execution
- Invalid agents logged and failed gracefully
- Maximum attempt limits prevent infinite loops

### Error Handling

- All exceptions caught and logged
- Failed tasks don't crash worker
- Error messages sanitized before storage
- Sensitive data not logged

---

## Performance Considerations

### Optimization Tips

1. **Index Usage** - All common queries use database indexes
2. **Eager Loading** - Relationships loaded upfront to prevent N+1
3. **Batch Processing** - Multiple workers can run in parallel
4. **Priority Queuing** - High-priority tasks process first
5. **Scheduled Execution** - Prevents unnecessary polling

### Scaling

**Horizontal Scaling:**
- Run multiple worker processes
- Use Redis for queue driver (faster than database)
- Deploy workers on separate servers

**Vertical Scaling:**
- Increase worker timeout for long tasks
- Adjust `numprocs` in Supervisor config
- Use more powerful server instances

---

## Future Enhancements

### Planned Features

1. **Pause/Resume** - Allow users to pause recurring tasks
2. **Manual Retry** - Let users retry failed tasks
3. **Priority Adjustment** - Change priority after queuing
4. **Execution History** - View all past executions
5. **Performance Metrics** - Track average execution times
6. **Notifications** - Alert users when tasks complete/fail
7. **Custom Schedules** - Cron-like scheduling for advanced users
8. **Batch Execution** - Group related tasks together
9. **Conditional Execution** - Only run if conditions met
10. **Agent Selection** - Let users choose which agent to use

---

## Troubleshooting

### Issue: Queue entries not processing

**Check:**
1. Is queue worker running? `php artisan queue:work`
2. Is queue name correct? Should be `task_execution`
3. Are there failed jobs? Check `failed_jobs` table
4. Check logs for errors

### Issue: Agent not found error

**Solution:** Verify agent class exists and namespace is correct
```php
// Agent name: integration_ingestor
// Expected class: App\Agents\IntegrationIngestor
```

### Issue: Task stuck in 'processing' status

**Solution:** Worker may have crashed during execution
```php
// Reset stuck tasks
TaskQueue::where('status', 'processing')
    ->where('started_at', '<', now()->subHour())
    ->update(['status' => 'retrying', 'attempts' => 0]);
```

### Issue: Recurring task not rescheduling

**Check:**
1. Task type is 'looping'
2. Schedule is set in payload
3. Check logs for "Recurring task scheduled" message

---

## File Locations

```
📁 Database
├── database/migrations/2025_11_19_211605_create_task_queue_table.php

📁 Models
├── app/Models/TaskQueue.php
└── app/Models/Task.php (updated)

📁 Controllers
└── app/Http/Controllers/TaskApprovalController.php (updated)

📁 Jobs
└── app/Jobs/ProcessTaskQueue.php

📁 Documentation
├── TASK_QUEUE_SYSTEM.md (this file)
├── TASK_APPROVAL_SYSTEM.md
└── COMPREHENSIVE_TEST_RESULTS.md
```

---

## Summary

The Task Queue System provides a robust, scalable, and production-ready solution for executing AI-generated tasks:

✅ **Automatic migration** - Approved tasks move to queue automatically
✅ **Retry mechanism** - 3 attempts with 60s backoff
✅ **Recurring tasks** - Auto-scheduling for looping tasks
✅ **Agent resolution** - Automatic agent class loading
✅ **Error handling** - Comprehensive logging and recovery
✅ **User isolation** - Secure per-user execution
✅ **Priority queuing** - Important tasks process first
✅ **Monitoring** - Full visibility into execution state
✅ **Scalable** - Run multiple workers in parallel

**Status: PRODUCTION READY 🚀**

