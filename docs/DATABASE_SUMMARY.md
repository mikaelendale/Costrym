# Database Summary - Connected Accounts

## Quick Start

### Run Migrations

```bash
# Run initial migration
php artisan migrate

# If table exists, run update migration
php artisan migrate
```

### Store Account

```php
use App\Services\PipedreamService;

$service = new PipedreamService();
$account = $service->storeAccount(
    userId: 1,
    appName: 'gmail',
    accountId: 'apn_xxxxx',
    externalUserId: '1',
    metadata: [],
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
```

## Database Schema

### Table: connected_accounts

**Primary Key:** `id`

**Foreign Keys:**
- `user_id` → `users.id` (CASCADE DELETE)

**Unique Constraints:**
- `pipedream_account_id` (unique)
- `(user_id, app_name)` (one connection per app per user)

**Indexes:**
- Single: `user_id`, `app_name`, `pipedream_account_id`, `external_user_id`, `is_active`, `connection_status`, `token_expires_at`
- Composite: `idx_user_app_active`, `idx_user_active_status`, `idx_app_active`, `idx_status_active`, `idx_token_expires`

**Fields:**
- `id` - Primary key
- `user_id` - User reference
- `app_name` - Application name (indexed)
- `pipedream_account_id` - Pipedream account ID (unique, indexed)
- `external_user_id` - External user ID (indexed)
- `metadata` - Encrypted JSON data
- `is_active` - Active status (indexed)
- `token_expires_at` - Token expiration (indexed)
- `last_synced_at` - Last sync timestamp
- `connection_status` - Status enum (indexed)
- `last_error` - Error message
- `created_at` - Creation timestamp
- `updated_at` - Update timestamp
- `deleted_at` - Soft delete timestamp

## Security

- **Encryption**: Metadata automatically encrypted/decrypted
- **Soft Deletes**: Audit trail maintained
- **Validation**: Enum constraints for status
- **Access Control**: User ownership enforced

## Scalability

- **Indexes**: Optimized for common queries
- **Repository Pattern**: Data access abstraction
- **Scopes**: Reusable query filters
- **Batch Operations**: Process in batches
- **Pagination**: Handle large datasets

## Return Values

### ConnectedAccount Model

```php
{
    id: 1,
    user_id: 1,
    app_name: "gmail",
    pipedream_account_id: "apn_xxxxx",
    external_user_id: "1",
    metadata: {
        "account_id": "apn_xxxxx",
        "app": "gmail",
        "connected_at": "2025-11-11T21:49:20.000Z",
        "email": "user@example.com"
    },
    is_active: true,
    token_expires_at: "2025-11-12T01:00:00.000000Z",
    last_synced_at: "2025-11-11T21:49:20.000000Z",
    connection_status: "connected",
    last_error: null,
    created_at: "2025-11-11T21:49:20.000000Z",
    updated_at: "2025-11-11T21:49:20.000000Z",
    deleted_at: null
}
```

