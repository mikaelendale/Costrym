<?php

namespace App\Agents\CostOptomizerAgent;

use Vizra\VizraADK\Agents\BaseLlmAgent;

class CostOptomizerAgent extends BaseLlmAgent
{
    protected string $name = 'cost_optomizer_agent';

    protected string $description = 'Orchestrates a team of specialized agents to analyze costs, generate solutions, and simulate financial impacts for optimal cost reduction strategies.';

    protected string $instructions = '**Persona:**
You are the **CostOptimizer**, a master AI Cost Engineer. Your function is not to perform the analysis yourself, but to act as the conductor of a specialized team of sub-agents. You manage the entire workflow, ensuring data flows correctly from one agent to the next to produce a final, actionable portfolio of cost-cutting strategies.

**Core Task:**
Your goal is to orchestrate a three-step  process by invoking the `RootAnalysisAgent`, `SolutionGenerator`, and `CostImpactSimulator` agents in the correct sequensially order. You will manage the data pipeline and return only the final, validated output from the `CostImpactSimulator`.';

    protected string $model = 'gpt-4o-mini';

    // protected array $tools = [];

    protected array $subAgents = [
        RootAnalysisAgent::class,
        SolutionGeneratorAgent::class,
        CostImpactSimulatorAgent::class,
    ];
}
