## 1. Overview

- **Name:** `CostOptomizerAgent`
- **Location:** `app/Agents/CostOptomizerAgent/CostOptomizerAgent.php`
- **Purpose:** Orchestrates a team of specialized agents to analyze costs, generate solutions, and simulate financial impacts for optimal cost reduction strategies.
- **Persona:** Master AI Cost Engineer. Acts as a conductor, not an analyst—manages workflow and data flow between sub-agents to produce actionable cost-cutting strategies.

## 2. Behavior Summary

The agent orchestrates a three-step pipeline:

1. Invokes `RootAnalysisAgent` with all input data to diagnose high-priority cost anomalies.
2. Passes the root cause analysis to `SolutionGeneratorAgent` to generate actionable solutions.
3. Passes the solutions to `CostImpactSimulatorAgent` to simulate, quantify, and filter for the most effective strategies.

Returns only the final JSON output from the simulator, with no modification or commentary. Ensures strict sequencing and data integrity throughout the process.

## 3. Inputs

- **Input Source:**
    - User or upstream pipeline, providing:
        - `rawData`: Unprocessed financial transaction data (JSON)
        - `categoryAgentResponse`: Categorized transactions (JSON)
        - `benchMarkData`: Benchmark comparison data (JSON)
- **Format:**
    - All inputs must be valid JSON objects.
- **Validation Rules:**
    - All three input objects must be present and valid for the pipeline to run.

## 4. Expected Outputs

- **Primary Output Format:**
    - A single, valid JSON object (output of `CostImpactSimulatorAgent`).
    - No prose, markdown, or commentary—JSON only.
- **Sample Output JSON:**

```json
{
    "final_recommendations": [{ "solution": "Switch to AWS Graviton tier", "expected_savings": 12000, "risk": "Low" }]
}
```

- **Post-conditions:**
    - Downstream systems can rely on the output as a validated, filtered set of cost optimization actions.

## 5. Key Functions / Public Methods

| Function                                       | Purpose                                                                    | Inputs                                            | Outputs                 | Notes                                                                                 |
| ---------------------------------------------- | -------------------------------------------------------------------------- | ------------------------------------------------- | ----------------------- | ------------------------------------------------------------------------------------- |
| `execute(mixed $input, AgentContext $context)` | Main entry point. Orchestrates sub-agent calls and manages pipeline state. | `$input`: array/object; `$context`: agent context | JSON object (see above) | Calls RootAnalysisAgent, SolutionGeneratorAgent, CostImpactSimulatorAgent in sequence |

## 6. Sub-agents / Tools / Dependencies

- **Sub-agents:**
    - `RootAnalysisAgent` (class: `App\Agents\CostOptomizerAgent\RootAnalysisAgent`): Diagnoses root causes of high-priority cost anomalies by analyzing benchmark data against raw financial records and categorized spending patterns. Invoked first.
    - `SolutionGeneratorAgent` (class: `App\Agents\CostOptomizerAgent\SolutionGeneratorAgent`): Generates actionable cost-cutting solutions based on diagnosed root causes. Invoked second.
    - `SearchAgent` (class: `App\Agents\CostOptomizerAgent\SearchAgent`): Runs targeted web searches using `SearchTool` based on solution outputs to gather alternatives, pricing, and implementation insights. Used as needed by other agents.
    - `CostImpactSimulatorAgent` (class: `App\Agents\CostOptomizerAgent\CostImpactSimulatorAgent`): Simulates and quantifies the impact of each solution, filtering for the most effective strategies. Invoked last.

- **Tools:**
    - `SearchTool` (class: `App\Tools\SearchTool`): Used by `SearchAgent` and `CostImpactSimulatorAgent` to perform targeted searches for alternatives, pricing, and implementation data.

- **External Services:** None. All dependencies are internal sub-agents or tools.
