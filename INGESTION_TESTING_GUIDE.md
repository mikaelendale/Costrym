# 🧪 Data Ingestion Live Updates - Testing Guide

## Quick Test

### 1. Start Queue Worker

```bash
php artisan queue:work --queue=data_ingestion,categorization,default
```

### 2. Test via Tinker

```bash
php artisan tinker
```

Then run these commands one by one to simulate the flow:

```php
// Get your user ID
$userId = auth()->id(); // or use specific ID like: $userId = 1;

// 1. Started
broadcast(new \App\Events\DataIngestionStatusUpdated(
    $userId,
    'started',
    ['message' => 'Starting data ingestion...']
));

// Wait 3 seconds, then:

// 2. Categorizing
broadcast(new \App\Events\DataIngestionStatusUpdated(
    $userId,
    'categorizing',
    ['message' => 'Data ingested successfully. Categorizing records...']
));

// Wait 3 seconds, then:

// 3. Completed
broadcast(new \App\Events\DataIngestionStatusUpdated(
    $userId,
    'completed',
    [
        'message' => 'Data ingestion and categorization complete!',
        'total_records' => 150,
        'categorized_records' => 145,
    ]
));
```

---

## What You'll See

### Laravel Logs (storage/logs/laravel.log)

```
🚀 Broadcasting ingestion STARTED
✅ Broadcast sent: ingestion STARTED

📊 Broadcasting ingestion CATEGORIZING
✅ Broadcast sent: ingestion CATEGORIZING

✅ Broadcasting ingestion COMPLETED
   - total_records: 150
   - categorized_records: 145
✅ Broadcast sent: ingestion COMPLETED
```

### Browser Console

```
📡 [INGESTION] Subscribing to channel: private-user.1
✅ [INGESTION] Successfully connected to channel: private-user.1

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📨 [INGESTION] STATUS UPDATE RECEIVED
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Status: started
Message: Starting data ingestion...
Data: {message: 'Starting data ingestion...'}
Timestamp: 2025-11-29 12:34:56
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

✅ [INGESTION] UI state updated to: started
```

### Dashboard UI

**Card will update in real-time:**

1. **Idle** (default state)
   - Shows: "No active ingestion"
   
2. **Started** (when broadcast sent)
   - 🔵 Spinning loader
   - "Starting data ingestion..."
   - Blue border glow
   - Animated dots

3. **Categorizing**
   - 🟣 Purple spinning loader
   - "Data ingested successfully. Categorizing records..."

4. **Completed**
   - 🟢 Green checkmark
   - "Data ingestion and categorization complete!"
   - Shows: Total Records: 150
   - Shows: Categorized: 145 (green)

---

## Real Flow Test

1. **Complete onboarding** with JSON file or connected integration
2. **Watch Laravel logs** in real-time:
   ```bash
   Get-Content storage/logs/laravel.log -Wait -Tail 50
   ```
3. **Open browser console** (F12)
4. **Watch dashboard** - card updates automatically

---

## Expected Timeline

```
0s   → 🚀 Started broadcast
      Dashboard: "Starting data ingestion..."
      
5s   → Data being fetched from integrations
      
30s  → 📊 Categorizing broadcast
      Dashboard: "Categorizing records..."
      
60s  → ✅ Completed broadcast
      Dashboard: "Complete! 150 records, 145 categorized"
```

---

## Logging Output

### Backend (Laravel Log)
- 🚀 = Starting broadcast
- 📊 = Processing broadcast  
- ✅ = Success confirmation
- ❌ = Error broadcast

### Frontend (Console)
- 📡 = Connecting to channel
- 📨 = Received event
- ✅ = State updated
- 👋 = Cleanup

---

## Quick Debug

If card doesn't update:

1. **Check Echo initialization**
   ```javascript
   console.log(window.Echo); // Should show Echo object
   ```

2. **Check channel connection**
   - Look for: `✅ [INGESTION] Successfully connected to channel`

3. **Check env variables**
   ```bash
   BROADCAST_CONNECTION=ably
   VITE_ABLY_PUBLIC_KEY=your-key-here
   ```

4. **Check queue worker is running**
   ```bash
   php artisan queue:work --queue=data_ingestion,categorization,default
   ```

---

That's it! Simple testing with clear logs everywhere! 🎉
