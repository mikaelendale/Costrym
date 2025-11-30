<?php

namespace App\AiAgents;

use App\Tools\LarAgentKnowledgeBaseTool;
use App\Tools\LarAgentListFinancialCategoriesTool;
use App\Tools\LarAgentQueryFinancialRecordsTool;
use App\Tools\FirecrawlTool;
use LarAgent\Agent;

class ChatAgent extends Agent
{
    protected $model = 'gpt-4o-mini';

    protected $history = 'session'; // Use session to persist conversation history

    protected $provider = 'default';

    protected $tools = [
        LarAgentQueryFinancialRecordsTool::class,
        LarAgentListFinancialCategoriesTool::class,
        LarAgentKnowledgeBaseTool::class,
        FirecrawlTool::class,
    ];

    public function instructions()
    {
        return 'You are a helpful AI assistant with access to a comprehensive set of tools. You can:

- Query and analyze financial records and categories
- Search the web for information using Firecrawl
- Access the knowledge base for company-specific information

When a user asks a question or requests an action:
1. Understand their intent
2. Use the appropriate tool(s) to gather information or perform actions
3. Provide clear, helpful responses
4. If you need more information, ask the user for clarification
5. If an action fails, explain the error and suggest alternatives

Be conversational, helpful, and proactive in using your tools to provide the best assistance possible.';
    }

    public function prompt($message)
    {
        return $message;
    }
}
