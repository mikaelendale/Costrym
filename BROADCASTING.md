# Laravel Broadcasting with Ably (Real-time Events)

## Overview
Real-time broadcasting setup using Laravel Echo + Ably (via Pusher protocol). Events are broadcast server-side using Laravel events, received client-side via Echo.

---

## Configuration

### 1. Environment Variables
```env
BROADCAST_CONNECTION=ably
ABLY_KEY=your-ably-key-here
VITE_ABLY_PUBLIC_KEY=your-ably-public-key-here
```

### 2. Broadcasting Routes
**File:** `routes/web.php`
```php
// Broadcasting authentication endpoint
Broadcast::routes(['middleware' => ['auth:web']]);
```

### 3. Channel Authorization
**File:** `routes/channels.php`
```php
// Private channel example
Broadcast::channel('private-user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Public channel (no auth needed)
// Just use Channel class instead of PrivateChannel in your event
```

### 4. CSRF Token (Required)
**File:** `resources/views/app.blade.php`
```html
<meta name="csrf-token" content="{{ csrf_token() }}">
```

---

## Frontend Setup

### Echo Initialization
**File:** `resources/js/app.tsx`
```typescript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

const initEcho = () => {
    const ablyKey = import.meta.env.VITE_ABLY_PUBLIC_KEY;
    
    if (!ablyKey) {
        console.error('❌ VITE_ABLY_PUBLIC_KEY not set');
        return;
    }
    
    try {
        const pusherClient = new Pusher(ablyKey, {
            cluster: 'mt1',
            wsHost: 'realtime-pusher.ably.io',
            wsPort: 443,
            wssPort: 443,
            disableStats: true,
            authEndpoint: '/broadcasting/auth',
        });
        
        (window as any).Echo = new Echo({
            broadcaster: 'pusher',
            client: pusherClient,
        });
        
        console.log('✅ Echo initialized with Ably');
    } catch (error) {
        console.error('❌ Failed to initialize Echo:', error);
    }
};

initEcho();
```

---

## Usage

### 1. Create an Event
```bash
php artisan make:event YourEventName
```

### 2. Implement the Event
**File:** `app/Events/YourEventName.php`
```php
<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class YourEventName implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    // For PUBLIC channel (no auth)
    public function broadcastOn(): array
    {
        return [
            new Channel('your-channel-name'),
        ];
    }

    // For PRIVATE channel (requires auth)
    // public function broadcastOn(): array
    // {
    //     return [
    //         new PrivateChannel('private-user.' . $this->userId),
    //     ];
    // }

    public function broadcastWith(): array
    {
        return [
            'data' => $this->data,
            'timestamp' => now()->toDateTimeString(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'your.event.name';
    }
}
```

### 3. Trigger the Event (Backend)
```php
// In controller, job, or anywhere
broadcast(new YourEventName($someData));
```

### 4. Listen to Events (Frontend)
```typescript
import { useEffect, useState } from 'react';

export function YourComponent() {
    const [data, setData] = useState(null);

    useEffect(() => {
        const echo = (window as any).Echo;
        
        if (!echo) {
            console.error('❌ Echo not available');
            return;
        }

        // For public channel
        const channel = echo.channel('your-channel-name');
        
        // For private channel
        // const channel = echo.private('private-user.' + userId);

        channel.subscribed(() => {
            console.log('✅ Connected to channel');
        });

        channel.listen('.your.event.name', (event) => {
            console.log('📨 Received:', event);
            setData(event);
        });

        channel.error((error) => {
            console.error('❌ Channel error:', error);
        });

        return () => {
            channel.stopListening('.your.event.name');
            echo.leave('your-channel-name');
        };
    }, []);

    return (
        <div>
            {data && <pre>{JSON.stringify(data, null, 2)}</pre>}
        </div>
    );
}
```

---

## Channel Types

### Public Channel
**No authentication required**
```php
// Event
public function broadcastOn(): array
{
    return [new Channel('public-channel')];
}
```
```typescript
// Frontend
echo.channel('public-channel').listen('.event.name', callback);
```

### Private Channel
**Requires authentication via `/broadcasting/auth`**
```php
// Event
public function broadcastOn(): array
{
    return [new PrivateChannel('private-user.' . $this->userId)];
}

// routes/channels.php
Broadcast::channel('private-user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
```
```typescript
// Frontend
echo.private('private-user.' + userId).listen('.event.name', callback);
```

---

## Key Points

✅ **Use `ShouldBroadcastNow`** - Broadcasts immediately (not queued)  
✅ **Public channels** - Use `Channel` class, `echo.channel()`  
✅ **Private channels** - Use `PrivateChannel` class, `echo.private()`, requires auth  
✅ **Event naming** - Use `broadcastAs()` to set custom event name (prefix with `.`)  
✅ **CSRF token** - Required in `<head>` for authenticated requests  
✅ **Auth endpoint** - `/broadcasting/auth` registered via `Broadcast::routes()`  

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| 404 on `/pusher/auth` | Set `authEndpoint: '/broadcasting/auth'` in Pusher config |
| 403 on `/broadcasting/auth` | Check channel authorization in `routes/channels.php` |
| 419 CSRF error | Add CSRF meta tag to `app.blade.php` |
| Echo not available | Initialize Echo in `app.tsx` before React app |
| No events received | Check event implements `ShouldBroadcastNow` |

---

## Testing

```bash
# Trigger event via tinker
php artisan tinker
>>> broadcast(new \App\Events\YourEventName('test data'));

# Check routes
php artisan route:list | grep broadcast
```

---

**That's it!** Events broadcast from Laravel → Ably → React instantly. 🚀
