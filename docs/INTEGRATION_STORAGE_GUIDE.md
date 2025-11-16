# Integration Storage Guide

## Quick Reference

### Storing a Connected Account

```php
use App\Services\PipedreamService;

$service = new PipedreamService();
$account = $service->storeAccount(
    userId: 1,
    appName: 'gmail',
    accountId: 'apn_xxxxx',
    externalUserId: '1',
    metadata: []
);
```

### Retrieving Stored Account

```php
$account = $service->getStoredAccount(1, 'gmail');
// Returns: ConnectedAccount model or null
```

### List All Accounts

```php
$accounts = $service->listStoredAccounts(1);
// Returns: Collection of ConnectedAccount models
```

### Make API Request

```php
$result = $service->makeApiRequest(
    accountId: 'apn_xxxxx',
    method: 'GET',
    endpoint: 'https://gmail.googleapis.com/gmail/v1/users/me/messages'
);
```

## Data Structure

### What Gets Stored

```php
ConnectedAccount {
    id: 1
    user_id: 1
    app_name: "gmail"
    pipedream_account_id: "apn_xxxxx"
    external_user_id: "1"
    metadata: {
        "account_id": "apn_xxxxx",
        "app": "gmail",
        "connected_at": "2025-11-11T21:49:20.000Z",
        // Full account details from Pipedream
    }
    is_active: true
    created_at: "2025-11-11T21:49:20.000000Z"
    updated_at: "2025-11-11T21:49:20.000000Z"
}
```

## Integration Config

### Available Integrations

```php
use App\Helpers\IntegrationHelper;

$integrations = IntegrationHelper::getAvailableIntegrations();
// Returns all integrations from config/integrations.php
```

### Check Integration

```php
if (IntegrationHelper::isValidIntegration('gmail')) {
    // Integration is available
}

if (IntegrationHelper::requiresPipedream('gmail')) {
    // Use Pipedream Connect
}
```

## API Endpoints

| Endpoint | Method | Description | Returns |
|----------|--------|-------------|---------|
| `/connect/token` | POST | Generate Connect token | `{success, token, expires_at}` |
| `/connect/{app}/save` | POST | Save connected account | `{success, message, account}` |
| `/connect/accounts` | GET | List user's accounts | `{success, accounts[]}` |
| `/connect/{app}/request` | POST | Make API request | `{success, data, status}` |

## Complete Flow Example

```php
// 1. Frontend connects account via SDK
// 2. Frontend sends account ID to backend
// 3. Backend stores account
$account = $service->storeAccount(...);

// 4. Retrieve stored account
$stored = $service->getStoredAccount($userId, 'gmail');

// 5. Use account for API requests
$result = $service->makeApiRequest(
    accountId: $stored->pipedream_account_id,
    method: 'GET',
    endpoint: 'https://gmail.googleapis.com/gmail/v1/users/me/messages'
);
```

