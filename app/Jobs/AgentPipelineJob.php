<?php

namespace App\Jobs;

use App\Models\Automation;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Vizra\VizraADK\System\AgentContext;

class AgentPipelineJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [60, 120, 300];

    public int $timeout = 600; // 10 minutes for full pipeline

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $userId,
        public string $pipelineName,
        public array $initialInput = [],
        public ?array $additionalContext = null
    ) {
        $this->onQueue('agent_pipeline');
    }

    /**
     * Execute the job - Run agent pipeline as a bus chain
     */
    public function handle(): void
    {
        Log::info('AgentPipelineJob started', [
            'user_id' => $this->userId,
            'pipeline' => $this->pipelineName,
        ]);

        try {
            $user = User::findOrFail($this->userId);
            $pipelineConfig = config("agents.pipelines.{$this->pipelineName}");

            if (! $pipelineConfig || ! ($pipelineConfig['enabled'] ?? false)) {
                throw new \Exception("Pipeline '{$this->pipelineName}' not found or not enabled");
            }

            $stages = $pipelineConfig['stages'] ?? [];
            if (empty($stages)) {
                throw new \Exception("Pipeline '{$this->pipelineName}' has no stages defined");
            }

            Log::info('Pipeline configuration loaded', [
                'pipeline' => $this->pipelineName,
                'stages' => count($stages),
            ]);

            // Execute pipeline stages sequentially
            $pipelineResults = $this->executePipeline($user, $stages, $pipelineConfig);

            // Save final pipeline result as Automation MD
            $this->savePipelineResult($user, $pipelineConfig, $pipelineResults);

            Log::info('AgentPipelineJob completed successfully', [
                'user_id' => $this->userId,
                'pipeline' => $this->pipelineName,
                'stages_executed' => count($pipelineResults),
            ]);

        } catch (\Exception $e) {
            Log::error('AgentPipelineJob failed', [
                'user_id' => $this->userId,
                'pipeline' => $this->pipelineName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Execute the agent pipeline sequentially
     */
    protected function executePipeline(User $user, array $stages, array $pipelineConfig): array
    {
        $results = [];
        $sharedContext = new AgentContext("pipeline_{$this->pipelineName}_{$this->userId}_".time());

        // Set initial context
        $sharedContext->setState('user_id', $this->userId);
        $sharedContext->setState('pipeline_name', $this->pipelineName);
        $sharedContext->setState('initial_input', $this->initialInput);

        if ($this->additionalContext) {
            foreach ($this->additionalContext as $key => $value) {
                $sharedContext->setState($key, $value);
            }
        }

        $passFullContext = config('agents.pipeline_execution.pass_full_context', true);
        $failOnError = config('agents.pipeline_execution.fail_on_error', false);
        $logIntermediate = config('agents.pipeline_execution.log_intermediate_results', true);
        $saveToAutomations = config('agents.pipeline_execution.save_to_automations', true);

        // Execute each stage
        foreach ($stages as $index => $stage) {
            $stageNum = $index + 1;
            $agentKey = $stage['agent'];
            $outputKey = $stage['output_key'] ?? "stage_{$stageNum}_output";

            $totalStages = count($stages);
            Log::info("Pipeline Stage {$stageNum}/{$totalStages}: {$agentKey}", [
                'description' => $stage['description'] ?? '',
            ]);

            try {
                // Get agent configuration
                $agentConfig = config("agents.available_agents.{$agentKey}");
                if (! $agentConfig) {
                    throw new \Exception("Agent '{$agentKey}' not found in configuration");
                }

                if (! ($agentConfig['enabled'] ?? false)) {
                    Log::warning("Agent '{$agentKey}' is disabled, skipping stage", [
                        'stage' => $stageNum,
                    ]);

                    continue;
                }

                $agentClass = $agentConfig['class'];
                if (! class_exists($agentClass)) {
                    throw new \Exception("Agent class '{$agentClass}' not found");
                }

                // Instantiate agent
                $agent = new $agentClass;

                // Build input from previous stages
                $input = $this->buildStageInput($index, $results, $passFullContext);

                // Execute agent
                $sessionId = "pipeline_{$this->pipelineName}_stage{$stageNum}_{$this->userId}_".time();

                $response = $agent->run(input: $input)
                    ->forUser($user)
                    ->withSession($sessionId)
                    ->withContext($sharedContext->getAllState())
                    ->go();

                // Store result
                $results[$stageNum] = [
                    'stage' => $stageNum,
                    'agent' => $agentKey,
                    'description' => $stage['description'] ?? '',
                    'output_key' => $outputKey,
                    'result' => $response,
                    'timestamp' => now()->toIso8601String(),
                ];

                // Update shared context with this stage's output
                $sharedContext->setState($outputKey, $response);

                if ($logIntermediate) {
                    Log::info("Stage {$stageNum} completed", [
                        'agent' => $agentKey,
                        'output_length' => strlen($response),
                    ]);
                }

                // Optionally save each stage as automation
                if ($saveToAutomations) {
                    $this->saveStageResult($user, $stageNum, $agentKey, $stage, $response);
                }

            } catch (\Exception $e) {
                Log::error("Pipeline stage {$stageNum} failed", [
                    'agent' => $agentKey,
                    'error' => $e->getMessage(),
                ]);

                $results[$stageNum] = [
                    'stage' => $stageNum,
                    'agent' => $agentKey,
                    'description' => $stage['description'] ?? '',
                    'output_key' => $outputKey,
                    'result' => null,
                    'error' => $e->getMessage(),
                    'timestamp' => now()->toIso8601String(),
                ];

                if ($failOnError) {
                    throw $e;
                }
            }
        }

        return $results;
    }

    /**
     * Build input for a stage from previous results
     */
    protected function buildStageInput(int $currentIndex, array $previousResults, bool $passFullContext): string
    {
        if ($currentIndex === 0) {
            // First stage gets initial input
            return is_string($this->initialInput)
                ? $this->initialInput
                : json_encode($this->initialInput, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if ($passFullContext) {
            // Pass all previous results
            $contextSummary = "# Pipeline Context\n\n";
            foreach ($previousResults as $result) {
                $contextSummary .= "## Stage {$result['stage']}: {$result['agent']}\n";
                $contextSummary .= "**Description:** {$result['description']}\n\n";
                if (isset($result['error'])) {
                    $contextSummary .= "**Error:** {$result['error']}\n\n";
                } else {
                    $contextSummary .= "**Result:**\n```\n{$result['result']}\n```\n\n";
                }
            }

            return $contextSummary;
        } else {
            // Pass only previous stage result
            $previousStage = $previousResults[$currentIndex] ?? null;
            if ($previousStage && isset($previousStage['result'])) {
                return $previousStage['result'];
            }

            return '';
        }
    }

    /**
     * Save individual stage result as Automation
     */
    protected function saveStageResult(User $user, int $stageNum, string $agentKey, array $stage, string $result): void
    {
        try {
            $mdContent = <<<MD
# 🔄 Pipeline Stage {$stageNum}: {$agentKey}

**Pipeline:** {$this->pipelineName}
**Date:** {$this->getFormattedDate()}
**Description:** {$stage['description']}

---

## 📊 Stage Result

```
{$result}
```

---

*Generated by AgentPipelineJob on {$this->getFormattedDate()}*
MD;

            Automation::create([
                'user_id' => $this->userId,
                'type' => 'pipeline_stage',
                'name' => "Stage {$stageNum}: {$agentKey}",
                'description' => $stage['description'] ?? '',
                'markdown_content' => $mdContent,
                'metadata' => [
                    'pipeline' => $this->pipelineName,
                    'stage' => $stageNum,
                    'agent' => $agentKey,
                    'output_key' => $stage['output_key'] ?? "stage_{$stageNum}_output",
                ],
                'status' => 'active',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to save stage result as Automation', [
                'error' => $e->getMessage(),
                'stage' => $stageNum,
                'agent' => $agentKey,
            ]);
        }
    }

    /**
     * Save final pipeline result as Automation
     */
    protected function savePipelineResult(User $user, array $pipelineConfig, array $results): void
    {
        try {
            $totalStages = count($results);
            $successfulStages = count(array_filter($results, fn ($r) => ! isset($r['error'])));
            $failedStages = $totalStages - $successfulStages;

            $stagesList = collect($results)->map(function ($result) {
                $status = isset($result['error']) ? '❌ FAILED' : '✅ SUCCESS';

                return "- **Stage {$result['stage']}: {$result['agent']}** - {$status}\n  Description: {$result['description']}";
            })->join("\n");

            $mdContent = <<<MD
# 🎯 Pipeline Execution Report: {$pipelineConfig['name']}

**Date:** {$this->getFormattedDate()}
**User:** {$user->name}
**Pipeline:** {$this->pipelineName}

---

## 📊 Summary

- **Total Stages:** {$totalStages}
- **Successful:** {$successfulStages} ✅
- **Failed:** {$failedStages} ❌
- **Status:** {$this->getPipelineStatus($successfulStages, $totalStages)}

---

## 🔄 Pipeline Stages

{$stagesList}

---

## 📝 Pipeline Description

{$pipelineConfig['description']}

---

## 📈 Detailed Results

MD;

            foreach ($results as $result) {
                $mdContent .= "\n### Stage {$result['stage']}: {$result['agent']}\n\n";
                $mdContent .= "**Description:** {$result['description']}\n\n";

                if (isset($result['error'])) {
                    $mdContent .= "**Status:** ❌ FAILED\n\n";
                    $mdContent .= "**Error:** {$result['error']}\n\n";
                } else {
                    $mdContent .= "**Status:** ✅ SUCCESS\n\n";
                    $mdContent .= "**Result:**\n```\n".substr($result['result'], 0, 1000)."...\n```\n\n";
                }
            }

            $mdContent .= "\n---\n\n";
            $mdContent .= "*Generated by AgentPipelineJob on {$this->getFormattedDate()}*\n";

            Automation::create([
                'user_id' => $this->userId,
                'type' => 'pipeline_complete',
                'name' => "Pipeline Complete: {$pipelineConfig['name']}",
                'description' => $pipelineConfig['description'],
                'markdown_content' => $mdContent,
                'metadata' => [
                    'pipeline' => $this->pipelineName,
                    'total_stages' => $totalStages,
                    'successful_stages' => $successfulStages,
                    'failed_stages' => $failedStages,
                    'stage_list' => array_map(fn ($r) => $r['agent'], $results),
                ],
                'status' => 'active',
            ]);

            Log::info('Pipeline result saved as Automation', [
                'user_id' => $this->userId,
                'pipeline' => $this->pipelineName,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to save pipeline result as Automation', [
                'error' => $e->getMessage(),
                'user_id' => $this->userId,
            ]);
        }
    }

    /**
     * Get pipeline status
     */
    protected function getPipelineStatus(int $successful, int $total): string
    {
        if ($successful === $total) {
            return '✅ All stages completed successfully';
        } elseif ($successful === 0) {
            return '❌ All stages failed';
        } else {
            return "⚠️ {$successful}/{$total} stages completed";
        }
    }

    /**
     * Get formatted date
     */
    protected function getFormattedDate(): string
    {
        return now()->format('Y-m-d H:i:s');
    }

    /**
     * Handle failed job
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('AgentPipelineJob permanently failed', [
            'user_id' => $this->userId,
            'pipeline' => $this->pipelineName,
            'error' => $exception->getMessage(),
        ]);
    }
}
