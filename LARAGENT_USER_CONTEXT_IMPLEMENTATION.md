# LarAgent User Context Implementation

## ✅ Clean Solution Using LarAgent's Built-in Features

Following the LarAgent documentation, we're using the **Message metadata pattern** to pass user context without exposing it to the AI.

## How It Works

### 1. **Message Metadata Pattern** (LarAgent Built-in)

Instead of complex reflection or tool injection, we use LarAgent's `UserMessage` constructor with metadata:

```php
use LarAgent\Messages\UserMessage;

// Create message with user_id in metadata
$userMessage = new UserMessage($prompt, ['user_id' => $this->userId]);

// Pass to agent
$response = CostDecompositionAgent::for($sessionId)
    ->message($userMessage)
    ->respond();
```

### 2. **Service Container Binding**

Bind the user_id to Laravel's service container so tools can access it:

```php
// In FirstTimeCostAnalysisJob before calling agent
app()->instance('laragent.user_id', $this->userId);
```

### 3. **Tool Accesses user_id from Container**

```php
// In FinancialRecordsTool::execute()
public function execute(array $input): mixed
{
    // Get user_id from container
    $userId = app('laragent.user_id');
    
    if (!$userId) {
        return json_encode(['error' => 'User context not available']);
    }
    
    // Use $userId to query data
    FinancialRecord::where('user_id', $userId)->...
}
```

## Complete Flow

```
[Job] userId=6
  ↓
[Bind to container] app()->instance('laragent.user_id', 6)
  ↓
[Create message] new UserMessage($prompt, ['user_id' => 6])
  ↓
[Agent] ->message($userMessage)->respond()
  ↓
[AI calls tool] {"operation": "category_breakdown"}
  ↓
[Tool accesses] app('laragent.user_id') → 6
  ↓
[Query] FinancialRecord::where('user_id', 6)->...
```

## Benefits

### 🎯 Simple & Clean
- No reflection needed
- No complex tool injection
- Uses LarAgent's built-in features

### 🔒 Secure
- AI never sees user_id
- AI can't query wrong user's data
- System controls access

### 📝 LarAgent Standard
- Follows official documentation pattern
- Uses `Message::user()` with metadata
- Chainable agent methods

## Implementation Details

### Modified Files

1. **`app/Jobs/FirstTimeCostAnalysisJob.php`**
   ```php
   // Added import
   use LarAgent\Messages\UserMessage;
   
   // In each agent call method:
   app()->instance('laragent.user_id', $this->userId);
   $userMessage = new UserMessage($prompt, ['user_id' => $this->userId]);
   $response = Agent::for($sessionId)->message($userMessage)->respond();
   ```

2. **`app/Tools/FinancialRecordsTool.php`**
   ```php
   // In execute method:
   $userId = app('laragent.user_id') ?? null;
   if (!$userId) {
       return json_encode(['error' => 'User context not available']);
   }
   ```

3. **`app/Tools/KnowledgeBaseTool.php`**
   - Already has constructor injection: `new KnowledgeBaseTool($userId)`
   - Works with Vizra ADK's `AgentContext`

## Agent Prompt Updates

Prompts explicitly tell the AI NOT to provide user_id:

```php
$prompt .= "**NOTE:** The tools automatically access the current user's data. ";
$prompt .= "You do NOT need to provide user_id.\n";
```

## Why This Approach?

### ✅ According to LarAgent Documentation:
> "Instead of passing a string as a message, you can build your own UserMessage instance. 
> It allows you to add metadata to the message, such as the user ID or request ID."

**Usage:**
```php
$userMessage = new UserMessage($prompt, ['user_id' => $userId]);
```

### ✅ Service Container Pattern:
Laravel's service container is perfect for sharing context across the request lifecycle without passing it explicitly everywhere.

### ✅ No Hacks:
- No reflection magic
- No manual tool instantiation
- No property injection

## Testing

```bash
# Test with actual user data
php artisan tinker
>>> dispatch(new \App\Jobs\FirstTimeCostAnalysisJob(6));

# Check logs
tail -f storage/logs/laravel.log | grep "FinancialRecordsTool"
```

## Example Tool Call

**AI sends:**
```json
{
  "operation": "category_breakdown",
  "limit": 100
}
```

**Tool receives:**
- `$input` = `{"operation": "category_breakdown", "limit": 100}`
- `$userId` = `6` (from container)

**Tool queries:**
```sql
SELECT * FROM financial_records 
WHERE user_id = 6 
GROUP BY category_id
LIMIT 100
```

## Summary

**The Pattern:**
1. Job binds `user_id` to container: `app()->instance('laragent.user_id', $userId)`
2. Job creates message with metadata: `new UserMessage($prompt, ['user_id' => $userId])`  
3. Agent receives message: `->message($userMessage)->respond()`
4. Tool accesses from container: `app('laragent.user_id')`

**Result:** Clean, secure, simple, and follows LarAgent best practices! 🎉

---

**Related Documentation:**
- LarAgent Agents: https://docs.laragent.ai/core-concepts/agents
- Message Metadata: See "Ready made user message" section
- Service Container: Laravel's DI container
