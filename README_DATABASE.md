# Connected Accounts Database

## Overview

Scalable and secure database structure for storing Pipedream connected accounts with encryption, indexing, and comprehensive tracking.

## Features

- **Encryption**: Metadata automatically encrypted at rest
- **Soft Deletes**: Audit trail maintained
- **Indexes**: Optimized for common queries
- **Status Tracking**: Connection status and expiration monitoring
- **Error Tracking**: Last error stored for debugging
- **Sync Tracking**: Last sync timestamp for data freshness

## Migration

```bash
# Run migrations
php artisan migrate

# If table exists, update migration will add new fields
php artisan migrate
```

## Table Structure

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | BIGINT | Primary key |
| `user_id` | BIGINT | Foreign key to users |
| `app_name` | VARCHAR(50) | Application name |
| `pipedream_account_id` | VARCHAR(255) | Pipedream account ID (unique) |
| `external_user_id` | VARCHAR(255) | External user ID |
| `metadata` | JSON | Encrypted account data |
| `is_active` | BOOLEAN | Active status |
| `token_expires_at` | TIMESTAMP | Token expiration |
| `last_synced_at` | TIMESTAMP | Last sync time |
| `connection_status` | ENUM | Status: connected, disconnected, expired, error |
| `last_error` | TEXT | Last error message |
| `created_at` | TIMESTAMP | Creation time |
| `updated_at` | TIMESTAMP | Update time |
| `deleted_at` | TIMESTAMP | Soft delete time |

### Indexes

**Single Column:**
- `user_id`, `app_name`, `pipedream_account_id`, `external_user_id`
- `is_active`, `connection_status`, `token_expires_at`

**Composite:**
- `idx_user_app_active` - (user_id, app_name, is_active)
- `idx_user_active_status` - (user_id, is_active, connection_status)
- `idx_app_active` - (app_name, is_active)
- `idx_status_active` - (connection_status, is_active)
- `idx_token_expires` - (token_expires_at, is_active)

## Usage

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
if ($account->isExpired()) {
    $account->markAsExpired();
}

if ($account->needsSync(24)) {
    // Sync account
}
```

## Security

- Metadata encrypted using Laravel encryption
- Soft deletes for audit trail
- Enum constraints for data validation
- User ownership enforced

## Scalability

- Optimized indexes for common queries
- Repository pattern for data access
- Model scopes for reusable queries
- Batch operations support
- Pagination for large datasets

