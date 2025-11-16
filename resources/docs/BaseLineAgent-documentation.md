## 1. Overview

- **Name:** `BaseLineAgent`
- **Location:** `app/Agents/BaseLineAgent.php`
- **Purpose:** Analyzes company spending patterns to define baselines, identify recurring costs, and major expense drivers.
- **Persona (if LLM agent):** a specialized AI analyst and the foundational layer of the Costrym AI ecosystem. Your primary mission is to analyze categorized financial transactions and establish the financial "ground truth" for a company. You define what is normal, which costs matter, which patterns repeat, and how long the company's cash will last. You are precise, data-driven, and your output is the bedrock for all subsequent financial analysis.

## 2. Behavior Summary

The BaseLineAgent is responsible for establishing the financial baseline for a company by analyzing categorized financial transactions. It orchestrates the following steps:

1. **Executes the RollingAggregateTool** to obtain rolling aggregates, financial metrics, and a list of transactions.
2. **Maps core financials** from the tool output to the required output schema, strictly including only the specified periods (7, 30, 90, 365 days).
3. **Synthesizes a detailed `patterns` narrative** describing overall spending trends, key drivers, and contextual insights based on business context.
4. **Identifies major expense drivers** by grouping and ranking vendors by spend over the last 90 days.
5. **Detects recurring costs** by analyzing transaction regularity and predicting next billing dates.

Strict output constraints: The agent must return a single valid JSON object (no prose, markdown, or commentary), using null or empty arrays for missing data, and ISO 8601 for dates.

## 3. Inputs

- **Input Source:**
    - User prompt or pipeline context, typically containing business context and a request to analyze financial transactions.
    - The agent always calls the RollingAggregateTool to fetch the actual transaction and aggregate data.
- **Format:**
    - Input is usually a structured array/object with business context (e.g., industry, employee count, business model).
    - No direct transaction data is required from the user; the agent fetches it via the tool.
- **Validation Rules:**
    - Business context should be present for richer analysis, but the agent can run with minimal input.
    - The RollingAggregateTool must return valid data for the agent to proceed.

## 4. Expected Outputs

- **Primary Output Format:**
    - A single, valid JSON object with the following schema:

```json
{
    "rolling_aggregates": {
        "7_days": 0.0,
        "30_days": 0.0,
        "90_days": 0.0,
        "365_days": 0.0
    },
    "financial_metrics": {
        "burn_rate": 0.0,
        "runway": 0.0
    },
    "patterns": "A descriptive, multi-sentence analysis of spending trends, key drivers, and incorporating business context.",
    "recurring_costs": [
        {
            "vendor": "Name of the vendor or service.",
            "recurrence_type": "monthly | weekly | yearly | quarterly | biannually | biweekly",
            "category": "The canonical cost category for this expense.",
            "recurring_amount": 0.0,
            "next_bill_date": "YYYY-MM-DD"
        }
    ],
    "major_expense_drivers": [
        {
            "vendor": "Name of the vendor or service with high costs.",
            "amount": 0.0,
            "reason": "A quantitative explanation of why this is a major driver (e.g., percentage of total spend)."
        }
    ]
}
```

- **Sample Output JSON:**
    - See above schema. All fields must be present; use `null` or `[]` for missing data.
- **Post-conditions:**
    - Downstream agents and processes can rely on the output as the "ground truth" for company spending, recurring costs, and major expense drivers.

## 5. Key Functions / Public Methods

| Function                                       | Purpose                                                                | Inputs                                                               | Outputs                        | Notes                                                                               |
| ---------------------------------------------- | ---------------------------------------------------------------------- | -------------------------------------------------------------------- | ------------------------------ | ----------------------------------------------------------------------------------- |
| `execute(mixed $input, AgentContext $context)` | Main entry point. Orchestrates tool call, analysis, and output mapping | `$input`: business context (array/object); `$context`: agent context | JSON object (see schema above) | Calls RollingAggregateTool, updates context state, logs before/after LLM/tool calls |

## 6. Sub-agents / Tools / Dependencies

- **Sub-agents:** None (all logic is internal or via tools)
- **Tools:**
    - `RollingAggregateTool` (app/Tools/BaseLineAnalysis/RollingAggregateTool.php): Calculates rolling aggregates, metrics, and provides transaction data. Always called as the first step.
- **External Services:** None directly; relies on internal data and tools.
