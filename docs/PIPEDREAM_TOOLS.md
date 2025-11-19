# Pipedream Tools Integration

Pipedream actions are automatically loaded as tools for AI agents based on user's connected accounts.

## Architecture

- **`PipedreamTool`** - Dynamic tool wrapper for any Pipedream action
- **`PipedreamToolLoader`** - Loads tools based on connected accounts
- **`LoadsPipedreamTools`** - Trait for agents needing Pipedream access

## Usage

```php
use App\Traits\LoadsPipedreamTools;

class MyAgent extends BaseLlmAgent
{
    use LoadsPipedreamTools;

    public function beforeLlmCall(array $inputMessages, AgentContext $context): array
    {
        $this->loadPipedreamTools($context);
        return parent::beforeLlmCall($inputMessages, $context);
    }
}
```

## Scalability

- **App-agnostic**: Works with any Pipedream app (Notion, Slack, Gmail, etc.)
- **Dynamic schema**: Tool parameters built from component's `configurable_props`
- **Automatic auth**: Uses user's connected account automatically
- **Actions only**: Only executable actions are loaded (triggers are event sources)

## API

- `POST /notion-agent/chat` - Chat with Notion agent
- `GET /notion-agent/actions` - List available actions

## Sync Components

```bash
php artisan pipedream:sync {app_name}
```

