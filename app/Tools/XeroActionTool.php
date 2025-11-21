<?php

namespace App\Tools;

use App\Models\ConnectedAccount;
use App\Services\PipedreamService;
use Illuminate\Support\Facades\Log;
use Vizra\VizraADK\Contracts\ToolInterface;
use Vizra\VizraADK\Memory\AgentMemory;
use Vizra\VizraADK\System\AgentContext;

/**
 * Dynamic Xero Action Tool
 *
 * This is a single tool that can execute any Xero action dynamically,
 * avoiding the need to load 40+ individual tools.
 */
class XeroActionTool implements ToolInterface
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
            'name' => 'xero_action',
            'description' => 'Execute any Xero accounting action. Use this tool to interact with Xero - get invoices, customers, payments, accounts, reports, and more. The action_name should be the Pipedream component key (e.g., "xero_accounting_api-list-invoices", "xero_accounting_api-get-invoice-by-id"). Use list_available_tools first to see all available Xero actions.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'action_name' => [
                        'type' => 'string',
                        'description' => 'The Xero action to execute (component key, e.g., "xero_accounting_api-list-invoices", "xero_accounting_api-get-invoice-by-id", "xero_accounting_api-search-customers")',
                    ],
                    'parameters' => [
                        'type' => 'object',
                        'description' => 'Parameters for the Xero action (varies by action). Common parameters: tenant_id, invoice_id, customer_id, date_from, date_to, etc.',
                        'additionalProperties' => true,
                    ],
                ],
                'required' => ['action_name'],
            ],
        ];
    }

    /**
     * Execute the Xero action
     */
    public function execute(array $arguments, AgentContext $context, AgentMemory $memory): string
    {
        Log::info('XeroActionTool execute called', [
            'user_id' => $this->userId ?? $context->getState('user_id'),
            'action_name' => $arguments['action_name'] ?? null,
            'parameters' => $arguments['parameters'] ?? null,
            'session_id' => $context->getSessionId(),
        ]);
        try {
            // Get user ID from context if not set in constructor
            $userId = $this->userId ?? $context->getState('user_id');

            if (! $userId) {
                return json_encode([
                    'success' => false,
                    'error' => 'User ID not found in context. Cannot execute Xero action.',
                ]);
            }

            $actionName = $arguments['action_name'] ?? null;
            if (! $actionName) {
                return json_encode([
                    'success' => false,
                    'error' => 'action_name parameter is required',
                ]);
            }

            // Normalize action name: convert underscores to hyphens if needed
            // LLM sometimes uses underscores (xero_accounting_api_list_invoices)
            // but Pipedream uses hyphens (xero_accounting_api-list-invoices)
            if (str_contains($actionName, '_') && ! str_contains($actionName, '-')) {
                // Only replace underscores AFTER the app name prefix
                // e.g., xero_accounting_api_list_invoices → xero_accounting_api-list-invoices
                if (str_starts_with($actionName, 'xero_accounting_api_')) {
                    $actionName = str_replace('xero_accounting_api_', 'xero_accounting_api-', $actionName);
                    // Replace remaining underscores in the action part only (after the prefix)
                    $parts = explode('-', $actionName, 2);
                    if (count($parts) === 2) {
                        $actionPart = str_replace('_', '-', $parts[1]);
                        $actionName = $parts[0].'-'.$actionPart;
                    }
                }
            }

            // Verify it's a Xero action
            if (! str_starts_with($actionName, 'xero_accounting_api-')) {
                return json_encode([
                    'success' => false,
                    'error' => "Invalid action. Xero actions must start with 'xero_accounting_api-'. Got: {$actionName}",
                ]);
            }

            // Get connected Xero account
            $connectedAccount = ConnectedAccount::where('user_id', $userId)
                ->where('app_name', 'xero_accounting_api')
                ->where('is_active', true)
                ->first();

            if (! $connectedAccount) {
                return json_encode([
                    'success' => false,
                    'error' => 'Xero account not connected. Please connect your Xero account first.',
                ]);
            }

            // Build configured props for Pipedream API
            $configuredProps = [
                'xero_accounting_api' => [
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
                $responseData = $result['data'] ?? null;

                // Check if Pipedream returned an error in the response
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
                                'xero_error_code' => $error['code'] ?? null,
                                'xero_status' => $error['status'] ?? null,
                            ]);
                        }
                    }
                }

                // Return successful response
                return json_encode([
                    'success' => true,
                    'data' => $responseData['ret'] ?? $responseData,
                    'action' => $actionName,
                ]);
            }

            return json_encode([
                'success' => false,
                'error' => $result['error'] ?? 'Failed to execute Xero action',
            ]);

        } catch (\Exception $e) {
            Log::error('XeroActionTool execution error', [
                'action_name' => $arguments['action_name'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return json_encode([
                'success' => false,
                'error' => 'Tool execution failed: '.$e->getMessage(),
            ]);
        }
    }
}
