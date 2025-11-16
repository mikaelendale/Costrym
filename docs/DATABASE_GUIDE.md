# Database Guide - Connected Accounts

## Overview

Scalable and secure database structure for storing connected accounts with encryption, indexing, and audit trails.

## Table Structure

### Fields

| Field | Type | Description | Indexed |
|-------|------|-------------|---------|
| `id` | BIGINT | Primary key | Yes |
| `user_id` | BIGINT | Foreign key to users | Yes |
| `app_name` | VARCHAR(50) | Application name | Yes |
| `pipedream_account_id` | VARCHAR(255) | Pipedream account ID | Yes (Unique) |
| `external_user_id` | VARCHAR(255) | External user ID | Yes |
| `metadata` | JSON | Encrypted account data | No |
| `is_active` | BOOLEAN | Active status | Yes |
| `token_expires_at` | TIMESTAMP | Token expiration | Yes |
| `last_synced_at` | TIMESTAMP | Last sync time | No |
| `connection_status` | ENUM | Connection status | Yes |
| `last_error` | TEXT | Last error message | No |
| `created_at` | TIMESTAMP | Creation time | No |
| `updated_at` | TIMESTAMP | Update time | No |
| `deleted_at` | TIMESTAMP | Soft delete time | No |

### Indexes

**Single Column:**
- `user_id` - Fast user lookups
- `app_name` - Fast app filtering
- `pipedream_account_id` - Unique account lookup
- `external_user_id` - External ID lookups
- `is_active` - Active status filtering
- `connection_status` - Status filtering
- `token_expires_at` - Expiration queries

**Composite:**
- `idx_user_app_active` - (user_id, app_name, is_active)
- `idx_user_active_status` - (user_id, is_active, connection_status)
- `idx_app_active` - (app_name, is_active)
- `idx_status_active` - (connection_status, is_active)
- `idx_token_expires` - (token_expires_at, is_active)

## Security Features

### Encryption

Metadata is automatically encrypted using Laravel's encryption:

```php
// Automatically encrypted
$account->metadata = ['email' => 'user@example.com', 'token' => 'secret'];
$account->save();

// Automatically decrypted
$email = $account->metadata['email'];
```

### Soft Deletes

Maintains audit trail:

```php
$account->delete(); // Sets deleted_at
$account->restore(); // Restores
$account->forceDelete(); // Permanent delete
```

### Data Validation

Enum constraints ensure valid status:

```php
$account->connection_status = 'connected'; // Valid
$account->connection_status = 'invalid'; // Database error
```

## Scalability Features

### Repository Pattern

Use repository for optimized queries:

```php
use App\Repositories\ConnectedAccountRepository;

$repository = new ConnectedAccountRepository();

// Optimized query
$account = $repository->getActiveAccount($userId, 'gmail');

// Efficient counting
$count = $repository->countActiveConnections($userId);

// Statistics
$stats = $repository->getConnectionStats($userId);
```

### Model Scopes

Use scopes for common queries:

```php
// Active connections
ConnectedAccount::active()->get();

// For specific app
ConnectedAccount::forApp('gmail')->get();

// For specific user
ConnectedAccount::forUser($userId)->get();

// Expired connections
ConnectedAccount::expired()->get();

// Needs syncing
ConnectedAccount::needsSync(24)->get();
```

### Batch Operations

Process in batches for performance:

```php
// Get accounts needing sync
$accounts = ConnectedAccount::needsSync(24)->limit(100)->get();

foreach ($accounts as $account) {
    // Process account
    $account->markAsSynced();
}
```

## Migration

### Initial Migration

```bash
php artisan migrate
```

### Update Migration

If table already exists, run update migration:

```bash
php artisan migrate
```

This adds:
- `token_expires_at`
- `last_synced_at`
- `connection_status`
- `last_error`
- `deleted_at` (soft deletes)
- Composite indexes

## Usage Examples

### Store Account

```php
use App\Services\PipedreamService;

$service = new PipedreamService();

$account = $service->storeAccount(
    userId: 1,
    appName: 'gmail',
    accountId: 'apn_xxxxx',
    externalUserId: '1',
    metadata: ['email' => 'user@example.com'],
    tokenExpiresAt: new \DateTime('2025-11-12 01:00:00')
);
```

### Retrieve Account

```php
// Using service
$account = $service->getStoredAccount(1, 'gmail');

// Using repository
$repository = new ConnectedAccountRepository();
$account = $repository->getActiveAccount(1, 'gmail');

// Using model scopes
$account = ConnectedAccount::active()
    ->forUser(1)
    ->forApp('gmail')
    ->first();
```

### Check Status

```php
// Check expiration
if ($account->isExpired()) {
    $account->markAsExpired();
}

// Check sync status
if ($account->needsSync(24)) {
    // Sync account
}

// Update status
$account->markAsSynced();
$account->markAsError('Connection failed');
```

### List Accounts

```php
// All active accounts for user
$accounts = $service->listStoredAccounts($userId);

// Using repository
$accounts = $repository->getActiveAccountsForUser($userId);

// Paginated
$accounts = $repository->getPaginatedAccountsForUser($userId, 15);
```

## Scheduled Tasks

Add to `routes/console.php` or `app/Console/Kernel.php`:

```php
use Illuminate\Console\Scheduling\Schedule;

$schedule->command('pipedream:sync-accounts')
    ->hourly()
    ->withoutOverlapping();

$schedule->command('pipedream:deactivate-expired')
    ->daily()
    ->at('02:00');
```

## Performance Tips

1. **Use Indexes**: All queries use indexed columns
2. **Use Scopes**: Leverage model scopes for common queries
3. **Use Repository**: Repository pattern for optimized queries
4. **Batch Operations**: Process accounts in batches
5. **Pagination**: Use pagination for large datasets

## Security Best Practices

1. **Encryption**: Metadata is automatically encrypted
2. **Soft Deletes**: Use soft deletes for audit trail
3. **Validation**: Validate all inputs before storing
4. **Access Control**: Always check user ownership
5. **Error Handling**: Store errors for debugging

