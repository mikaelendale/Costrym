<?php

namespace App\Tools;

use App\Models\ConnectedAccount;
use App\Services\PipedreamService;
use Illuminate\Support\Facades\Log;
use Vizra\VizraADK\Contracts\ToolInterface;
use Vizra\VizraADK\Memory\AgentMemory;
use Vizra\VizraADK\System\AgentContext;

/**
 * Dynamic Expensify Action Tool
 *
 * Single tool that can execute any Expensify action dynamically.
 */
class ExpensifyActionTool implements ToolInterface
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
            'name' => 'expensify_action',
            'description' => 'Execute any Expensify action. Use this tool to interact with Expensify - get expenses, reports, receipts, reimbursements, and more. The action_name should be the Pipedream component key (e.g., "expensify-list-expenses"). Use list_available_tools first to see all available Expensify actions.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'action_name' => [
                        'type' => 'string',
                        'description' => 'The Expensify action to execute (component key, e.g., "expensify-list-expenses", "expensify-get-report")',
                    ],
                    'parameters' => [
                        'type' => 'object',
                        'description' => 'Parameters for the Expensify action (varies by action). Common parameters: report_id, start_date, end_date, status, etc.',
                        'additionalProperties' => true,
                    ],
                ],
                'required' => ['action_name'],
            ],
        ];
    }

    /**
     * Execute the Expensify action
     */
    public function execute(array $arguments, AgentContext $context, AgentMemory $memory): string
    {
        try {
            $userId = $this->userId ?? $context->getState('user_id');

            if (! $userId) {
                return json_encode([
                    'success' => false,
                    'error' => 'User ID not found in context. Cannot execute Expensify action.',
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
                if (str_starts_with($actionName, 'expensify_')) {
                    $actionName = str_replace('expensify_', 'expensify-', $actionName);
                    $parts = explode('-', $actionName, 2);
                    if (count($parts) === 2) {
                        $actionPart = str_replace('_', '-', $parts[1]);
                        $actionName = $parts[0].'-'.$actionPart;
                    }
                }
            }

            // Verify it's an Expensify action
            if (! str_starts_with($actionName, 'expensify-')) {
                return json_encode([
                    'success' => false,
                    'error' => "Invalid action. Expensify actions must start with 'expensify-'. Got: {$actionName}",
                ]);
            }

            // Get connected account
            $connectedAccount = ConnectedAccount::where('user_id', $userId)
                ->where('app_name', 'expensify')
                ->where('is_active', true)
                ->first();

            if (! $connectedAccount) {
                return json_encode([
                    'success' => false,
                    'error' => 'Expensify account not connected. Please connect your Expensify account first.',
                ]);
            }

            Log::info('ExpensifyActionTool: Executing action', [
                'user_id' => $userId,
                'action' => $actionName,
                'has_parameters' => ! empty($arguments['parameters']),
            ]);

            // Build configured props
            $configuredProps = [
                'expensify' => [
                    'authProvisionId' => $connectedAccount->pipedream_account_id,
                ],
            ];

            // Add action parameters
            $actionParams = $arguments['parameters'] ?? [];

            // Handle if parameters is a JSON string
            if (is_string($actionParams)) {
                $decoded = json_decode($actionParams, true);
                $actionParams = $decoded !== null ? $decoded : [];
            }

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

                // Check for errors in response
                if (isset($responseData['os']) && is_array($responseData['os'])) {
                    foreach ($responseData['os'] as $logEntry) {
                        if (isset($logEntry['k']) && $logEntry['k'] === 'error') {
                            return json_encode([
                                'success' => false,
                                'error' => $logEntry['err']['message'] ?? 'Expensify API error',
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
                'error' => $result['error'] ?? 'Failed to execute Expensify action',
                'action' => $actionName,
            ]);

        } catch (\Exception $e) {
            Log::error('ExpensifyActionTool execution error', [
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
