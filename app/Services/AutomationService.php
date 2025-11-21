<?php

namespace App\Services;

use App\Agents\ApprovalAgent;
use App\Agents\AutomationPlanningAgent;
use App\Repositories\AutomationRepository;
use App\Repositories\CostOptimizationRepository;
use Illuminate\Support\Facades\Log;

class AutomationService
{
    public function __construct(
        private CostOptimizationRepository $costOptimizationRepository,
        private AutomationRepository $automationRepository,
    ) {
        //
    }

    /**
     * Run automation planning then approval layer.
     * Uses the Cost Value Alignment output as the input for automation.
     */
    public function run(int $userId): array
    {
        // Fetch prior aligned portfolio (from CostOptimizationService)
        $aligned = $this->costOptimizationRepository->getCostValueAlignment($userId);
        $alignedJson = json_encode($aligned, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // Plan automations
        Log::info('AutomationService: dispatching AutomationPlanningAgent');
        $automationRaw = AutomationPlanningAgent::run($alignedJson)->go();

        Log::info('AutomationService: AutomationPlanningAgent raw response', ['automationRaw' => $automationRaw]);
        $automationParsed = [];
        try {
            $automationParsed = CleanUpResponse::extractJsonPayload($automationRaw);
        } catch (\Throwable $e) {
            Log::warning('AutomationService: failed to parse automations; storing empty array', ['error' => $e->getMessage()]);
        }

        $persistedAutomations = $this->automationRepository->updateAutomations($automationParsed['automation_planning_agent_response'] ?? [], $userId);
        Log::info('AutomationService: automations persisted', [
            'items' => is_array($persistedAutomations) ? count($persistedAutomations) : 0,
        ]);

        // Approval layer (notify / approval payload)
        // Use the raw output if stringy; else feed parsed
        $approvalInput = is_string($automationRaw)
            ? $automationRaw
            : json_encode($automationParsed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        Log::info('AutomationService: dispatching ApprovalAgent');
        $approvalRaw = ApprovalAgent::run(input: $approvalInput)->go();
        Log::info('AutomationService: ApprovalAgent raw response', ['approvalRaw' => $approvalRaw]);

        $approvalParsed = [];
        try {
            $approvalParsed = CleanUpResponse::extractJsonPayload($approvalRaw);
            $data = $approvalParsed['approval_agent_response']['approval_requests'] ?? [];
        } catch (\Throwable $e) {
            Log::warning('AutomationService: failed to parse approval layer; storing empty array', ['error' => $e->getMessage()]);
        }
        $persistedApproval = $this->automationRepository->updateApprovalLayer($data, $userId);
        Log::info('AutomationService: approval layer persisted', [
            'items' => is_array($persistedApproval) ? count($persistedApproval) : 0,
        ]);

        return [
            'automations' => $persistedAutomations,
            'approvalLayer' => $persistedApproval,
        ];
    }

    /**
     * Process a chunk of aligned data for a single category using the AutomationPlanningAgent
     * and append the resulting execution plans to the per-category automations store.
     *
     * @param  array<int,mixed>  $alignedChunk
     */
    public function processPlanningChunk(string $category, array $alignedChunk, int $userId, int $chunkIndex = 0, int $totalChunks = 1): void
    {
        $payload = json_encode($alignedChunk, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $prompt = $payload; // planner prompt expects an array of tasks; pass the slice directly
        Log::info('AutomationService: calling AutomationPlanningAgent for category chunk', [
            'category' => $category,
            'chunk_index' => $chunkIndex,
            'total_chunks' => $totalChunks,
            'payload_length' => strlen($prompt),
        ]);

        $raw = AutomationPlanningAgent::run($prompt)->go();
        Log::info('AutomationService: planner raw chunk response', [
            'category' => $category,
            'response_length' => is_string($raw) ? strlen($raw) : 0,
        ]);

        $parsed = [];
        $plans = null;
        try {
            $parsed = CleanUpResponse::extractJsonPayload($raw);
            // expected shape: { automation_planning_agent_response: { execution_plans: [...] } }
            $plans = $parsed['automation_planning_agent_response']['execution_plans'] ?? $parsed;
        } catch (\Throwable $e) {
            Log::warning('AutomationService: planner chunk parse failed; storing raw result', ['error' => $e->getMessage()]);
            $plans = $raw;
        }

        // Append the plans chunk under the category
        $updated = $this->automationRepository->updateAutomationsByCategory($category, $plans, $userId);
        Log::info('AutomationService: appended planner results for category', [
            'category' => $category,
            'cumulative_categories' => is_array($updated) ? count(array_keys($updated)) : 0,
        ]);
    }

    /**
     * Process a chunk of execution plans for a single category using the ApprovalAgent
     * and append the resulting approval requests to the per-category approval layer store.
     *
     * @param  array<int,mixed>  $plansChunk  list of execution plan objects
     */
    public function processApprovalChunk(string $category, array $plansChunk, int $userId, int $chunkIndex = 0, int $totalChunks = 1): void
    {
        // Build the input shape expected by ApprovalAgent prompt
        $input = [
            'automation_planning_agent_response' => [
                'execution_plans' => $plansChunk,
            ],
        ];
        $prompt = json_encode($input, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        Log::info('AutomationService: calling ApprovalAgent for category chunk', [
            'category' => $category,
            'chunk_index' => $chunkIndex,
            'total_chunks' => $totalChunks,
            'payload_length' => strlen($prompt),
        ]);

        $raw = ApprovalAgent::run($prompt)->go();
        Log::info('AutomationService: approval raw chunk response', [
            'category' => $category,
            'response_length' => is_string($raw) ? strlen($raw) : 0,
        ]);

        $parsed = [];
        $approvalRequests = null;
        try {
            $parsed = CleanUpResponse::extractJsonPayload($raw);
            // expected shape: { approval_agent_response: { approval_requests: [...] } }
            $approvalRequests = $parsed['approval_agent_response']['approval_requests'] ?? $parsed;
        } catch (\Throwable $e) {
            Log::warning('AutomationService: approval chunk parse failed; storing raw result', ['error' => $e->getMessage()]);
            $approvalRequests = $raw;
        }

        $updated = $this->automationRepository->updateApprovalLayerByCategory($category, $approvalRequests, $userId);
        Log::info('AutomationService: appended approval results for category', [
            'category' => $category,
            'cumulative_categories' => is_array($updated) ? count(array_keys($updated)) : 0,
        ]);
    }
}
