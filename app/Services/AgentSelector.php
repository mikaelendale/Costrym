<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class AgentSelector
{
    /**
     * Available agents and their capabilities (loaded from config)
     */
    protected array $agents;

    /**
     * Constructor - load agents from config
     */
    public function __construct()
    {
        $this->agents = $this->loadAgentsFromConfig();
    }

    /**
     * Load available agents from config
     */
    protected function loadAgentsFromConfig(): array
    {
        $configAgents = config('agents.available_agents', []);
        $agents = [];

        foreach ($configAgents as $key => $agentConfig) {
            // Only include enabled agents
            if ($agentConfig['enabled'] ?? true) {
                $agents[$key] = [
                    'class' => $agentConfig['class'],
                    'capabilities' => $agentConfig['capabilities'] ?? [],
                    'description' => $agentConfig['description'] ?? '',
                ];
            }
        }

        return $agents;
    }

    /**
     * Select the best agent for a given task
     */
    public function selectAgent(array $taskData): array
    {
        $taskName = $taskData['name'] ?? '';
        $taskDescription = $taskData['description'] ?? '';
        $taskType = $taskData['task_type'] ?? 'one_time';

        // Combine task info for matching
        $taskContent = strtolower($taskName.' '.$taskDescription);

        Log::info('AgentSelector: Analyzing task', [
            'task_name' => $taskName,
            'task_type' => $taskType,
        ]);

        // Score each agent based on capability match
        $scores = [];
        foreach ($this->agents as $agentName => $agentInfo) {
            $score = 0;

            // Check if any capabilities match the task content
            foreach ($agentInfo['capabilities'] as $capability) {
                if (str_contains($taskContent, strtolower($capability))) {
                    $score += 10;
                }
            }

            $scores[$agentName] = $score;
        }

        // Sort by score (highest first)
        arsort($scores);

        // Get the best agent
        $bestAgentName = array_key_first($scores);
        $bestScore = $scores[$bestAgentName];

        // If no clear match, use a default based on task type
        if ($bestScore === 0) {
            $bestAgentName = $this->getDefaultAgent($taskData);
            Log::info('AgentSelector: No match found, using default', [
                'default_agent' => $bestAgentName,
            ]);
        }

        $selectedAgent = $this->agents[$bestAgentName];

        Log::info('AgentSelector: Agent selected', [
            'agent_name' => $bestAgentName,
            'score' => $bestScore,
            'class' => $selectedAgent['class'],
        ]);

        return [
            'agent_name' => $bestAgentName,
            'agent_class' => $selectedAgent['class'],
            'score' => $bestScore,
            'reasoning' => $this->generateReasoning($bestAgentName, $taskContent, $bestScore),
        ];
    }

    /**
     * Get default agent based on task characteristics
     */
    protected function getDefaultAgent(array $taskData): string
    {
        $taskName = strtolower($taskData['name'] ?? '');
        $taskDescription = strtolower($taskData['description'] ?? '');
        $combined = $taskName.' '.$taskDescription;

        // Pattern matching for common task types
        if (str_contains($combined, 'report') || str_contains($combined, 'document')) {
            return 'notion_agent';
        }

        if (str_contains($combined, 'expense') || str_contains($combined, 'cost') || str_contains($combined, 'spend')) {
            return 'cost_optimizer_agent';
        }

        if (str_contains($combined, 'data') || str_contains($combined, 'fetch') || str_contains($combined, 'sync')) {
            return 'integration_ingestor';
        }

        if (str_contains($combined, 'categorize') || str_contains($combined, 'classify')) {
            return 'categorizer_agent';
        }

        // Default fallback
        return 'cost_optimizer_agent';
    }

    /**
     * Generate reasoning for agent selection
     */
    protected function generateReasoning(string $agentName, string $taskContent, int $score): string
    {
        $agent = $this->agents[$agentName];

        if ($score > 0) {
            return "Selected {$agentName} (score: {$score}) - {$agent['description']}";
        }

        return "Using {$agentName} as default - {$agent['description']}";
    }

    /**
     * Get all available agents
     */
    public function getAvailableAgents(): array
    {
        return $this->agents;
    }

    /**
     * Check if an agent exists
     */
    public function agentExists(string $agentName): bool
    {
        return isset($this->agents[$agentName]);
    }

    /**
     * Get agent class by name
     */
    public function getAgentClass(string $agentName): ?string
    {
        return $this->agents[$agentName]['class'] ?? null;
    }
}
