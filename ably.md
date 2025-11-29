# Laravel Broadcasting with Ably - Usage Guide

## Quick Setup

### Environment Variables
Add to your `.env` file:
```env
BROADCAST_CONNECTION=ably
ABLY_KEY=your_ably_key_here
ABLY_APP_ID=your_ably_app_id_here
VITE_ABLY_PUBLIC_KEY=your_ably_public_key_here
```

### Frontend Configuration
Echo is automatically configured in `resources/js/bootstrap.js` and imported in `resources/js/app.tsx`.

## Usage Examples

### 1. Basic Channel Listening
```javascript
import { useEcho } from '@/hooks/useEcho';

const { isConnected } = useEcho('channel-name', '.event.name', (data) => {
    console.log('Received:', data);
});
```

### 2. Private Channel (Requires Authentication)
```javascript
import { useEchoPrivate } from '@/hooks/useEcho';

const { isConnected } = useEchoPrivate('private-channel', '.event.name', (data) => {
    console.log('Private data:', data);
});
```

### 3. Presence Channel (User Presence)
```javascript
import { useEchoPresence } from '@/hooks/useEcho';

const { isConnected } = useEchoPresence('presence-channel', '.event.name', (data) => {
    console.log('Presence data:', data);
});
```

## Creating Broadcast Events

### 1. Create Event Class
```bash
php artisan make:event MyEvent
```

### 2. Implement ShouldBroadcastNow
```php
<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MyEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function broadcastOn()
    {
        return new Channel('my-channel');
    }

    public function broadcastAs()
    {
        return 'my.event';
    }
}
```

### 3. Broadcast the Event
```php
use App\Events\MyEvent;

broadcast(new MyEvent($data));
```

## Testing Broadcasting

### Using Artisan Command
```bash
php artisan test:broadcast "Your message here"
```

### Using Tinker
```bash
php artisan tinker
```
```php
broadcast(new \App\Events\TestMessage('Hello from Tinker!'));
```

## Event Naming Convention

- **Laravel Event**: `MyEvent`
- **Broadcast Name**: `my.event` (from `broadcastAs()` method)
- **Frontend Listener**: `.my.event` (note the dot prefix)

## Channel Types

- **Public**: `new Channel('public-channel')`
- **Private**: `new PrivateChannel('private-channel')`
- **Presence**: `new PresenceChannel('presence-channel')`

## Connection Status

The `useEcho` hook returns `{ channel, isConnected }` where:
- `isConnected`: Boolean indicating connection status
- `channel`: The Echo channel instance

## Troubleshooting

1. **Echo not available**: Ensure `bootstrap.js` is imported in `app.tsx`
2. **Events not received**: Check event name has dot prefix (`.event.name`)
3. **Connection issues**: Verify Ably credentials in `.env`
4. **Private channels**: Ensure proper authorization in `routes/channels.php`

## File Structure

```
resources/js/
├── bootstrap.js          # Echo configuration
├── app.tsx              # Main app entry point
├── hooks/
│   └── useEcho.js       # React hooks for Echo
└── pages/
    └── dashboard.tsx    # Example usage

app/
├── Events/
│   └── TestMessage.php  # Example event
└── Console/Commands/
    └── TestBroadcast.php # Test command
```

This setup provides real-time communication between your Laravel backend and React frontend using Ably as the broadcasting service.