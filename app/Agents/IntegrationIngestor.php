<?php

namespace App\Agents;

use Illuminate\Support\Facades\Log;
use Vizra\VizraADK\Agents\BaseLlmAgent;
use Vizra\VizraADK\System\AgentContext;

/**
 * Integration Ingestor Agent
 *
 * Generic agent for fetching financial data from various accounting integrations.
 * Supports: Xero, QuickBooks, Zoho Books, Sevdesk, Expensify
 * Uses dynamic prompting based on integration type and context.
 */
class IntegrationIngestor extends BaseLlmAgent
{
    protected string $name = 'integration_ingestor';

    protected string $description = 'An AI agent specialized in fetching and normalizing financial data from accounting systems through Pipedream. Supports multiple integrations including Xero, QuickBooks, Zoho Books, Sevdesk, and Expensify.';

    /**
     * Instructions are passed dynamically from the calling job.
     * The job renders the Blade template with all necessary context before calling the agent.
     * This property is a fallback in case no prompt is provided.
     */
    protected string $instructions = 'You are a financial data ingestion assistant. Follow the instructions provided in the user message.';

    protected string $model = 'gpt-4o-mini';

    // Load tools directly - they'll get userId from context
    // Only integration-specific action tools - no KnowledgeBase (that's for other agents)
    protected array $tools = [
        \App\Tools\XeroActionTool::class,
        \App\Tools\ListAvailableToolsTool::class,
    ];

    // Disable conversation history for data ingestion tasks
    protected bool $includeConversationHistory = false;

    public function beforeLlmCall(array $inputMessages, AgentContext $context): array
    {
        // Get context data
        $userId = $context->getState('user_id');
        $integrationType = $context->getState('integration_type');
        $taskType = $context->getState('task_type');

        // Debug logging
        Log::info('IntegrationIngestor beforeLlmCall: Context state', [
            'user_id' => $userId,
            'integration_type' => $integrationType,
            'task_type' => $taskType,
            'session_id' => $context->getSessionId(),
        ]);

        // Initialize tools based on integration type
        // Note: Since we don't have context state, we'll try to infer from user message or use default tools
        // The job should pre-render the prompt with all necessary instructions
        if ($userId && $integrationType) {
            $this->loadToolsForIntegration($integrationType, $userId);
        } else {
            // No context - can't load integration-specific tools
            // This is expected when the prompt is pre-rendered from the job
            Log::info('IntegrationIngestor: No context state, using default behavior', [
                'user_id' => $userId,
                'session_id' => $context->getSessionId(),
            ]);
        }

        $toolCount = count($this->loadedTools);
        Log::info('IntegrationIngestor loaded tools', [
            'user_id' => $userId,
            'integration_type' => $integrationType,
            'tool_count' => $toolCount,
            'using_dynamic_prompt' => ! empty($this->instructions),
        ]);

        return parent::beforeLlmCall($inputMessages, $context);
    }

    /**
     * Build dynamic instructions from Blade template using context variables
     */
    protected function buildDynamicInstructions(AgentContext $context): string
    {
        try {
            // Get all state variables for the prompt
            $promptData = [
                'integration_type' => $context->getState('integration_type'),
                'integration_name' => $context->getState('integration_name'),
                'task_type' => $context->getState('task_type'),
                'is_initial_sync' => $context->getState('is_initial_sync'),
                'date_range' => $context->getState('date_range'),
                'start_date' => $context->getState('start_date'),
                'end_date' => $context->getState('end_date'),
                'user_id' => $context->getState('user_id'),
                'ingestion_log_id' => $context->getState('ingestion_log_id'),
            ];

            // Load and render the Blade template
            $promptPath = resource_path('prompts/integration_ingestor/default.blade.php');

            if (! file_exists($promptPath)) {
                Log::warning('IntegrationIngestor: Dynamic prompt file not found', [
                    'path' => $promptPath,
                ]);

                return '';
            }

            // Use Blade to render the prompt
            return view()->file($promptPath, $promptData)->render();

        } catch (\Exception $e) {
            Log::error('IntegrationIngestor: Failed to build dynamic instructions', [
                'error' => $e->getMessage(),
            ]);

            return '';
        }
    }

    /**
     * Load appropriate tools based on integration type
     */
    protected function loadToolsForIntegration(string $integrationType, int $userId): void
    {
        // Map integration types to their action tool classes
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

    /**
     * Get the integration-specific name for display
     */
    public function getIntegrationName(string $integrationType): string
    {
        $names = [
            'xero' => 'Xero',
            'xero_accounting_api' => 'Xero',
            'zoho_books' => 'Zoho Books',
            'quickbooks' => 'QuickBooks Online',
            'sevdesk' => 'Sevdesk',
            'expensify' => 'Expensify',
        ];

        return $names[$integrationType] ?? ucfirst($integrationType);
    }
}
