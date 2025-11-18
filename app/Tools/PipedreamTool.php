<?php

namespace App\Tools;

use App\Models\ConnectedAccount;
use App\Models\PipedreamComponent;
use App\Services\PipedreamService;
use Illuminate\Support\Facades\Log;
use Vizra\VizraADK\Contracts\ToolInterface;
use Vizra\VizraADK\Memory\AgentMemory;
use Vizra\VizraADK\System\AgentContext;

/**
 * Dynamic Pipedream Tool
 *
 * This tool is instantiated with a specific Pipedream component
 * and can execute that component's action using the user's connected account.
 */
class PipedreamTool implements ToolInterface
{
    protected PipedreamComponent $component;

    protected PipedreamService $pipedreamService;

    protected ?int $userId = null;

    public function __construct(PipedreamComponent $component, ?int $userId = null)
    {
        $this->component = $component;
        $this->userId = $userId;
        $this->pipedreamService = app(PipedreamService::class);
    }

    /**
     * Get the tool's definition for the LLM
     */
    public function definition(): array
    {
        $componentData = $this->component->component_data ?? [];
        $configurableProps = $componentData['configurable_props'] ?? [];

        // Build parameters schema from component's configurable props
        $properties = [];
        $required = [];

        foreach ($configurableProps as $prop) {
            $propName = $prop['name'] ?? null;
            $propType = $prop['type'] ?? 'string';
            $isOptional = ($prop['optional'] ?? false) || ($propName === $this->component->app_name);

            // Skip app authentication props (dynamic based on app_name) and info labels
            if (! $propName || $propName === $this->component->app_name || $propName === 'infoLabel') {
                continue;
            }

            // Map Pipedream types to JSON schema types
            $jsonType = match ($propType) {
                'boolean' => 'boolean',
                'integer', 'number' => 'number',
                'string[]' => 'array',
                default => 'string',
            };

            $properties[$propName] = [
                'type' => $jsonType,
                'description' => $prop['description'] ?? $prop['label'] ?? "The {$propName} parameter",
            ];

            // Handle array items
            if ($jsonType === 'array' && isset($prop['items'])) {
                $properties[$propName]['items'] = ['type' => 'string'];
            }

            // Handle options/enum
            if (isset($prop['options']) && is_array($prop['options'])) {
                $properties[$propName]['enum'] = $prop['options'];
            }

            if (! $isOptional) {
                $required[] = $propName;
            }
        }

        $description = $this->component->description ?? $componentData['description'] ?? "Execute {$this->component->component_name} action";

        return [
            'name' => $this->getToolName(),
            'description' => $description,
            'parameters' => [
                'type' => 'object',
                'properties' => $properties,
                'required' => $required,
            ],
        ];
    }

    /**
     * Execute the Pipedream action
     */
    public function execute(array $arguments, AgentContext $context, AgentMemory $memory): string
    {
        try {
            // Get user ID from context if not set in constructor
            $userId = $this->userId ?? $context->getState('user_id');

            if (! $userId) {
                return json_encode([
                    'success' => false,
                    'error' => 'User ID not found in context. Cannot execute Pipedream action.',
                ]);
            }

            // Get connected account for this app
            $connectedAccount = ConnectedAccount::where('user_id', $userId)
                ->where('app_name', $this->component->app_name)
                ->where('is_active', true)
                ->first();

            if (! $connectedAccount) {
                return json_encode([
                    'success' => false,
                    'error' => "No active connection found for {$this->component->app_name}. Please connect your account first.",
                ]);
            }

            // Build configured props for Pipedream API
            $configuredProps = [
                $this->component->app_name => [
                    'authProvisionId' => $connectedAccount->pipedream_account_id,
                ],
            ];

            // Add all action parameters to root level
            foreach ($arguments as $key => $value) {
                $configuredProps[$key] = $value;
            }

            // Execute the action
            $result = $this->pipedreamService->runAction(
                $this->component->component_key,
                $connectedAccount->external_user_id ?? (string) $userId,
                $configuredProps
            );

            if ($result['success']) {
                $responseData = $result['data'] ?? null;

                // Check if Pipedream returned an error in the response (action executed but Notion API errored)
                if (isset($responseData['os']) && is_array($responseData['os'])) {
                    foreach ($responseData['os'] as $logEntry) {
                        if (isset($logEntry['k']) && $logEntry['k'] === 'error' && isset($logEntry['err'])) {
                            $error = $logEntry['err'];
                            $errorMessage = $error['name'] ?? 'Unknown error';
                            if (isset($error['body'])) {
                                $errorBody = is_string($error['body']) ? json_decode($error['body'], true) : $error['body'];
                                if (isset($errorBody['message'])) {
                                    $errorMessage = $errorBody['message'];
                                } elseif (isset($errorBody['code'])) {
                                    $errorMessage = $errorBody['code'].': '.($errorBody['message'] ?? $errorMessage);
                                }
                            }

                            return json_encode([
                                'success' => false,
                                'error' => $errorMessage,
                                'notion_error_code' => $error['code'] ?? null,
                                'notion_status' => $error['status'] ?? null,
                            ]);
                        }
                    }
                }

                // Return successful response
                return json_encode([
                    'success' => true,
                    'data' => $responseData['ret'] ?? $responseData,
                ]);
            }

            return json_encode([
                'success' => false,
                'error' => $result['error'] ?? 'Failed to execute action',
            ]);

        } catch (\Exception $e) {
            Log::error('PipedreamTool execution error', [
                'component_key' => $this->component->component_key,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return json_encode([
                'success' => false,
                'error' => 'Tool execution failed: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Get a unique tool name for this component
     */
    protected function getToolName(): string
    {
        // Convert component key to a valid tool name
        // e.g., "notion-create-page" -> "pipedream_notion_create_page"
        $name = str_replace('-', '_', $this->component->component_key);

        return 'pipedream_'.$name;
    }

    /**
     * Get the component this tool represents
     */
    public function getComponent(): PipedreamComponent
    {
        return $this->component;
    }
}
