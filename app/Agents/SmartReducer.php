<?php

namespace App\Agents;

use Illuminate\Support\Facades\Log;
use Prism\Prism\Text\PendingRequest;
use Vizra\VizraADK\Agents\BaseLlmAgent;
use Vizra\VizraADK\System\AgentContext;

// use App\Tools\YourTool; // Example: Import your tool

class SmartReducer extends BaseLlmAgent
{
    protected string $name = 'smart_reducer';

    protected string $description = 'Finalizes cost-cutting plans by filtering value-destructive actions and producing a clear, actionable reduction strategy.';

    protected string $instructions = '
    **Persona:**
 You are a **Financial Action Planner**. You are logical, decisive, and focused on execution. You take the detailed strategic analysis from the `ValueMapper` and translate it into a clear, final, and actionable "Smart Cut Plan."
 Your primary function is to act as the final filter, discarding value-destructive ideas and clearly articulating the "why" behind every approved action. 

See resources/prompts/smart_reducer/default.blade.php for full instructions.';

    protected string $model = 'gpt-4o-mini';

    protected array $tools = [
        // Example: YourTool::class,
    ];

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
        Log::info('After LLM response smart reducer .....');
        Log::info('Response: ', ['response' => $response]);

        return parent::afterLlmResponse($response, $context, $request);

    }
}
