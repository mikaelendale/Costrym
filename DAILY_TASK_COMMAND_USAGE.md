# ⏰ Daily Task Checker Command

## 📋 Command: `tasks:check-daily`

A powerful Artisan command to check and execute scheduled tasks for today.

---

## 🎯 Usage Examples

### 1. **View Today's Tasks Summary**
```bash
php artisan tasks:check-daily
```

**Output:**
- Shows a table of all tasks scheduled for today
- Displays priority, time, estimated savings
- Shows summary statistics

### 2. **Execute First 3 Tasks (Default)**
```bash
php artisan tasks:check-daily --first
# OR
php artisan tasks:check-daily --first=3
```

**What it does:**
- Dispatches only the first 3 tasks to the execution queue
- Shows how many tasks remain
- Useful for gradual execution

### 3. **Execute First N Tasks**
```bash
php artisan tasks:check-daily --first=2
php artisan tasks:check-daily --first=5
php artisan tasks:check-daily --first=1
```

**What it does:**
- Dispatches only the first N tasks
- Respects task priority order
- Perfect for testing or controlled execution

### 4. **Execute ALL Tasks for Today**
```bash
php artisan tasks:check-daily --dispatch
```

**What it does:**
- Dispatches ALL tasks scheduled for today
- No limit on number of tasks
- Use with caution if many tasks are scheduled

### 5. **Filter by Specific User**
```bash
php artisan tasks:check-daily --user=1
php artisan tasks:check-daily --user=1 --first=2
php artisan tasks:check-daily --user=1 --dispatch
```

**What it does:**
- Shows/dispatches tasks for specific user only
- Combine with `--first` or `--dispatch`

---

## 🔧 Available Options

| Option | Description | Example |
|--------|-------------|---------|
| `--dispatch` | Execute ALL tasks for today | `--dispatch` |
| `--first[=N]` | Execute only first N tasks (default: 3) | `--first=5` |
| `--user=ID` | Filter tasks for specific user | `--user=1` |
| `-h, --help` | Show help information | `--help` |

---

## 📊 Example Output

```bash
$ php artisan tasks:check-daily --first=2

🔍 Checking tasks scheduled for today...

📊 Tasks Scheduled for Today (Friday, Nov 21, 2025)

+----+---------------+----------------------------------------------------+----------+-------+------------+----------+
| ID | User          | Task                                               | Priority | Time  | Savings    | Type     |
+----+---------------+----------------------------------------------------+----------+-------+------------+----------+
| 24 | Mikael Endale | Analyze travel expenses                            | HIGH     | 09:00 | $400/month | one_time |
| 22 | Mikael Endale | Monthly telecom review                             | LOW      | 10:00 | $100/month | looping  |
| 23 | Mikael Endale | Check unused cloud resources                       | LOW      | 11:00 | $200/month | looping  |
+----+---------------+----------------------------------------------------+----------+-------+------------+----------+

📈 Summary:
   Total Tasks: 3
   High Priority: 1
   Medium Priority: 0
   Low Priority: 2
   Total Potential Savings: $700/month

🚀 Dispatching first 2 task(s) for execution...

✅ Dispatched: Analyze travel expenses
✅ Dispatched: Monthly telecom review

✅ Dispatched 2 task(s) for execution
💡 1 task(s) remaining. Use --dispatch to execute all.
```

---

## 🚀 Best Practices

### For Daily Use:
```bash
# Morning: Check what's scheduled
php artisan tasks:check-daily

# Execute first few tasks
php artisan tasks:check-daily --first=3

# Later: Execute remaining tasks
php artisan tasks:check-daily --dispatch
```

### For Testing:
```bash
# Test with just 1 task
php artisan tasks:check-daily --first=1

# Test specific user's tasks
php artisan tasks:check-daily --user=1 --first=1
```

### Scheduled in Laravel:
```php
// In app/Console/Kernel.php or routes/console.php

// Check daily at 6 AM
Schedule::command('tasks:check-daily')->dailyAt('06:00');

// Execute first 3 tasks at 7 AM
Schedule::command('tasks:check-daily --first=3')->dailyAt('07:00');

// Execute remaining tasks at 9 AM
Schedule::command('tasks:check-daily --dispatch')->dailyAt('09:00');
```

---

## 💡 Tips

1. **Start Small**: Use `--first=1` when testing new task types
2. **Gradual Rollout**: Execute `--first=3` in the morning, rest later
3. **Monitor**: Check logs after dispatching tasks
4. **User-Specific**: Use `--user=ID` for focused execution
5. **No Risk**: The command only dispatches to queue, tasks can still fail gracefully

---

## 🔄 Task Execution Flow

```
1. User approves tasks → Tasks added to task_queue
2. Task queue entries scheduled for specific dates/times
3. Daily checker finds tasks for today
4. Use --first to dispatch limited number
5. Tasks execute via ProcessTaskQueue job
6. MasterOrchestratorExecutor coordinates execution
7. Results saved as Markdown automations
8. User sees completed automations in /automations
```

---

## 🎯 Command Created!

**Status: ✅ FULLY FUNCTIONAL**

The `--first` option allows you to:
- Execute a controlled number of tasks
- Test task execution safely
- Gradually process task queue
- Prevent system overload
- Perfect for daily operations

**Default:** `--first` without a number defaults to 3 tasks.

