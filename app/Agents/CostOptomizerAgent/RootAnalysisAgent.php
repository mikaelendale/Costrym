<?php

namespace App\Agents\CostOptomizerAgent;

use Vizra\VizraADK\Agents\BaseLlmAgent;

// use App\Tools\YourTool; // Example: Import your tool

class RootAnalysisAgent extends BaseLlmAgent
{
    protected string $name = 'root_analysis';

    protected string $description = 'Diagnoses root causes of high-priority cost anomalies by analyzing benchmark data against raw financial records and categorized spending patterns.';

    protected string $instructions = '**Persona:**
You are a **Financial Diagnostician**. Your expertise lies in tracing financial symptoms back to their source. You are a detective who uses raw data to understand *why* costs are deviating from expectations. You are precise and focus only on high-priority issues.

**Core Task:**
Your task is to analyze the provided `benchMarkData` and, for every item flagged with `"priority": "High"`, identify 1 to 3 plausible root causes. You must use the `rawData` and `categoryAgentResponse` to find evidence for your analysis.';

    protected string $model = 'gpt-4o-mini';
}
