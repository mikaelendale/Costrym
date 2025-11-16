# PipedreamService Documentation

## Overview

`PipedreamService` provides a structured interface for interacting with Pipedream Connect API. It handles authentication, token management, account storage, and API requests.

## Methods

### Authentication

#### `getOAuthAccessToken(): ?string`

Obtains OAuth access token using client credentials. Required for all Pipedream API requests.

**Returns:**
- `string|null` - Access token or null on failure

**Example:**
```php
$service = new PipedreamService();
$token = $service->getOAuthAccessToken();
```

#### `createConnectToken(string $externalUserId, array $allowedOrigins = []): array`

Creates a Connect token for frontend SDK. Two-step process: OAuth token, then Connect token.

**Parameters:**
- `$externalUserId` (string) - User ID in your system
- `$allowedOrigins` (array) - List of allowed origin URLs (defaults to app URL and localhost)

**Returns:**
```php
[
    'success' => true,
    'token' => 'ctok_xxxxx',
    'expires_at' => '2025-11-12T01:00:00.000Z'
]
```

**Example:**
```php
$result = $service->createConnectToken('user_123', ['https://example.com']);
if ($result['success']) {
    $token = $result['token'];
}
```

### Account Management

#### `getAccountDetails(string $accountId): ?array`

Retrieves account details from Pipedream API.

**Parameters:**
- `$accountId` (string) - Pipedream account ID

**Returns:**
- `array|null` - Account details or null on failure

**Example:**
```php
$details = $service->getAccountDetails('apn_xxxxx');
```

#### `storeAccount(int $userId, string $appName, string $accountId, string $externalUserId, array $metadata = []): ConnectedAccount`

Stores connected account in database. Creates or updates existing connection.

**Parameters:**
- `$userId` (int) - User ID
- `$appName` (string) - Application name (e.g., 'gmail', 'slack')
- `$accountId` (string) - Pipedream account ID
- `$externalUserId` (string) - External user ID
- `$metadata` (array) - Additional account metadata

**Returns:**
- `ConnectedAccount` - Created or updated account model

**Example:**
```php
$account = $service->storeAccount(
    userId: 1,
    appName: 'gmail',
    accountId: 'apn_xxxxx',
    externalUserId: 'user_123',
    metadata: ['email' => 'user@example.com']
);
```

**Stored Data Structure:**
```php
[
    'user_id' => 1,
    'app_name' => 'gmail',
    'pipedream_account_id' => 'apn_xxxxx',
    'external_user_id' => 'user_123',
    'metadata' => [
        'account_id' => 'apn_xxxxx',
        'app' => 'gmail',
        'connected_at' => '2025-11-11T21:49:20.000Z',
        'email' => 'user@example.com',
        // ... other account details from Pipedream
    ],
    'is_active' => true,
]
```

#### `getStoredAccount(int $userId, string $appName): ?ConnectedAccount`

Retrieves stored account for user and app.

**Parameters:**
- `$userId` (int) - User ID
- `$appName` (string) - Application name

**Returns:**
- `ConnectedAccount|null` - Account model or null if not found

**Example:**
```php
$account = $service->getStoredAccount(1, 'gmail');
if ($account) {
    $accountId = $account->pipedream_account_id;
    $metadata = $account->metadata;
}
```

#### `listStoredAccounts(int $userId)`

Lists all stored accounts for a user.

**Parameters:**
- `$userId` (int) - User ID

**Returns:**
- `\Illuminate\Database\Eloquent\Collection` - Collection of ConnectedAccount models

**Example:**
```php
$accounts = $service->listStoredAccounts(1);
foreach ($accounts as $account) {
    echo $account->app_name . ': ' . $account->pipedream_account_id;
}
```

#### `disconnectAccount(int $userId, string $appName): bool`

Deactivates a connected account.

**Parameters:**
- `$userId` (int) - User ID
- `$appName` (string) - Application name

**Returns:**
- `bool` - True if account was deactivated

**Example:**
```php
$disconnected = $service->disconnectAccount(1, 'gmail');
```

### API Requests

#### `makeApiRequest(string $accountId, string $method, string $endpoint, array $body = [], array $headers = []): array`

Makes API request to external service using connected account. Uses Pipedream API proxy.

**Parameters:**
- `$accountId` (string) - Pipedream account ID
- `$method` (string) - HTTP method (GET, POST, PUT, PATCH, DELETE)
- `$endpoint` (string) - External API endpoint URL
- `$body` (array) - Request body (optional)
- `$headers` (array) - Additional headers (optional)

**Returns:**
```php
[
    'success' => true,
    'data' => [...], // API response data
    'status' => 200
]
```

**Example:**
```php
// Get Gmail messages
$result = $service->makeApiRequest(
    accountId: 'apn_xxxxx',
    method: 'GET',
    endpoint: 'https://gmail.googleapis.com/gmail/v1/users/me/messages',
    headers: ['Accept' => 'application/json']
);

if ($result['success']) {
    $messages = $result['data'];
}
```

## Usage Examples

### Complete Flow: Connect and Store Account

```php
use App\Services\PipedreamService;
use Illuminate\Support\Facades\Auth;

$service = new PipedreamService();
$user = Auth::user();

// 1. Create Connect token for frontend
$tokenResult = $service->createConnectToken((string) $user->id);
// Frontend uses token to connect account via SDK

// 2. After frontend connects, save account
$account = $service->storeAccount(
    userId: $user->id,
    appName: 'gmail',
    accountId: 'apn_xxxxx', // From frontend SDK response
    externalUserId: (string) $user->id,
    metadata: [] // Optional additional data
);

// 3. Retrieve stored account
$storedAccount = $service->getStoredAccount($user->id, 'gmail');

// 4. Make API request
$result = $service->makeApiRequest(
    accountId: $storedAccount->pipedream_account_id,
    method: 'GET',
    endpoint: 'https://gmail.googleapis.com/gmail/v1/users/me/messages'
);
```

### Using Integration Config

```php
use Illuminate\Support\Facades\Config;

$integrations = Config::get('integrations.available');

foreach ($integrations as $key => $integration) {
    if ($integration['requires_pipedream']) {
        // Show connect button for this integration
        echo $integration['display_name'];
    }
}
```

## Return Value Reference

### ConnectedAccount Model

```php
ConnectedAccount {
    id: int
    user_id: int
    app_name: string
    pipedream_account_id: string
    external_user_id: string
    metadata: array {
        account_id: string
        app: string
        connected_at: string
        // ... other fields from Pipedream
    }
    is_active: bool
    created_at: Carbon
    updated_at: Carbon
}
```

### Account Details from Pipedream

```php
[
    'id' => 'apn_xxxxx',
    'app' => 'gmail',
    'external_user_id' => 'user_123',
    'status' => 'connected',
    // ... other account-specific fields
]
```

