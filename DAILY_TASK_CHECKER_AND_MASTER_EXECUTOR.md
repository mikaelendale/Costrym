# 📅 Daily Task Checker & Master Orchestrator Executor System

## Overview

The system now features:
1. **Daily Task Checker** - Automated command to check and dispatch tasks scheduled for each day
2. **MasterOrchestrator as Executor** - All tasks executed through MasterOrchestrator which delegates to specialized agents
3. **Config-Based Agent System** - All available agents defined in `config/agents.php`

---

## 🎯 System Architecture

```
DAILY SCHEDULE (7 AM)
    ↓
┌─────────────────────────────────────────┐
│ tasks:check-daily --dispatch            │
│ Runs automatically every morning        │
└─────────────────────────────────────────┘
    ↓
┌─────────────────────────────────────────┐
│ Find all tasks scheduled for today      │
│ - Priority: High → Medium → Low         │
│ - Time: Earliest → Latest               │
└─────────────────────────────────────────┘
    ↓
┌─────────────────────────────────────────┐
│ Dispatch ProcessTaskQueue jobs          │
│ One job per task                        │
└─────────────────────────────────────────┘
    ↓
┌─────────────────────────────────────────┐
│ ProcessTaskQueue Job                    │
│ Uses: MasterOrchestrator as executor   │
└─────────────────────────────────────────┘
    ↓
┌─────────────────────────────────────────┐
│ MasterOrchestrator                      │
│ - Receives task details                 │
│ - Has access to all agents              │
│ - Delegates to best agent(s)            │
│ - Coordinates execution                 │
│ - Synthesizes results                   │
└─────────────────────────────────────────┘
    ↓
┌─────────────────────────────────────────┐
│ SUB-AGENTS (from config)                │
│ - IntegrationIngestor                   │
│ - CategorizerAgent                      │
│ - CostOptimizerAgent                    │
│ - NotionAgent                           │
│ - AutomationOrchestrator                │
└─────────────────────────────────────────┘
    ↓
✅ TASK COMPLETED WITH RESULTS
```

---

## 📋 Daily Task Checker Command

### Command: `tasks:check-daily`

**Purpose:** Check and optionally dispatch tasks scheduled for today

**Usage:**

```bash
# View today's tasks (summary only)
php artisan tasks:check-daily

# Dispatch tasks for execution
php artisan tasks:check-daily --dispatch

# Check tasks for specific user
php artisan tasks:check-daily --user=1

# Dispatch for specific user
php artisan tasks:check-daily --dispatch --user=1
```

### Features

✅ **Shows tasks for today** - Displays all tasks scheduled for current date
✅ **Priority ordering** - High → Medium → Low
✅ **Time-based** - Shows execution time for each task
✅ **Savings summary** - Calculates total potential savings
✅ **User filtering** - Can check tasks for specific users
✅ **Dispatch mode** - Actually executes the tasks

### Example Output

```
🔍 Checking tasks scheduled for today...

📊 Tasks Scheduled for Today (Thursday, Nov 20, 2025)

┌────┬──────────┬─────────────────────────────────────┬──────────┬───────┬────────────┬──────────┐
│ ID │ User     │ Task                                │ Priority │ Time  │ Savings    │ Type     │
├────┼──────────┼─────────────────────────────────────┼──────────┼───────┼────────────┼──────────┤
│ 1  │ John Doe │ Analyze cloud infrastructure costs  │ HIGH     │ 09:00 │ $500/month │ one_time │
│ 2  │ John Doe │ Review software subscriptions       │ MEDIUM   │ 14:30 │ $300/month │ one_time │
│ 3  │ John Doe │ Check unused licenses               │ LOW      │ 16:45 │ $150/month │ one_time │
└────┴──────────┴─────────────────────────────────────┴──────────┴───────┴────────────┴──────────┘

📈 Summary:
   Total Tasks: 3
   High Priority: 1
   Medium Priority: 1
   Low Priority: 1
   Total Potential Savings: $950/month

💡 Use --dispatch flag to execute these tasks
```

### Automated Schedule

Set up in `app/Console/Kernel.php`:

```php
// Dispatch tasks every morning at 7 AM
$schedule->command('tasks:check-daily --dispatch')
    ->dailyAt('07:00')
    ->timezone('UTC');

// Optional: Summary at 6 AM (no dispatch)
$schedule->command('tasks:check-daily')
    ->dailyAt('06:00')
    ->timezone('UTC');
```

**To enable scheduler:**

```bash
# Add to crontab
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🤖 MasterOrchestrator as Executor

### New Execution Flow

**Old way (Direct Agent):**
```
Task → ProcessTaskQueue → AgentSelector → SpecificAgent → Result
```

**New way (MasterOrchestrator):**
```
Task → ProcessTaskQueue → MasterOrchestrator → Delegates to Agent(s) → Result
```

### Why MasterOrchestrator?

✅ **Intelligent Coordination** - Can use multiple agents for complex tasks
✅ **Dynamic Decision Making** - Chooses best agent(s) at execution time
✅ **Result Synthesis** - Combines outputs from multiple agents
✅ **Flexible Execution** - Can adapt strategy based on task requirements
✅ **Better Context** - Understands full task scope

### How It Works

1. **Task Scheduled** - User approves task, scheduled for Day X
2. **Daily Checker** - Finds task on Day X at 7 AM
3. **Job Dispatched** - ProcessTaskQueue job created
4. **MasterOrchestrator Invoked** - Receives task details + available agents
5. **Delegation** - MasterOrchestrator uses `delegate_to_sub_agent` tool
6. **Execution** - Specialized agent(s) execute work
7. **Synthesis** - MasterOrchestrator compiles comprehensive report
8. **Storage** - Results saved to queue entry

### Example Execution

**Task:** "Analyze software subscriptions and create optimization report"

**MasterOrchestrator's Plan:**
1. Delegate to `integration_ingestor` → Fetch subscription data
2. Delegate to `cost_optimizer_agent` → Analyze for savings
3. Delegate to `notion_agent` → Create formatted report
4. Synthesize → Comprehensive savings report with action items

**Single Result:** Complete analysis + report + recommendations

---

## ⚙️ Config-Based Agent System

### Location: `config/agents.php`

All agents now defined in centralized config file.

### Structure

```php
'available_agents' => [
    'agent_key' => [
        'class' => \App\Agents\AgentClass::class,
        'name' => 'Human Readable Name',
        'description' => 'What this agent does',
        'capabilities' => ['keyword1', 'keyword2', ...],
        'enabled' => true, // Can disable agents
    ],
]
```

### Available Agents

| Agent | Status | Capabilities |
|-------|--------|--------------|
| **integration_ingestor** | ✅ Enabled | data ingestion, API integration, Xero, QuickBooks |
| **categorizer_agent** | ✅ Enabled | categorization, classification, expense categorization |
| **cost_optimizer_agent** | ✅ Enabled | cost optimization, savings, expense reduction |
| **notion_agent** | ✅ Enabled | documentation, reports, Notion content |
| **automation_orchestrator** | ✅ Enabled | automation, workflow automation |
| **onboarding_agent** | ⚠️ Disabled | onboarding, user setup (not for task execution) |

### Configuration Options

```php
'task_execution' => [
    'use_master_orchestrator' => true,  // Use MasterOrchestrator
    'direct_agent_execution' => false,  // Legacy mode
    'max_execution_time' => 300,        // 5 minutes
    'max_retries' => 3,
],

'master_orchestrator' => [
    'enabled' => true,
    'class' => \App\Agents\MasterOrchestrator::class,
    'description' => 'Central coordinator',
    'max_delegation_depth' => 3,  // Prevent infinite loops
],
```

### Adding New Agents

To add a new agent:

1. **Create agent class**
2. **Add to config:**

```php
'my_new_agent' => [
    'class' => \App\Agents\MyNewAgent::class,
    'name' => 'My New Agent',
    'description' => 'Does amazing things',
    'capabilities' => ['amazing', 'incredible', 'fantastic'],
    'enabled' => true,
],
```

3. **Done!** MasterOrchestrator can now delegate to it

### Disabling Agents

Set `'enabled' => false` to temporarily disable:

```php
'onboarding_agent' => [
    // ... 
    'enabled' => false, // Won't be used for task execution
],
```

---

## 🔄 Complete Daily Flow

### Morning: 7 AM

```
1. Scheduler triggers: tasks:check-daily --dispatch
   ↓
2. Command finds 3 tasks scheduled for today:
   - 09:00: High priority task
   - 14:30: Medium priority task
   - 16:45: Low priority task
   ↓
3. Dispatches 3 ProcessTaskQueue jobs to 'task_execution' queue
   ↓
4. Queue worker picks up jobs throughout the day
```

### 9:00 AM - First Task

```
ProcessTaskQueue Job runs:
   ↓
Checks config: use_master_orchestrator = true
   ↓
Loads MasterOrchestrator with:
   - Task details
   - All available agents from config
   - User context
   ↓
MasterOrchestrator analyzes task:
   "Analyze cloud infrastructure costs"
   ↓
Decides: Use cost_optimizer_agent
   ↓
Delegates via delegate_to_sub_agent tool
   ↓
CostOptimizerAgent executes analysis
   ↓
MasterOrchestrator receives results
   ↓
Compiles comprehensive report:
   - Findings: $500/month potential savings
   - Specific recommendations
   - Action items
   ↓
Stores result in task_queue
   ↓
Marks task as completed
```

### 2:30 PM - Second Task

```
(Same flow, different task)
```

### 4:45 PM - Third Task

```
(Same flow, different task)
```

### End of Day

```
All 3 tasks completed
Results available for user review
Recurring tasks auto-scheduled for next execution
```

---

## 🧪 Testing

### Test 1: Daily Checker

```php
php artisan tinker

// Create test tasks for today
$task = Task::create([...]);
TaskQueue::create([
    'scheduled_at' => today()->setTime(14, 0, 0),
    ...
]);

// Run checker
exit
php artisan tasks:check-daily
```

### Test 2: Config Loading

```php
php artisan tinker

config('agents.available_agents');
// Shows all agents

config('agents.task_execution.use_master_orchestrator');
// true
```

### Test 3: Full Execution

```bash
# Create task, approve it, wait for scheduled time
php artisan queue:work --queue=task_execution --stop-when-empty
```

---

## 📊 Monitoring

### Check Today's Tasks

```bash
php artisan tasks:check-daily
```

### Check Queue

```bash
php artisan queue:monitor task_execution
```

### View Schedule

```bash
php artisan schedule:list
```

Expected output:
```
0 6 * * * php artisan tasks:check-daily ........ Next Due: Tomorrow at 6:00 AM
0 7 * * * php artisan tasks:check-daily --dispatch . Next Due: Tomorrow at 7:00 AM
```

---

## 🎯 Key Benefits

### 1. **Automated Daily Execution**
- No manual intervention needed
- Tasks execute automatically at scheduled times
- Morning checker ensures nothing is missed

### 2. **Intelligent Orchestration**
- MasterOrchestrator coordinates complex tasks
- Can use multiple agents for one task
- Synthesizes comprehensive results

### 3. **Centralized Configuration**
- All agents in one config file
- Easy to enable/disable agents
- Simple to add new agents

### 4. **Flexible & Extensible**
- Can switch between MasterOrchestrator and direct execution
- Config-driven behavior
- Easy to customize

### 5. **Transparent Execution**
- Clear logs of what executed when
- Summary reports available
- Easy monitoring and debugging

---

## 🚀 Summary

The new system provides:

✅ **Daily Task Checker** - `php artisan tasks:check-daily --dispatch`
✅ **MasterOrchestrator Executor** - Intelligent task coordination
✅ **Config-Based Agents** - Centralized agent management
✅ **Automated Schedule** - Runs every morning at 7 AM
✅ **Multi-Agent Coordination** - Complex tasks use multiple agents
✅ **Comprehensive Reporting** - Synthesized results from all agents

**Status: PRODUCTION READY 🚀**

Tasks are automatically checked, dispatched, and executed with intelligent orchestration!

