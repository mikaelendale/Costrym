<?php

namespace App\Agents;

use App\Traits\LoadsPipedreamTools;
use Illuminate\Support\Facades\Log;
use Vizra\VizraADK\Agents\BaseLlmAgent;
use Vizra\VizraADK\System\AgentContext;

/**
 * Execution Agent
 *
 * Executes user instructions by discovering and invoking Pipedream-backed tools
 * (Notion, Xero Accounting API, QuickBooks, Zoho Books, etc.) based on the
 * connected apps for the current user.
 *
 * Usage:
 *  - Limit scope with context: ['target_apps' => ['notion']]
 *  - Or load all connected apps (fallback)
 */
class ExecutionAgent extends BaseLlmAgent
{
    use LoadsPipedreamTools;

    protected string $name = 'execution_agent';

    protected string $description = 'Executes cross-app operations by listing and invoking the appropriate Pipedream tools (Notion, Xero, QuickBooks, etc.) based on the user’s connected integrations.';

    protected string $instructions = 'You are a financial data executor assistant. Follow the instructions provided in the user message.';

    // Keep explicit list tool available; the trait loads app-specific action tools.
    protected array $tools = [
        \App\Tools\XeroActionTool::class,
        // \App\Tools\ZohoBooksActionTool::class,
        // \App\Tools\QuickBooksActionTool::class,
        \App\Tools\ListAvailableToolsTool::class,
    ];

    // For execution requests we do not need chat history
    protected bool $includeConversationHistory = false;

    public function beforeLlmCall(array $inputMessages, AgentContext $context): array
    {
        // Get context and desired targets
        $userId = $context->getState('user_id');
        $integrationType = $context->getState('integration_type');

        Log::info('ExecutionAgent beforeLlmCall: Context state', [
            'user_id' => $userId,
            'integration_type' => $integrationType,
            'session_id' => $context->getSessionId(),
        ]);

        try {
            if (is_string($integrationType) && $integrationType !== '') {
                $integrationType = [$integrationType];
            }

            // Initialize tools based on integration type
            // Note: Since we don't have context state, we'll try to infer from user message or use default tools
            // The job should pre-render the prompt with all necessary instructions
            if ($userId && $integrationType) {
                $this->loadToolsForIntegration($integrationType, $userId);
            } else {
                // No context - can't load integration-specific tools
                // This is expected when the prompt is pre-rendered from the job
                Log::info('ExecutionAgent: No context state, using default behavior', [
                    'user_id' => $userId,
                    'session_id' => $context->getSessionId(),
                ]);
            }

            $toolCount = count($this->loadedTools);

            Log::info('ExecutionAgent loaded tools', [
                'user_id' => $userId,
                'integration_type' => $integrationType,
                'tool_count' => $toolCount,
            ]);

        } catch (\Exception $e) {
            Log::error('ExecutionAgent failed to load Pipedream tools', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Continue; the agent can still use list_available_tools or return guidance.
        }

        // Optional: surface nicer task metadata in context
        if ($name = $context->getState('task_name')) {
            $context->setState('task_name', $name);
        }
        if ($desc = $context->getState('task_description')) {
            $context->setState('task_description', $desc);
        }

        return parent::beforeLlmCall($inputMessages, $context);
    }

    /**
     * Load a single action tool for a given app name if trait loader isn't available
     */
    protected function loadToolsForIntegration(string $integrationType, ?int $userId): void
    {
        $toolMap = [
            'xero' => [
                'class' => \App\Tools\XeroActionTool::class,
                'tool_name' => 'xero_action',
            ],
            'xero_accounting_api' => [
                'class' => \App\Tools\XeroActionTool::class,
                'tool_name' => 'xero_action',
            ],
            'zoho_books' => [
                'class' => \App\Tools\ZohoBooksActionTool::class,
                'tool_name' => 'zoho_books_action',
            ],
            'quickbooks' => [
                'class' => \App\Tools\QuickBooksActionTool::class,
                'tool_name' => 'quickbooks_action',
            ],
            'sevdesk' => [
                'class' => \App\Tools\SevdeskActionTool::class,
                'tool_name' => 'sevdesk_action',
            ],
            'expensify' => [
                'class' => \App\Tools\ExpensifyActionTool::class,
                'tool_name' => 'expensify_action',
            ],
        ];

        $toolConfig = $toolMap[$integrationType] ?? null;

        if ($toolConfig && class_exists($toolConfig['class'])) {
            // Load the specific action tool for this integration
            $actionTool = new $toolConfig['class']($userId);
            $this->loadedTools[$toolConfig['tool_name']] = $actionTool;

            // Always load the list tools helper
            $listToolsTool = new \App\Tools\ListAvailableToolsTool($userId);
            $this->loadedTools['list_available_tools'] = $listToolsTool;

            Log::info('IntegrationIngestor: Loaded tools for integration', [
                'integration_type' => $integrationType,
                'tool_class' => $toolConfig['class'],
                'tool_name' => $toolConfig['tool_name'],
                'total_tools' => count($this->loadedTools),
            ]);
        } else {
            Log::warning('IntegrationIngestor: No tool found for integration', [
                'integration_type' => $integrationType,
                'available_integrations' => array_keys($toolMap),
            ]);
        }
    }

    public function afterLlmResponse(mixed $response, AgentContext $context, ?\Prism\Prism\Text\PendingRequest $request = null): mixed
    {
        Log::info('PipedreamExecutor completed', [
            'user_id' => $context->getState('user_id'),
            'response' => $response,
            'session_id' => $context->getSessionId(),
        ]);

        return parent::afterLlmResponse($response, $context, $request);
    }

    // before tool call hook
    public function beforeToolCall(string $toolName, array $params, AgentContext $context): array
    {
        Log::info('ExecutionAgent beforeToolCall', [
            'user_id' => $context->getState('user_id'),
            'tool_name' => $toolName,
            'params' => $params,
            'session_id' => $context->getSessionId(),
        ]);

        return parent::beforeToolCall($toolName, $params, $context);
    }

    // after tool call hook
    public function afterToolResult(string $toolName, string $result, AgentContext $context): string
    {

        Log::info('ExecutionAgent afterToolResult', [
            'user_id' => $context->getState('user_id'),
            'tool_name' => $toolName,
            'response' => $result,
            'session_id' => $context->getSessionId(),
        ]);

        return parent::afterToolResult($toolName, $result, $context);

    }
}
