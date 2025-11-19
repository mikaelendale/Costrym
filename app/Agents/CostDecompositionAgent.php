<?php

namespace App\Agents;

use App\Tools\GetCompanyContext;
use App\Tools\GetTotalCostByCategory;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Text\PendingRequest;
use Vizra\VizraADK\Agents\BaseLlmAgent;
use Vizra\VizraADK\System\AgentContext;

// use App\Tools\YourTool; // Example: Import your tool

class CostDecompositionAgent extends BaseLlmAgent
{
    protected string $name = 'cost_decomposition_agent';

    protected string $description = 'Breaks products into their direct cost components and estimates required quantities.';

    protected string $instructions = 'Read company_context, products, direct_cost lists; output JSON product_decompositions with quantity_required_per_product filled for each associated direct cost.';

    // protected ?string $provider = Provider::Groq->value;

    protected string $model = '';

    protected array $tools = [
        GetCompanyContext::class,
        GetTotalCostByCategory::class,
        // Example: YourTool::class,
    ];

    public function beforeLlmCall(array $inputMessages, AgentContext $context): array
    {

        // Log::info('CostDecompositionAgent.beforeLlmCall:start', [
        //     'input_msg' => $inputMessages,
        // ]);

        $result = parent::beforeLlmCall($inputMessages, $context);

        return $result;
    }

    public function afterLlmResponse(mixed $response, AgentContext $context, ?PendingRequest $request = null): mixed
    {

        $processed = parent::afterLlmResponse($response, $context, $request);

        return $processed;

    }

    public function beforeToolCall(string $toolName, array $arguments, AgentContext $context): array
    {

        $result = parent::beforeToolCall($toolName, $arguments, $context);

        return $result;

    }

    public function afterToolResult(string $toolName, string $result, AgentContext $context): string
    {

        $processed = parent::afterToolResult($toolName, $result, $context);

        return $processed;

    }
}
