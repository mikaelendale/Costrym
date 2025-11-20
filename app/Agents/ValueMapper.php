<?php

namespace App\Agents;

use Illuminate\Support\Facades\Log;
use Prism\Prism\Text\PendingRequest;
use Vizra\VizraADK\Agents\BaseLlmAgent;
use Vizra\VizraADK\System\AgentContext;

// use App\Tools\YourTool; // Example: Import your tool

class ValueMapper extends BaseLlmAgent
{
    protected string $name = 'value_mapper';

    protected string $description = 'Describe what this agent does.';

    protected string $instructions = <<<'INSTRUCTIONS'
ValueMapper Agent (Sub-Agent):

**Persona:**
You are a **Strategic Value Analyst**. Your expertise transcends simple accounting. You are a deep, critical thinker who models the second- and third-order effects of financial decisions. Your primary function is to answer the question: "What is the *true net value* of this proposed cost cut?" You must quantify not only the obvious savings but also the often-hidden costs associated with customer friction, employee disruption, and competitive disadvantage.

See resources/prompts/value_mapper/default.blade.php for full instructions.

***
INSTRUCTIONS;

    protected string $model = 'gpt-4o-mini';

    /*

    Optional hook methods to override:

    public function beforeLlmCall(array $inputMessages, AgentContext $context): array
    {
        // $context->setState('custom_data_for_llm', 'some_value');
        // $inputMessages[] = ['role' => 'system', 'content' => 'Additional system note for this call.'];
        return parent::beforeLlmCall($inputMessages, $context);
    }

    public function afterLlmResponse(mixed $response, AgentContext $context, ?PendingRequest $request = null): mixed {

         return parent::afterLlmResponse($response, $context, $request);

    }

    public function beforeToolCall(string $toolName, array $arguments, AgentContext $context): array {

        return parent::beforeToolCall($toolName, $arguments, $context);

    }

    public function afterToolResult(string $toolName, string $result, AgentContext $context): string {

        return parent::afterToolResult($toolName, $result, $context);

    } */

    public function afterLlmResponse(mixed $response, AgentContext $context, ?PendingRequest $request = null): mixed
    {
        Log::info('After LLM response value mapper .....');
        Log::info('Response: ', ['response' => $response]);

        return parent::afterLlmResponse($response, $context, $request);

    }
}
