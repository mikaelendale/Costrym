<?php

namespace App\Tools;

use App\Models\ConnectedAccount;
use App\Services\PipedreamService;
use Illuminate\Support\Facades\Log;
use Vizra\VizraADK\Contracts\ToolInterface;
use Vizra\VizraADK\Memory\AgentMemory;
use Vizra\VizraADK\System\AgentContext;

/**
 * Dynamic Zoho Books Action Tool
 *
 * Single tool that can execute any Zoho Books action dynamically.
 */
class ZohoBooksActionTool implements ToolInterface
{
    protected ?int $userId = null;

    protected PipedreamService $pipedreamService;

    public function __construct(?int $userId = null)
    {
        $this->userId = $userId;
        $this->pipedreamService = app(PipedreamService::class);
    }

    /**
     * Get the tool's definition for the LLM
     */
    public function definition(): array
    {
        return [
            'name' => 'zoho_books_action',
            'description' => 'Execute any Zoho Books action. Use this tool to interact with Zoho Books - get expenses, invoices, customers, bills, and more. The action_name should be the Pipedream component key (e.g., "zoho_books-list-expenses"). Use list_available_tools first to see all available Zoho Books actions.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'action_name' => [
                        'type' => 'string',
                        'description' => 'The Zoho Books action to execute (component key, e.g., "zoho_books-list-expenses", "zoho_books-get-invoice")',
                    ],
                    'parameters' => [
                        'type' => 'object',
                        'description' => 'Parameters for the Zoho Books action (varies by action). Common parameters: organization_id, date_from, date_to, customer_id, etc.',
                        'additionalProperties' => true,
                    ],
                ],
                'required' => ['action_name'],
            ],
        ];
    }

    /**
     * Execute the Zoho Books action
     */
    public function execute(array $arguments, AgentContext $context, AgentMemory $memory): string
    {
        try {
            $userId = $this->userId ?? $context->getState('user_id');

            if (! $userId) {
                return json_encode([
                    'success' => false,
                    'error' => 'User ID not found in context. Cannot execute Zoho Books action.',
                ]);
            }

            $actionName = $arguments['action_name'] ?? null;
            if (! $actionName) {
                return json_encode([
                    'success' => false,
                    'error' => 'action_name parameter is required',
                ]);
            }

            // Normalize action name (convert underscores to hyphens)
            if (str_contains($actionName, '_') && ! str_contains($actionName, '-')) {
                if (str_starts_with($actionName, 'zoho_books_')) {
                    $actionName = str_replace('zoho_books_', 'zoho_books-', $actionName);
                    $parts = explode('-', $actionName, 2);
                    if (count($parts) === 2) {
                        $actionPart = str_replace('_', '-', $parts[1]);
                        $actionName = $parts[0].'-'.$actionPart;
                    }
                }
            }

            // Verify it's a Zoho Books action
            if (! str_starts_with($actionName, 'zoho_books-') && ! str_starts_with($actionName, 'zoho-')) {
                return json_encode([
                    'success' => false,
                    'error' => "Invalid action. Zoho Books actions must start with 'zoho_books-' or 'zoho-'. Got: {$actionName}",
                ]);
            }

            // Get connected account
            $connectedAccount = ConnectedAccount::where('user_id', $userId)
                ->where('app_name', 'zoho_books')
                ->where('is_active', true)
                ->first();

            if (! $connectedAccount) {
                return json_encode([
                    'success' => false,
                    'error' => 'Zoho Books account not connected. Please connect your Zoho Books account first.',
                ]);
            }

            Log::info('ZohoBooksActionTool: Executing action', [
                'user_id' => $userId,
                'action' => $actionName,
                'has_parameters' => ! empty($arguments['parameters']),
            ]);

            // Build configured props
            $configuredProps = [
                'zoho_books' => [
                    'authProvisionId' => $connectedAccount->pipedream_account_id,
                ],
            ];

            // Add action parameters
            $actionParams = $arguments['parameters'] ?? [];

            // Handle if parameters is a JSON string (LLM sometimes passes it as string)
            if (is_string($actionParams)) {
                $decoded = json_decode($actionParams, true);
                $actionParams = $decoded !== null ? $decoded : [];
            }

            // Ensure it's an array
            if (! is_array($actionParams)) {
                $actionParams = [];
            }

            foreach ($actionParams as $key => $value) {
                $configuredProps[$key] = $value;
            }

            // Execute the action
            $result = $this->pipedreamService->runAction(
                $actionName,
                $connectedAccount->external_user_id ?? (string) $userId,
                $configuredProps
            );

            if ($result['success']) {
                $responseData = $result['data'];

                // Check for errors in Pipedream response
                if (isset($responseData['os']) && is_array($responseData['os'])) {
                    foreach ($responseData['os'] as $logEntry) {
                        if (isset($logEntry['k']) && $logEntry['k'] === 'error') {
                            return json_encode([
                                'success' => false,
                                'error' => $logEntry['err']['message'] ?? 'Zoho Books API error',
                                'action' => $actionName,
                            ]);
                        }
                    }
                }

                return json_encode([
                    'success' => true,
                    'data' => $responseData['ret'] ?? $responseData,
                    'action' => $actionName,
                ]);
            }

            return json_encode([
                'success' => false,
                'error' => $result['error'] ?? 'Failed to execute Zoho Books action',
                'action' => $actionName,
            ]);

        } catch (\Exception $e) {
            Log::error('ZohoBooksActionTool execution error', [
                'action' => $arguments['action_name'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            return json_encode([
                'success' => false,
                'error' => 'Tool execution failed: '.$e->getMessage(),
            ]);
        }
    }
}
