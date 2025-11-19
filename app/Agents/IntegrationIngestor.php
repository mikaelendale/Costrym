<?php

namespace App\Agents;

use App\Services\PipedreamToolLoader;
use App\Traits\LoadsPipedreamTools;
use Prism\Prism\ValueObjects\Messages\SystemMessage;
use Vizra\VizraADK\Agents\BaseLlmAgent;
use Vizra\VizraADK\System\AgentContext;

/**
 * Integration Ingestor Agent
 *
 * Specialized agent for fetching data and managing triggers from required integrations.
 * Focuses on essential integrations (accounting, payment systems) for data ingestion and trigger updates.
 */
class IntegrationIngestor extends BaseLlmAgent
{
    use LoadsPipedreamTools;

    protected string $name = 'integration_ingestor';

    protected string $description = 'An AI agent specialized in fetching data and managing triggers from required integrations (accounting systems, payment processors, etc.) through Pipedream. Focuses on essential integrations for data ingestion and automated trigger updates.';

    protected string $instructions = <<<'INSTRUCTIONS'
You are a specialized data ingestion and integration management assistant. Your primary role is to help users fetch data from their connected integrations and manage automated triggers.

**Your Core Responsibilities:**

1. **Data Fetching:**
   - Fetch financial data from accounting systems (QuickBooks, Xero, Zoho, FreshBooks, Wave, Sage Intacct, NetSuite, Odoo)
   - Retrieve transaction data from payment processors (Stripe, PayPal, Square, Paddle, Adyen, Checkout.com)
   - Get customer, invoice, payment, and other business data
   - Always prioritize required integrations when multiple options exist
   - Use search actions to find specific records before retrieving details
   - When searching, use appropriate filters (dates, status, customer names, etc.)

2. **Data Organization:**
   - Present fetched data in a clear, structured format
   - Summarize key metrics and insights from the data
   - Group related data logically (e.g., invoices by customer, payments by date)
   - Highlight important information (overdue invoices, recent transactions, etc.)

3. **Trigger Management:**
   - Explain available triggers for each integration
   - Help users understand what events can be monitored
   - Guide users on setting up automated workflows
   - Note: Triggers are event sources that can be configured for real-time data synchronization

4. **Best Practices:**
   - Always check what integrations are connected before attempting to fetch data
   - Use search/list actions first to find relevant records, then retrieve specific details
   - For date ranges, use appropriate filters (e.g., last 30 days, this month, this year)
   - When fetching invoices, payments, or transactions, consider pagination for large datasets
   - If a user asks for "all" data, clarify the scope (time period, record type) to avoid overwhelming responses

5. **Error Handling:**
   - If an action fails, read the error message carefully and explain it clearly to the user
   - Do NOT retry failed actions with the same parameters
   - Suggest alternative approaches if a specific action fails
   - If an integration is not connected, guide the user to connect it first

6. **Communication:**
   - Provide clear summaries of what data was retrieved
   - List which integrations were accessed
   - Explain any limitations or missing data
   - Ask clarifying questions if the request is ambiguous
   - Use tables or structured formats when presenting multiple records

**Available Integrations Context:**
You have access to tools from connected required integrations. Check the context state for:
- `available_integrations`: List of connected app names
- `integration_tools_summary`: Summary of available tools per integration
- `required_integrations`: All required integrations from config
- `connected_required_integrations`: Which required integrations are actually connected

**Example Workflows:**
- "Get all invoices from Xero for last month" → Use Xero search/list invoices with date filter, then retrieve details
- "Show me recent payments from Stripe" → Use Stripe list payments with date range
- "What customers do I have in QuickBooks?" → Use QuickBooks search customers
- "Get my balance sheet from QuickBooks" → Use QuickBooks get balance sheet report

Remember: Always be helpful, clear, and provide actionable insights from the data you retrieve.
INSTRUCTIONS;

    protected string $model = 'gpt-4.1';

    protected array $tools = [];

    public function beforeLlmCall(array $inputMessages, AgentContext $context): array
    {
        // Get user ID from context
        $userId = $context->getState('user_id');

        // Initialize ListAvailableToolsTool with user ID (needs userId in constructor)
        if ($userId) {
            $listToolsTool = new \App\Tools\ListAvailableToolsTool($userId);
            $this->loadedTools['list_available_tools'] = $listToolsTool;
        }

        // Load Pipedream tools for required integrations only (same pattern as NotionAgent)
        $this->loadPipedreamTools($context, true);

        // Add context about available integrations and required ones
        if ($userId) {
            $toolLoader = app(PipedreamToolLoader::class);
            $pipedreamService = app(\App\Services\PipedreamService::class);

            $connectedApps = $toolLoader->getConnectedAppNames($userId);
            $summary = $toolLoader->getToolsSummary($userId, true);

            // Get required integrations from config
            $allIntegrations = $pipedreamService->getAvailableIntegrations();
            $requiredIntegrations = array_filter($allIntegrations, function ($config) {
                return ($config['required'] ?? false) === true;
            });

            // Check which required integrations are connected
            $connectedRequired = array_filter($requiredIntegrations, function ($config, $appId) use ($connectedApps) {
                $appName = $config['app_id'] ?? $appId;

                return in_array($appName, $connectedApps);
            }, ARRAY_FILTER_USE_BOTH);

            // Build context message for the agent
            $contextMessage = "**Available Integrations Context:**\n\n";
            $contextMessage .= 'Connected Integrations: '.implode(', ', $connectedApps)."\n\n";

            if (! empty($summary)) {
                $contextMessage .= "Available Tools by Integration:\n";
                foreach ($summary as $integration) {
                    $contextMessage .= "- {$integration['app_name']}: {$integration['tool_count']} tools available\n";
                }
                $contextMessage .= "\n";
            }

            $contextMessage .= 'Required Integrations: '.implode(', ', array_keys($requiredIntegrations))."\n";
            $contextMessage .= 'Connected Required Integrations: '.implode(', ', array_keys($connectedRequired))."\n";

            // Add context to state for programmatic access
            $context->setState('available_integrations', $connectedApps);
            $context->setState('integration_tools_summary', $summary);
            $context->setState('required_integrations', array_keys($requiredIntegrations));
            $context->setState('connected_required_integrations', array_keys($connectedRequired));

            // Add context message as a SystemMessage (same pattern as other agents)
            if (! empty($connectedApps)) {
                $inputMessages[] = new SystemMessage($contextMessage);
            }
        }

        return parent::beforeLlmCall($inputMessages, $context);
    }
}
