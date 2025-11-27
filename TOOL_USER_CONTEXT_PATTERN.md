# Tool User Context Pattern

## The Problem

When AI agents use tools that need to query user-specific data (like `FinancialRecordsTool` or `KnowledgeBaseTool`), we need to ensure they query the **correct user's data** without exposing user IDs to the AI.

## ❌ Wrong Approach

**Don't do this:**
```php
// DON'T: Including user_id in tool parameters
protected array $properties = [
    'user_id' => [
        'type' => 'integer',
        'description' => 'User ID to query records for (required)',
    ],
    // ...
];

// AI would call: {"operation": "get_all", "user_id": 6}
```

**Why this is wrong:**
- 🚫 AI shouldn't have access to user IDs (security risk)
- 🚫 AI could accidentally query wrong user's data
- 🚫 AI could be prompted to leak other users' data
- 🚫 Adds unnecessary complexity to tool calls

## ✅ Correct Approach

### 1. Tool Design: Separate User Context from Parameters

```php
// app/Tools/FinancialRecordsTool.php
class FinancialRecordsTool extends Tool
{
    protected ?int $userId = null;

    /**
     * Set the user ID for this tool instance
     */
    public function setUserId(int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    protected array $properties = [
        'operation' => [
            'type' => 'string',
            'enum' => ['get_all', 'spending_summary', 'category_breakdown'],
        ],
        // NO user_id property here!
    ];

    public function execute(array $input): mixed
    {
        // Use the userId set on this tool instance
        if (!$this->userId) {
            return json_encode(['error' => 'User context not available']);
        }
        $userId = $this->userId;
        
        // ... use $userId to query data
    }
}
```

### 2. Job/Controller: Inject User Context Before Agent Use

```php
// app/Jobs/FirstTimeCostAnalysisJob.php
class FirstTimeCostAnalysisJob implements ShouldQueue
{
    public function __construct(public int $userId) {}

    private function runCostDecomposition(string $sessionId, ...): string
    {
        $agent = CostDecompositionAgent::for($sessionId);
        
        // Inject user context BEFORE the agent uses tools
        $agent = $this->injectUserContext($agent);
        
        $response = $agent->respond($prompt);
        return $response;
    }

    /**
     * Inject user context into agent's tools
     */
    private function injectUserContext($agent)
    {
        $reflection = new \ReflectionClass($agent);
        $toolsProperty = $reflection->getProperty('tools');
        $toolsProperty->setAccessible(true);
        $tools = $toolsProperty->getValue($agent);
        
        $updatedTools = [];
        foreach ($tools as $toolClass) {
            if ($toolClass === \App\Tools\FinancialRecordsTool::class) {
                $toolInstance = new \App\Tools\FinancialRecordsTool();
                $toolInstance->setUserId($this->userId);
                $updatedTools[] = $toolInstance;
            } elseif ($toolClass === \App\Tools\KnowledgeBaseTool::class) {
                $toolInstance = new \App\Tools\KnowledgeBaseTool($this->userId);
                $updatedTools[] = $toolInstance;
            } else {
                $updatedTools[] = $toolClass;
            }
        }
        
        $toolsProperty->setValue($agent, $updatedTools);
        return $agent;
    }
}
```

### 3. Agent Prompts: Tell AI NOT to Provide user_id

```php
$prompt .= "**IMPORTANT:** Use the following tools:\n";
$prompt .= "1. Call `query_financial_records` with operation='spending_summary'\n";
$prompt .= "2. Call `query_financial_records` with operation='category_breakdown'\n";
$prompt .= "\n**NOTE:** The tools automatically access the current user's data. You do NOT need to provide user_id.\n";
```

## How It Works

### Step-by-Step Flow

1. **Job/Controller receives user_id**
   ```php
   new FirstTimeCostAnalysisJob($userId = 6)
   ```

2. **Job initializes agent**
   ```php
   $agent = CostDecompositionAgent::for('session_123');
   ```

3. **Job injects user context into tools**
   ```php
   $agent = $this->injectUserContext($agent); // Sets userId=6 on tools
   ```

4. **Agent responds to prompt**
   ```php
   $agent->respond("Analyze costs...");
   ```

5. **AI calls tool (without user_id)**
   ```json
   {
     "operation": "category_breakdown",
     "limit": 100
   }
   ```

6. **Tool uses injected user_id**
   ```php
   $userId = $this->userId; // Already set to 6
   FinancialRecord::where('user_id', $userId)->...
   ```

## Benefits

### 🔒 Security
- AI never sees or handles user IDs
- Prevents AI from accessing other users' data
- Reduces risk of data leakage through prompt injection

### 🎯 Simplicity
- Tool calls are cleaner and simpler
- AI has fewer parameters to worry about
- Less chance of AI making mistakes

### 🛡️ Safety
- System controls which user's data is accessed
- AI can't be tricked into querying wrong user
- Clear separation of concerns

### 📝 Clear Separation
- **System Context** (user_id, tenant_id, etc.) - Set by system
- **Tool Parameters** (operation, filters, limits) - Set by AI
- Each stays in its proper domain

## When to Use This Pattern

Use this pattern when your tool needs to access **user-specific** or **tenant-specific** data:

✅ **Use for:**
- Database queries filtered by user_id
- File storage scoped to users
- API calls with user authentication
- Any resource tied to a specific user/tenant

❌ **Don't use for:**
- Public data sources (weather, news, etc.)
- Stateless calculations
- External APIs not tied to users
- Tools that work the same for all users

## Other Approaches

### Alternative 1: Agent State (Vizra ADK)

If using Vizra ADK, you can use `AgentContext`:

```php
public function execute(array $arguments, AgentContext $context, AgentMemory $memory): string
{
    $userId = $context->getState('user_id');
    // ...
}
```

### Alternative 2: Service Container Binding

Bind user context in service provider:

```php
app()->bind(FinancialRecordsTool::class, function ($app) {
    $tool = new FinancialRecordsTool();
    $tool->setUserId(auth()->id());
    return $tool;
});
```

## Summary

**The Golden Rule:**
> System context (like user_id) should be injected by the system, not provided by the AI.

This keeps your AI agents secure, simple, and focused on their actual task rather than managing authentication and authorization concerns.

---

**Related Files:**
- `app/Tools/FinancialRecordsTool.php` - Example tool with setUserId()
- `app/Tools/KnowledgeBaseTool.php` - Example tool with constructor userId
- `app/Jobs/FirstTimeCostAnalysisJob.php` - Example usage with injectUserContext()
