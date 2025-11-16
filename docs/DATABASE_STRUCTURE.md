# Database Structure - Connected Accounts

## Table Schema

### connected_accounts

```sql
CREATE TABLE connected_accounts (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    app_name VARCHAR(50) NOT NULL,
    pipedream_account_id VARCHAR(255) NOT NULL UNIQUE,
    external_user_id VARCHAR(255) NULL,
    metadata JSON NULL,
    is_active BOOLEAN DEFAULT TRUE,
    token_expires_at TIMESTAMP NULL,
    last_synced_at TIMESTAMP NULL,
    connection_status ENUM('connected', 'disconnected', 'expired', 'error') DEFAULT 'connected',
    last_error TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_app (user_id, app_name),
    
    INDEX idx_user_app_active (user_id, app_name, is_active),
    INDEX idx_user_active_status (user_id, is_active, connection_status),
    INDEX idx_app_active (app_name, is_active),
    INDEX idx_status_active (connection_status, is_active),
    INDEX idx_token_expires (token_expires_at, is_active),
    INDEX idx_user_id (user_id),
    INDEX idx_app_name (app_name),
    INDEX idx_pipedream_account_id (pipedream_account_id),
    INDEX idx_external_user_id (external_user_id),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Indexes for Performance

### Composite Indexes

1. **idx_user_app_active** - Optimizes queries for active user connections
   ```php
   ConnectedAccount::where('user_id', $userId)
       ->where('app_name', $appName)
       ->where('is_active', true)
       ->first();
   ```

2. **idx_user_active_status** - Optimizes user account listings
   ```php
   ConnectedAccount::where('user_id', $userId)
       ->where('is_active', true)
       ->where('connection_status', 'connected')
       ->get();
   ```

3. **idx_token_expires** - Optimizes expired token queries
   ```php
   ConnectedAccount::where('token_expires_at', '<', now())
       ->where('is_active', true)
       ->get();
   ```

## Security Features

### Encryption

Metadata is automatically encrypted using Laravel's encryption:

```php
// Automatically encrypted when saving
$account->metadata = ['email' => 'user@example.com'];
$account->save();

// Automatically decrypted when reading
$email = $account->metadata['email'];
```

### Soft Deletes

Accounts are soft deleted to maintain audit trail:

```php
$account->delete(); // Sets deleted_at timestamp
$account->restore(); // Restores soft deleted account
$account->forceDelete(); // Permanently deletes
```

### Data Validation

Model validation ensures data integrity:

```php
// Only valid connection statuses allowed
$account->connection_status = 'connected'; // Valid
$account->connection_status = 'invalid'; // Throws error
```

## Scalability Features

### Query Optimization

Use repository pattern for optimized queries:

```php
use App\Repositories\ConnectedAccountRepository;

$repository = new ConnectedAccountRepository();

// Optimized query with indexes
$account = $repository->getActiveAccount($userId, 'gmail');

// Efficient counting
$count = $repository->countActiveConnections($userId);
```

### Batch Operations

Process accounts in batches:

```php
// Sync accounts in batches
$accounts = $repository->getConnectionsNeedingSync(24, 100);
foreach ($accounts as $account) {
    // Process account
}
```

### Pagination

Use pagination for large datasets:

```php
$accounts = $repository->getPaginatedAccountsForUser($userId, 15);
```

## Model Scopes

### Active Connections

```php
ConnectedAccount::active()->get();
// Returns only active, connected accounts
```

### For Specific App

```php
ConnectedAccount::forApp('gmail')->get();
```

### For Specific User

```php
ConnectedAccount::forUser($userId)->get();
```

### Expired Connections

```php
ConnectedAccount::expired()->get();
// Returns connections with expired tokens
```

### Needs Syncing

```php
ConnectedAccount::needsSync(24)->get();
// Returns connections not synced in 24 hours
```

## Scheduled Tasks

### Sync Accounts

```php
// app/Console/Kernel.php
$schedule->command('pipedream:sync-accounts')
    ->hourly()
    ->withoutOverlapping();
```

### Deactivate Expired

```php
$schedule->command('pipedream:deactivate-expired')
    ->daily()
    ->at('02:00');
```

## Best Practices

### 1. Always Use Repository

```php
// Good
$repository->getActiveAccount($userId, 'gmail');

// Avoid
ConnectedAccount::where('user_id', $userId)
    ->where('app_name', 'gmail')
    ->first();
```

### 2. Use Scopes

```php
// Good
ConnectedAccount::active()->forUser($userId)->get();

// Avoid
ConnectedAccount::where('is_active', true)
    ->where('connection_status', 'connected')
    ->where('user_id', $userId)
    ->get();
```

### 3. Handle Encryption

```php
// Metadata is automatically encrypted/decrypted
$account->metadata = ['sensitive' => 'data'];
$account->save();

// Access decrypted data
$data = $account->metadata;
```

### 4. Monitor Expired Connections

```php
// Check expiration
if ($account->isExpired()) {
    $account->markAsExpired();
}

// Check sync status
if ($account->needsSync(24)) {
    // Sync account
}
```

## Performance Considerations

### Index Usage

All queries should use indexed columns:
- `user_id` - Indexed
- `app_name` - Indexed
- `is_active` - Indexed
- `connection_status` - Indexed
- `pipedream_account_id` - Unique index

### Query Patterns

Optimized query patterns:

```php
// Uses idx_user_app_active
$account = ConnectedAccount::where('user_id', $userId)
    ->where('app_name', $appName)
    ->where('is_active', true)
    ->first();

// Uses idx_user_active_status
$accounts = ConnectedAccount::where('user_id', $userId)
    ->where('is_active', true)
    ->where('connection_status', 'connected')
    ->get();
```

## Migration Commands

```bash
# Run migration
php artisan migrate

# Rollback migration
php artisan migrate:rollback

# Check migration status
php artisan migrate:status
```

