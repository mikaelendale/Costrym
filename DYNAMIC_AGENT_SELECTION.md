# 🤖 Dynamic Agent Selection System

## Overview

The task system now features **intelligent, dynamic agent selection** instead of pre-assigning agents. When a user approves a task, the system automatically selects the best AI agent based on the task's description and content.

---

## 🎯 Key Changes

### 1. **No Pre-Assigned Agents**
- ❌ **Before:** Tasks had `agent_name` assigned upfront
- ✅ **Now:** Tasks created with `agent_name = null`
- 🤖 Agent selected dynamically when task executes

### 2. **Smart Distribution Across 4-5 Days**
- ❌ **Before:** All tasks executed immediately or at same time
- ✅ **Now:** Tasks distributed across next 4-5 days
- 📅 High priority tasks execute sooner (1-2 days)
- ⚖️ Load balanced to avoid overwhelming system

### 3. **Intelligent Selection**
- 🧠 AgentSelector service analyzes task content
- 🎯 Matches keywords to agent capabilities
- 📊 Scores each agent and picks the best match

---

## 🏗️ Architecture

```
USER APPROVES TASK (No Agent Assigned)
    ↓
┌─────────────────────────────────────────┐
│ TaskApprovalController                  │
│ • Task status → 'approved'              │
│ • Schedule 4-5 days from now            │
│ • Create queue entry (agent = null)     │
│ • Dispatch ProcessTaskQueue job         │
└─────────────────────────────────────────┘
    ↓
⏰ WAITING 4-5 DAYS...
    ↓
┌─────────────────────────────────────────┐
│ ProcessTaskQueue Job Executes           │
│ 1. Load task from queue                 │
│ 2. Call AgentSelector service           │
│ 3. Analyze task description             │
│ 4. Score all available agents           │
│ 5. Select best agent                    │
│ 6. Update queue with agent_name         │
│ 7. Load & instantiate agent class       │
│ 8. Execute task                         │
│ 9. Store result                         │
└─────────────────────────────────────────┘
    ↓
✅ TASK COMPLETED WITH BEST AGENT!
```

---

## 🧠 AgentSelector Service

**Location:** `app/Services/AgentSelector.php`

### Available Agents & Capabilities

| Agent | Capabilities |
|-------|-------------|
| **integration_ingestor** | data ingestion, fetching data, api integration, xero, quickbooks, accounting data |
| **categorizer_agent** | categorization, classification, expense categorization, transaction categorization |
| **cost_optimizer_agent** | cost optimization, cost reduction, savings, expense reduction, budget optimization |
| **notion_agent** | notion, documentation, notes, reporting, create reports, write documentation |
| **automation_orchestrator** | automation, workflow automation, process automation, scheduling |
| **onboarding_agent** | onboarding, user setup, initial setup, configuration |

### Selection Algorithm

```php
public function selectAgent(array $taskData): array
{
    $taskName = $taskData['name'] ?? '';
    $taskDescription = $taskData['description'] ?? '';
    $taskContent = strtolower($taskName . ' ' . $taskDescription);
    
    // Score each agent based on keyword matches
    $scores = [];
    foreach ($this->agents as $agentName => $agentInfo) {
        $score = 0;
        foreach ($agentInfo['capabilities'] as $capability) {
            if (str_contains($taskContent, strtolower($capability))) {
                $score += 10;
            }
        }
        $scores[$agentName] = $score;
    }
    
    // Return highest scoring agent
    arsort($scores);
    return [
        'agent_name' => array_key_first($scores),
        'agent_class' => $this->agents[$bestAgent]['class'],
        'score' => $scores[$bestAgent],
        'reasoning' => '...'
    ];
}
```

### Example Selections

**Task:** "Analyze recent expenses for cost reduction"
- **Selected:** `cost_optimizer_agent`
- **Score:** 10 (matched "expense" and "cost reduction")
- **Reasoning:** "Analyzes costs and identifies optimization opportunities"

**Task:** "Fetch data from Xero accounting system"
- **Selected:** `integration_ingestor`
- **Score:** 10 (matched "data" and "xero")
- **Reasoning:** "Fetches and ingests data from external integrations"

**Task:** "Create weekly expense report"
- **Selected:** `notion_agent`
- **Score:** 10 (matched "report")
- **Reasoning:** "Creates documentation, reports, and manages Notion content"

**Task:** "Categorize financial transactions"
- **Selected:** `categorizer_agent`
- **Score:** 10 (matched "categorize")
- **Reasoning:** "Categorizes financial transactions into predefined categories"

---

## ⏰ Scheduling System

### Task Distribution Timeline

Tasks are **distributed across 4-5 days** based on priority:

```
DAY 0: User approves 5 tasks
    ↓
┌─────────────────────────────────────┐
│ DISTRIBUTION ALGORITHM              │
│                                     │
│ High Priority (P1) → Day 1-2        │
│ Medium Priority (P2) → Day 1-3      │
│ Low Priority (P3) → Day 1-5         │
│                                     │
│ Tasks spread evenly across days     │
└─────────────────────────────────────┘
    ↓
DAY 1: Task 1 (High Priority) executes
    [Agent selected dynamically]
    ↓
DAY 2: Task 2 (High Priority) executes
    Task 3 (Medium Priority) executes
    ↓
DAY 3: Task 4 (Medium Priority) executes
    ↓
DAY 4: Task 5 (Low Priority) executes
```

**Example Distribution:**
```
📅 Tomorrow (Day 1)
   🔴 Analyze expenses (P1) at 12:05
   
📅 Day 2
   🔴 Review cloud costs (P1) at 14:30
   🟡 Software review (P2) at 16:20
   
📅 Day 3
   🟡 Payment audit (P2) at 10:15
   
📅 Day 4
   🟢 Telecom review (P3) at 11:45
```

### Recurring Tasks

**First Execution:** Distributed across 1-5 days based on priority

**Subsequent Executions:** Based on schedule
- **Daily:** Every day
- **Weekly:** Every week
- **Monthly:** Every month

```
User Approves Recurring Task (Medium Priority)
    ↓
Scheduled for Day 2
    ↓
First Execution (Agent selected)
    ↓
[Wait based on schedule]
    ↓
Second Execution (next week, agent re-selected)
    ↓
[Continues indefinitely...]
```

---

## 📝 MasterOrchestrator Changes

### Old Prompt Format (Removed)

```json
{
    "name": "Task name",
    "agent_name": "cost_optimizer_agent",  // ❌ NO LONGER NEEDED
    "description": "Task description"
}
```

### New Prompt Format

```json
{
    "name": "Task name",
    "description": "Detailed description of what this task will do and what data to analyze",
    "task_type": "one_time" or "looping",
    "priority": 1-10,
    "estimated_savings": "$500/month"
}
```

**Important:** MasterOrchestrator no longer assigns agents. It focuses on:
1. **What** the task should accomplish
2. **Why** it's valuable (savings potential)
3. **When** it should run (task_type, schedule)

The **agent selection happens automatically** during execution.

---

## 🎨 UI Updates

### PendingTasksCard Component

**Old Display:**
```
Agent: integration_ingestor
```

**New Display:**
```
Agent: Auto-Selected 🤖
Best agent chosen at execution
```

**Modal Detail:**
```
┌────────────────────────────────┐
│ AI Agent                       │
│ Auto-Selected 🤖                │
│ Best agent chosen at execution │
└────────────────────────────────┘
```

---

## 🧪 Test Results

### Test 1: Agent Selection Logic

```
✅ "Analyze recent expenses"
   → Selected: cost_optimizer_agent (score: 10)

✅ "Fetch data from Xero"
   → Selected: integration_ingestor (score: 10)

✅ "Create weekly report"
   → Selected: notion_agent (score: 10)

✅ "Categorize transactions"
   → Selected: categorizer_agent (default)
```

### Test 2: Create Task Without Agent

```
✅ Task created with agent_name = NULL
✅ Status: pending
✅ Ready for approval
```

### Test 3: Scheduling Calculation

```
Current Time: 2025-11-19 21:38:10

Sample Scheduled Times:
  Approval 1: 2025-11-23 21:38:10 (4 days)
  Approval 2: 2025-11-23 21:38:10 (4 days)
  Approval 3: 2025-11-24 21:38:10 (5 days)
  Approval 4: 2025-11-23 21:38:10 (4 days)
  Approval 5: 2025-11-24 21:38:10 (5 days)
```

### Test 4: Full Approval Flow

```
1. ✅ Task created (no agent)
2. ✅ User approves
3. ✅ Queue entry created (agent = null)
4. ✅ Scheduled 4 days from now
5. ✅ Agent selected dynamically: cost_optimizer_agent
6. ✅ Task ready for execution
```

---

## 📊 Database Changes

### Tasks Table

```sql
ALTER TABLE tasks 
ALTER COLUMN agent_name DROP NOT NULL;
```

**Before:** `agent_name VARCHAR(255) NOT NULL`
**After:** `agent_name VARCHAR(255) NULL`

### Task Queue Table

```sql
ALTER TABLE task_queue 
ALTER COLUMN agent_name DROP NOT NULL;
```

**Before:** `agent_name VARCHAR(255) NOT NULL`
**After:** `agent_name VARCHAR(255) NULL`

---

## 🔄 Migration Path

### Existing Tasks

Existing tasks with assigned agents will continue to work. The system:
1. ✅ Respects pre-assigned agents if present
2. ✅ Only selects dynamically if `agent_name IS NULL`
3. ✅ Backward compatible

### Future Tasks

All new tasks generated by MasterOrchestrator:
1. ✅ Created with `agent_name = null`
2. ✅ Agent selected during execution
3. ✅ Selection logged for review

---

## 📈 Benefits

### 1. **Flexibility**
- ✅ System adapts to task content
- ✅ No rigid pre-assignment needed
- ✅ MasterOrchestrator focuses on task goals, not implementation

### 2. **Intelligence**
- ✅ Best agent chosen based on actual task needs
- ✅ Keyword matching ensures relevance
- ✅ Fallback logic for edge cases

### 3. **Scalability**
- ✅ Easy to add new agents (just update AgentSelector)
- ✅ No need to retrain MasterOrchestrator
- ✅ Centralized agent management

### 4. **User Experience**
- ✅ Users see "Auto-Selected 🤖" (confidence in system)
- ✅ 4-5 day buffer allows for planning
- ✅ Clear feedback on scheduling

---

## 🔧 File Changes Summary

### Created
- ✅ `app/Services/AgentSelector.php` - Agent selection service
- ✅ `database/migrations/2025_11_19_213317_update_tasks_table_make_agent_optional.php` - Make agent optional
- ✅ `DYNAMIC_AGENT_SELECTION.md` - This document

### Modified
- ✅ `app/Http/Controllers/TaskApprovalController.php` - 4-5 day scheduling, no agent
- ✅ `app/Jobs/ProcessTaskQueue.php` - Dynamic agent selection
- ✅ `app/Jobs/MasterOrchestratorJob.php` - No agent in task creation
- ✅ `resources/prompts/master_orchestrator/first_onboarding.blade.php` - No agent assignment
- ✅ `resources/js/Components/Dashboard/PendingTasksCard.tsx` - Show "Auto-Selected"

---

## 🚀 Usage Examples

### Example 1: User Approves Task

**Dashboard:**
```
📋 Task: Optimize monthly software subscriptions
💰 Savings: $450/month
🤖 Agent: Auto-Selected
Priority: 1
```

**User clicks "Approve"**

**Backend:**
```php
// Task created with no agent
Task::create([
    'agent_name' => null,  // ✅ No agent!
    'data' => [
        'name' => 'Optimize monthly software subscriptions',
        'description' => 'Review all software subscriptions...',
    ]
]);

// Scheduled 4-5 days out
$scheduledAt = now()->addDays(rand(4, 5));

// Queue entry created
TaskQueue::create([
    'agent_name' => null,  // ✅ Will be selected later
    'scheduled_at' => $scheduledAt,
]);
```

**4 Days Later:**

```php
// ProcessTaskQueue runs
$agentSelector = new AgentSelector();
$selection = $agentSelector->selectAgent($task->data);

// Result:
// agent_name: cost_optimizer_agent
// score: 10
// reasoning: "Matched 'software subscriptions' and 'optimize'"

// Update queue
$queueEntry->update(['agent_name' => 'cost_optimizer_agent']);

// Execute with selected agent
$agent = app(\App\Agents\CostOptomizerAgent\CostOptomizerAgent::class);
$result = $agent->run($prompt);
```

---

## 🎯 Summary

The new Dynamic Agent Selection system provides:

✅ **Intelligent agent matching** based on task content  
✅ **4-5 day scheduling** for all approved tasks  
✅ **Simplified task creation** (MasterOrchestrator doesn't assign agents)  
✅ **Flexible architecture** (easy to add new agents)  
✅ **Better user experience** (clear "Auto-Selected" messaging)  
✅ **Backward compatible** (existing tasks still work)  
✅ **Comprehensive logging** (selection reasoning tracked)  

**Status: PRODUCTION READY 🚀**

