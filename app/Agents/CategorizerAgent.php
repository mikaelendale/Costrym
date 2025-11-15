<?php

namespace App\Agents;

use App\Services\CleanUpResponse;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Text\PendingRequest;
use Prism\Prism\ValueObjects\Messages\SystemMessage;
use Vizra\VizraADK\Agents\BaseLlmAgent;
use Vizra\VizraADK\System\AgentContext;

// use App\Tools\YourTool; // Example: Import your tool

class CategorizerAgent extends BaseLlmAgent
{
    protected string $name = 'categorizer_agent';

    protected string $description = 'Maps and normalizes raw category names to a canonical master list.';

    protected string $instructions = 'Normalize and map incoming category/name variants to the master category list and return a clean JSON mapping. Keep it precise; see prompt file for full details.';

    // // protected ?string $provider = Provider::Groq->value;

    public function beforeLlmCall(array $inputMessages, AgentContext $context): array
    {
        $company_financials = $context->getState('company_financials');
        $company_profile = $context->getState('company_profile');

        $company_financials = json_encode($company_financials, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $company_profile = json_encode($company_profile, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $contextBlock = "Company Financials:\n{$company_financials}\n\nCompany Profile:\n{$company_profile}";
        $inputMessages[] = new SystemMessage($contextBlock);

        return parent::beforeLlmCall($inputMessages, $context);
    }

    public function afterLlmResponse(mixed $response, AgentContext $context, ?PendingRequest $request = null): mixed
    {
        Log::info('After CategorizerAgent LLM response ...');
        $parsedResponse = CleanUpResponse::extractJsonPayload($response);
        Log::info('Parsed CategorizerAgent response payload.', ['response' => $parsedResponse]);

        $context->setState('categorized_data', $parsedResponse);

        Log::info('Finished setting categorized_data state.');

        return parent::afterLlmResponse($response, $context, $request);

    }

    public function beforeToolCall(string $toolName, array $arguments, AgentContext $context): array
    {

        return parent::beforeToolCall($toolName, $arguments, $context);

    }

    public function afterToolResult(string $toolName, string $result, AgentContext $context): string
    {

        return parent::afterToolResult($toolName, $result, $context);

    }
}
