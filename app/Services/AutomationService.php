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
}
