## 1. Overview

- **Name:** `CostValueAlignerAgent`
- **Location:** `app/Agents/CostValueAlignerAgent.php`
- **Purpose:** Orchestrates the Cost-to-Value Alignment process by coordinating Value Mapping and Smart Reduction sub-agents. Ensures strict sequencing and data integrity, returning only the final, strictly formatted JSON output.
- **Persona:** Cost-to-Value Alignment (CVA) Orchestrator. Purely orchestration—never performs its own analysis, only manages sub-agent invocation and output sanitization.

## 2. Behavior Summary

The agent acts as an orchestrator, sequentially invoking the `ValueMapper` and then the `SmartReducer` sub-agents. It passes the raw user input to `ValueMapper`, processes the result, and then passes the enriched output to `SmartReducer`. The final output is sanitized to ensure it is a single, valid JSON object, with all non-JSON commentary stripped. The agent never performs its own analysis or transformation beyond orchestration and output validation.

## 3. Inputs

- **Input Source:**
    - User prompt or pipeline context, typically containing mixed input for value mapping and reduction.
- **Format:**
    - Mixed (array/object/string) as received from upstream or user.
- **Validation Rules:**
    - Input must be valid for the `ValueMapper` sub-agent. No additional validation is performed by this agent.

## 4. Expected Outputs

- **Primary Output Format:**
    - A single, valid JSON object (output of `SmartReducer`).
    - No prose, markdown, or commentary—JSON only.
- **Sample Output JSON:**

```json
{
    "final_tasks": [{ "task_id": "123", "description": "Reduce SaaS spend by 20%", "value": 1000 }]
}
```

- **Post-conditions:**
    - Downstream systems can rely on the output as the final, filtered set of value-aligned tasks or actions.

## 5. Key Functions / Public Methods

| Function                                         | Purpose                                                                 | Inputs                                     | Outputs                 | Notes                                                                       |
| ------------------------------------------------ | ----------------------------------------------------------------------- | ------------------------------------------ | ----------------------- | --------------------------------------------------------------------------- |
| `execute(mixed $input, AgentContext $context)`   | Main entry point. Orchestrates sub-agent calls and output sanitization. | `$input`: mixed; `$context`: agent context | JSON object (see above) | Calls ValueMapper, then SmartReducer, updates context state, logs as needed |
| `sanitizeJsonOutput(string $raw): string`        | Ensures output is a single valid JSON object                            | Raw string                                 | JSON string             | Strips non-JSON, returns `{}` if invalid                                    |
| `getToolsForPrism(AgentContext $context): array` | Registers tools for orchestration                                       | Context                                    | Array of tools          | Ensures no duplicate delegate tool registration                             |

## 6. Sub-agents / Tools / Dependencies

- **Sub-agents:**
    - `ValueMapper` (class: `App\Agents\ValueMapper`):
        - **Purpose:** Analyzes the provided input and enriches the portfolio with value mapping logic. Responsible for transforming raw or mixed input into a structured, value-enriched format for downstream processing.
        - **Invocation:** Always called first in the sequence.
    - `SmartReducer` (class: `App\Agents\SmartReducer`):
        - **Purpose:** Receives the enriched portfolio from `ValueMapper` and filters out value-negative items. Produces the final JSON object of tasks or actions that are value-aligned and ready for execution.
        - **Invocation:** Always called second, after `ValueMapper`.

- **Tools:**
    - `DelegateToSubAgentTool` (class: `Vizra\VizraADK\Tools\DelegateToSubAgentTool`):
        - **Purpose:** Provides the mechanism for this agent to delegate execution to its sub-agents in a controlled, sequential manner. Ensures that sub-agent calls are managed and their outputs are properly captured and passed along the pipeline.

- **External Services:** None. All dependencies are internal sub-agents or tools.

## 7. Execution Constraints

- Must invoke sub-agents in strict sequence: ValueMapper → SmartReducer
- Output must be a single valid JSON object (no prose, markdown, or commentary)
- If sub-agent is missing, returns an error JSON
- Logging is performed after LLM response for traceability

## 8. Testing / Diagnostics Tips

- Test with various input types to ensure correct sequencing and output sanitization
- Check logs for 'After LLM response cost value aligner .....' and response content
- Validate that output JSON is strictly formatted and contains no extra fields or commentary

## 9. Fill-in Checklist

- [x] `Overview` completed
- [x] `Inputs` described with formats
- [x] `Outputs` sample snippet provided
- [x] `Key Functions` listed for teammates to integrate
- [x] `Constraints` and `notes` captured
