# 🎯 COMPLETE SYSTEM FLOW - Costrym

## 📋 Table of Contents
1. [User Onboarding Flow](#user-onboarding-flow)
2. [Data Ingestion Pipeline](#data-ingestion-pipeline)
3. [Task Generation & Approval](#task-generation--approval)
4. [Daily Task Execution](#daily-task-execution)
5. [Agent Pipeline Orchestration](#agent-pipeline-orchestration)
6. [MD Documentation System](#md-documentation-system)

---

## 🚀 User Onboarding Flow

### **Step 1: User Completes Onboarding**

**Location:** `app/Http/Controllers/OnboardingController.php`

```
User fills out form:
├─ Company name
├─ Industry
├─ Financial goals
├─ Pain points
├─ Current challenges
└─ Integrations to connect
```

### **Step 2: Data Saved to KnowledgeBase**

```php
KnowledgeBase::create([
    'user_id' => $user->id,
    'context' => [
        'company_name' => 'TechStartup Inc',
        'industry' => 'SaaS',
        'goals' => ['Reduce OPEX by 15%', 'Achieve profitability'],
        'pain_points' => ['High cloud costs', 'Unused SaaS'],
    ]
]);
```

### **Step 3: DataIngestionJob Dispatched**

```php
DataIngestionJob::dispatch($user->id, isInitialSync: true);
```

---

## 📊 Data Ingestion Pipeline

### **Stage 1: Batch Ingestion Jobs**

**Job:** `DataIngestionJob.php`

```
Creates Batch with Multiple Jobs:
├─ XeroIngestionJob
├─ ZohoBooksIngestionJob
├─ QuickBooksIngestionJob
├─ SevdeskIngestionJob
└─ ExpensifyIngestionJob

Each job:
├─ Uses IntegrationIngestor agent
├─ Calls Pipedream actions
├─ Fetches financial data
└─ Saves to financial_records table
```

**Output:**
```
financial_records table populated:
├─ Transactions
├─ Invoices
├─ Expenses
├─ Bank summaries
└─ All tagged with source integration
```

### **Stage 2: Financial Categorization**

**Job:** `FinancialCategorizerJob.php`  
**Triggered:** After all ingestion jobs complete (Batch callback)

```
Agent: CategorizerAgent
├─ Fetches uncategorized records (batch: 20)
├─ Uses ListFinancialCategoriesTool
├─ Assigns category_id to each record
├─ Self-chains if more uncategorized records
└─ Processing: 1-20 rows/second
```

**Categories Used:**
```
1. Marketing
2. Sales
3. Cloud & Infrastructure
4. Software & Subscriptions (SaaS)
5. Payroll & Compensation
6. Office & Facilities
7. Professional Services
8. Travel & Entertainment
... (18 total)
```

### **Stage 3: MasterOrchestrator Task Generation**

**Job:** `MasterOrchestratorJob.php`  
**Triggered:** After categorization completes (with 5-second delay)

```
Agent: MasterOrchestrator
├─ Scenario: 'first_onboarding'
├─ Tools Available:
│   ├─ KnowledgeBaseTool → User context
│   ├─ QueryFinancialRecordsTool → Analyze data
│   └─ ListFinancialCategoriesTool → Category info
│
├─ Analyzes:
│   ├─ User's financial goals
│   ├─ Pain points
│   ├─ Categorized transaction data
│   └─ Spending patterns
│
└─ Generates: 5-7 cost-saving tasks
```

**Task Generation Output:**
```json
[
  {
    "name": "Audit Unused SaaS Subscriptions",
    "description": "Review all software subscriptions, identify unused licenses",
    "task_type": "one_time",
    "priority": 1,
    "estimated_savings": "$1,200/month",
    "input": {
      "focus_areas": ["Slack", "Adobe", "Zoom"],
      "analysis_depth": "detailed"
    },
    "metadata": {
      "addresses_pain_point": "Too many unused SaaS subscriptions",
      "contributes_to_goal": "Reduce OPEX by 15%"
    }
  },
  ...
]
```

**Tasks Saved:**
```
tasks table:
├─ user_id: 1
├─ agent_name: null (dynamic selection)
├─ status: pending
├─ priority: 1-10
└─ data: {JSON with full task details}
```

**MD Document Created:**
```
automations table:
├─ type: 'task_generation'
├─ name: "Task Generation - first_onboarding"
├─ markdown_content: Full report with tasks
└─ metadata: {task_count: 5, estimated_savings: $2500}
```

---

## ✅ Task Generation & Approval

### **Step 1: Dashboard Display**

**Component:** `PendingTasksCard.tsx`

```
User sees in Dashboard:
┌─────────────────────────────────────┐
│ 📋 Pending Tasks (5)                │
├─────────────────────────────────────┤
│ 🔴 Priority 1: Audit SaaS           │
│    Savings: $1,200/month            │
│    Agent: Auto-Selected 🤖          │
│    [Approve] [Reject] [Details]     │
├─────────────────────────────────────┤
│ 🟠 Priority 2: Optimize Cloud       │
│    Savings: $800/month              │
│    [Approve] [Reject]               │
└─────────────────────────────────────┘
```

### **Step 2: User Approves Tasks**

**Controller:** `TaskApprovalController.php`

```
When user clicks "Approve":
├─ Task status: pending → approved
├─ Calculate scheduled_at:
│   ├─ Priority 1-3: Today or tomorrow
│   ├─ Priority 4-6: 0-2 days out
│   └─ Priority 7-10: 0-4 days out
│   (Random time: 8 AM - 6 PM)
│
├─ Create TaskQueue entry:
│   ├─ user_id: 1
│   ├─ task_id: 5
│   ├─ agent_name: null (dynamic)
│   ├─ status: queued
│   ├─ scheduled_at: 2025-11-22 14:30:00
│   └─ data: {task details}
│
└─ Dispatch ProcessTaskQueue job:
    └─ With delay = scheduled_at
```

**Result:**
```
5 tasks approved:
├─ Task 1: Scheduled for Nov 21, 10:00 AM
├─ Task 2: Scheduled for Nov 21, 3:00 PM
├─ Task 3: Scheduled for Nov 22, 9:00 AM
├─ Task 4: Scheduled for Nov 23, 2:00 PM
└─ Task 5: Scheduled for Nov 24, 11:00 AM
```

---

## ⏰ Daily Task Execution

### **Step 1: Daily Task Checker**

**Command:** `CheckDailyTasks.php`  
**Schedule:** Runs daily at 7:00 AM

```bash
php artisan tasks:check-daily --dispatch
```

**What it does:**
```
1. Query task_queue table:
   WHERE scheduled_at = TODAY
   AND status = 'queued'
   
2. Find: 2 tasks for today
   ├─ Task ID 1 @ 10:00 AM
   └─ Task ID 2 @ 3:00 PM
   
3. Dispatch ProcessTaskQueue for each:
   ├─ ProcessTaskQueue::dispatch(userId: 1, taskId: 1, queueId: 123)
   └─ With delay until scheduled time
```

### **Step 2: ProcessTaskQueue Execution**

**Job:** `ProcessTaskQueue.php`  
**Queue:** `task_execution`

```
Job handles task execution:
├─ Loads TaskQueue entry
├─ Loads Task details
├─ Updates status: queued → processing
│
├─ Execution Logic:
│   └─ Uses MasterOrchestratorExecutor agent
│       ├─ Analyzes task requirements
│       ├─ Selects best approach
│       ├─ Can delegate to specialized agents
│       └─ Can trigger Agent Pipelines
│
└─ Returns: Markdown report
```

---

## 🤖 Agent Pipeline Orchestration

### **When Pipeline is Triggered**

```
MasterOrchestratorExecutor decides:
"This task requires deep analysis"
↓
AgentPipelineJob::dispatch(
    userId: 1,
    pipelineName: 'deep_cost_analysis',
    initialInput: {task data},
)
```

### **Pipeline Execution Flow**

**Job:** `AgentPipelineJob.php`  
**Queue:** `agent_pipeline`

```
Pipeline: deep_cost_analysis (5 stages)

Stage 1: baseline_agent
├─ Input: Company financials + profile
├─ Tool: RollingAggregateTool
├─ Output: baseline_data (spending patterns)
└─ Saved to: AgentContext + Automation MD

Stage 2: cost_decomposition_agent
├─ Input: baseline_data + full context
├─ Tool: GetTotalCostByCategory
├─ Output: decomposition_data (cost breakdown)
└─ Saved to: AgentContext + Automation MD

Stage 3: benchmarking_agent
├─ Input: All previous data
├─ Tool: FireCrawler (web research)
├─ Output: benchmark_data (industry standards)
└─ Saved to: AgentContext + Automation MD

Stage 4: cer_agent
├─ Input: All previous data
├─ Tool: CERCalculator
├─ Output: cer_data (efficiency ratios)
└─ Saved to: AgentContext + Automation MD

Stage 5: cost_value_aligner_agent
├─ Sub-agents: ValueMapper + SmartReducer
├─ Input: All previous data
├─ Output: alignment_data (smart cost cuts)
└─ Saved to: AgentContext + Automation MD

Final Report:
└─ Combines all 5 stages into comprehensive MD
```

### **Context Passing Between Stages**

```markdown
# Pipeline Context

## Stage 1: baseline_agent
**Description:** Establish spending baselines
**Result:**
```json
{
  "baseline_spending": {
    "Marketing": {"monthly_avg": 75000, "trend": "growing"},
    "Cloud": {"monthly_avg": 35000, "trend": "stable"}
  }
}
```

## Stage 2: cost_decomposition_agent
**Description:** Break down cost components
**Result:**
```json
{
  "Marketing": {
    "Google Ads": 35000,
    "Content Creation": 20000,
    "Events": 20000
  }
}
```

... (and so on)
```

---

## 📄 MD Documentation System

### **Every Execution Creates MD Files**

#### **1. Task Generation MD**
```
Type: task_generation
Created: After MasterOrchestratorJob
Content: List of generated tasks with details
```

#### **2. Pipeline Stage MDs** (5 files)
```
Type: pipeline_stage
Created: After each agent in pipeline
Content: Individual agent output
```

#### **3. Pipeline Complete MD**
```
Type: pipeline_complete
Created: After full pipeline finishes
Content: All stages combined + summary
```

#### **4. Execution Report MD**
```
Type: execution_report
Created: After task completes
Content: Full task execution results
```

### **MD Storage**

```
automations table:
├─ id
├─ user_id
├─ task_id
├─ task_queue_id
├─ type (task_generation, pipeline_stage, etc.)
├─ name
├─ markdown_content ← The MD file!
├─ file_path (optional disk storage)
├─ metadata (JSON)
└─ status
```

---

## 🔄 Recurring Tasks

### **For Looping Tasks**

```
Task with task_type = 'looping':
└─ After completion, ProcessTaskQueue:
    ├─ Checks: Is this a looping task?
    ├─ Creates new TaskQueue entry:
    │   ├─ scheduled_at = +1 week/month/day
    │   ├─ agent_name = null (re-selected)
    │   └─ status = queued
    └─ Cycle repeats from daily checker
```

**Example:**
```
Monthly SaaS Audit Task:
├─ Executes: Nov 20
├─ Next scheduled: Dec 20
├─ Status: queued
└─ Will run automatically next month
```

---

## 📊 Complete Flow Diagram

```
┌─────────────────────────────────────────────────────────────┐
│ 1. USER ONBOARDS                                            │
│    ├─ Fills out form                                        │
│    ├─ Connects integrations                                 │
│    └─ Saves to KnowledgeBase                                │
└────────────────┬────────────────────────────────────────────┘
                 ↓
┌─────────────────────────────────────────────────────────────┐
│ 2. DATA INGESTION PIPELINE                                  │
│    ├─ XeroIngestionJob                                      │
│    ├─ QuickBooksIngestionJob                                │
│    ├─ ... (Batch of 5 jobs)                                 │
│    └─ Data saved to financial_records                       │
└────────────────┬────────────────────────────────────────────┘
                 ↓
┌─────────────────────────────────────────────────────────────┐
│ 3. FINANCIAL CATEGORIZATION                                 │
│    ├─ FinancialCategorizerJob                               │
│    ├─ CategorizerAgent assigns categories                   │
│    └─ Processes 1-20 rows/sec                               │
└────────────────┬────────────────────────────────────────────┘
                 ↓
┌─────────────────────────────────────────────────────────────┐
│ 4. TASK GENERATION                                          │
│    ├─ MasterOrchestratorJob                                 │
│    ├─ MasterOrchestrator agent analyzes data                │
│    ├─ Generates 5-7 cost-saving tasks                       │
│    ├─ Saves to tasks table (status: pending)                │
│    └─ Creates Task Generation MD                            │
└────────────────┬────────────────────────────────────────────┘
                 ↓
┌─────────────────────────────────────────────────────────────┐
│ 5. USER APPROVAL (Dashboard)                                │
│    ├─ PendingTasksCard shows tasks                          │
│    ├─ User clicks "Approve"                                 │
│    ├─ TaskApprovalController processes                      │
│    ├─ Creates TaskQueue entries                             │
│    └─ Distributes across 4-5 days                           │
└────────────────┬────────────────────────────────────────────┘
                 ↓
┌─────────────────────────────────────────────────────────────┐
│ 6. DAILY TASK CHECKER (7 AM)                                │
│    ├─ CheckDailyTasks command runs                          │
│    ├─ Finds tasks scheduled for today                       │
│    └─ Dispatches ProcessTaskQueue for each                  │
└────────────────┬────────────────────────────────────────────┘
                 ↓
┌─────────────────────────────────────────────────────────────┐
│ 7. TASK EXECUTION                                           │
│    ├─ ProcessTaskQueue job runs                             │
│    ├─ MasterOrchestratorExecutor coordinates                │
│    ├─ Can delegate to single agent OR                       │
│    └─ Can trigger full Agent Pipeline                       │
└────────────────┬────────────────────────────────────────────┘
                 ↓
┌─────────────────────────────────────────────────────────────┐
│ 8. AGENT PIPELINE (If Complex Task)                         │
│    ├─ AgentPipelineJob dispatched                           │
│    ├─ Stage 1: baseline_agent                               │
│    ├─ Stage 2: cost_decomposition_agent                     │
│    ├─ Stage 3: benchmarking_agent                           │
│    ├─ Stage 4: cer_agent                                    │
│    ├─ Stage 5: cost_value_aligner_agent                     │
│    ├─ Context passed between all stages                     │
│    └─ Each stage creates MD                                 │
└────────────────┬────────────────────────────────────────────┘
                 ↓
┌─────────────────────────────────────────────────────────────┐
│ 9. RESULTS & DOCUMENTATION                                  │
│    ├─ TaskQueue status: processing → completed              │
│    ├─ Task status: running → completed                      │
│    ├─ Execution Report MD created                           │
│    ├─ Pipeline Complete MD created (if pipeline)            │
│    └─ All stored in automations table                       │
└────────────────┬────────────────────────────────────────────┘
                 ↓
┌─────────────────────────────────────────────────────────────┐
│ 10. RECURRING TASK SCHEDULING (If looping)                  │
│     ├─ New TaskQueue entry created                          │
│     ├─ scheduled_at = +1 week/month                         │
│     └─ Cycle repeats from Step 6                            │
└─────────────────────────────────────────────────────────────┘
```

---

## 🎯 Example: Complete User Journey

### **Day 1 - Monday (Onboarding)**
```
9:00 AM  → User completes onboarding
9:01 AM  → DataIngestionJob starts (5 parallel jobs)
9:05 AM  → All ingestion complete, 1,250 records fetched
9:05 AM  → FinancialCategorizerJob starts
9:08 AM  → All 1,250 records categorized
9:08 AM  → MasterOrchestratorJob starts
9:10 AM  → 5 tasks generated and saved (status: pending)
         → Task Generation MD created

Tasks Generated:
1. [Priority 1] Audit SaaS Subscriptions - $1,200/month savings
2. [Priority 2] Optimize Cloud Infrastructure - $800/month savings
3. [Priority 3] Review Marketing ROI - $600/month savings
4. [Priority 5] Negotiate Vendor Contracts - $400/month savings
5. [Priority 7] Automate Invoice Processing - $300/month savings
```

### **Day 1 - Monday (Afternoon)**
```
2:00 PM  → User logs in, sees 5 pending tasks
2:15 PM  → User approves all 5 tasks

Task Distribution:
- Task 1: Scheduled for Tuesday 10:00 AM
- Task 2: Scheduled for Tuesday 3:00 PM
- Task 3: Scheduled for Wednesday 11:00 AM
- Task 4: Scheduled for Thursday 2:00 PM
- Task 5: Scheduled for Friday 9:00 AM
```

### **Day 2 - Tuesday**
```
7:00 AM  → CheckDailyTasks command runs
         → Finds 2 tasks for today (Task 1 & 2)
         → Dispatches ProcessTaskQueue for both

10:00 AM → Task 1 execution starts
         → MasterOrchestratorExecutor analyzes: "Audit SaaS"
         → Decides: This needs deep analysis
         → Triggers: AgentPipelineJob (deep_cost_analysis)
         
10:01 AM → Pipeline Stage 1: baseline_agent
         → Output: Current SaaS spending patterns
         → MD created

10:03 AM → Pipeline Stage 2: cost_decomposition_agent
         → Output: Breakdown by vendor
         → MD created

10:05 AM → Pipeline Stage 3: benchmarking_agent
         → Output: Industry benchmarks
         → MD created

10:07 AM → Pipeline Stage 4: cer_agent
         → Output: Efficiency ratios
         → MD created

10:10 AM → Pipeline Stage 5: cost_value_aligner_agent
         → Output: Smart reduction recommendations
         → MD created

10:12 AM → Pipeline Complete MD generated
         → Task 1 marked complete
         → Execution Report MD created
         
Result: Found $1,350/month in SaaS savings (better than estimate!)

3:00 PM  → Task 2 execution starts (Cloud Optimization)
         → Similar pipeline flow
         → Completes at 3:15 PM
```

### **Day 3 - Wednesday**
```
7:00 AM  → CheckDailyTasks runs
         → Finds Task 3 for today

11:00 AM → Task 3 executes (Marketing ROI Review)
         → Completes at 11:20 AM
```

### **Day 4 - Thursday**
```
Task 4 executes...
```

### **Day 5 - Friday**
```
Task 5 executes...

End Result:
- 5 tasks completed
- 10+ MD documents generated
- Total savings identified: $3,800/month
- All documented and stored
- User can review all reports in dashboard
```

---

## 📈 Data Flow Summary

```
KnowledgeBase
    ↓
financial_records (via ingestion)
    ↓
financial_records (with category_id)
    ↓
tasks (generated by MasterOrchestrator)
    ↓
task_queue (after user approval)
    ↓
automations (MD reports from execution)
```

---

## 🔧 Key Configuration Files

1. **`config/agents.php`** - All agents & pipelines
2. **`app/Console/Kernel.php`** - Daily scheduler
3. **`routes/web.php`** - Task approval routes
4. **Database Tables:**
   - `users`
   - `knowledge_base`
   - `financial_records`
   - `financial_categories`
   - `tasks`
   - `task_queue`
   - `automations`

---

## 🚀 System Capabilities

✅ **Multi-Integration Data Ingestion**  
✅ **AI-Powered Transaction Categorization**  
✅ **Intelligent Task Generation**  
✅ **User Approval Workflow**  
✅ **Smart Task Distribution**  
✅ **Daily Automated Execution**  
✅ **Multi-Agent Orchestration**  
✅ **Sequential Pipeline Processing**  
✅ **Context Passing Between Agents**  
✅ **Markdown Documentation**  
✅ **Recurring Task Support**  
✅ **Error Resilience**  

---

**PRODUCTION READY!** 🎉

