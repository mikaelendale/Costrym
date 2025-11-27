<?php

namespace App\Jobs;

use App\AiAgents\CostDecompositionAgent;
use App\AiAgents\BenchmarkAgent;
use App\AiAgents\CERAgent;
use App\AiAgents\RootAnalysisAgent;
use App\AiAgents\SolutionGeneratorAgent;
use App\AiAgents\CostImpactSimulatorAgent;
use App\AiAgents\ValueMapper;
use App\AiAgents\SmartReducer;
use App\Models\Automation;
use App\Models\FinancialRecord;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use LarAgent\Messages\UserMessage;

/**
 * First-Time Cost Analysis Job
 *
 * Runs a comprehensive cost analysis immediately after data ingestion.
 * This is a one-time deep dive that provides baseline insights before
 * the daily Master Orchestrator begins (24 hours later).
 *
 * Agent Flow:
 * 1. CostDecompositionAgent - Break down costs by product
 * 2. BenchmarkAgent - Research industry benchmarks
 * 3. CERAgent - Calculate cost efficiency ratios
 * 4. RootAnalysisAgent - Identify root causes of inefficiencies
 * 5. SolutionGeneratorAgent - Generate optimization solutions
 * 6. CostImpactSimulatorAgent - Simulate and validate solutions
 * 7. ValueMapper - Assess value impact
 * 8. SmartReducer - Final approved recommendations
 */
class FirstTimeCostAnalysisJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;
    public int $timeout = 1800; // 30 minutes
    public array $backoff = [300, 900]; // 5min, 15min

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $userId
    ) {
        $this->onQueue('default');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('FirstTimeCostAnalysisJob started', ['user_id' => $this->userId]);

        $user = User::find($this->userId);

        if (!$user) {
            Log::error('FirstTimeCostAnalysisJob: User not found', ['user_id' => $this->userId]);
            return;
        }

        // Check if first-time analysis already exists
        $existingAnalysis = Automation::where('user_id', $this->userId)
            ->where('type', 'first_time_cost_analysis')
            ->exists();

        if ($existingAnalysis) {
            Log::info('FirstTimeCostAnalysisJob: Analysis already exists', ['user_id' => $this->userId]);
            return;
        }

        // Gather all financial data
        $financialRecords = FinancialRecord::where('user_id', $this->userId)
            ->with('category')
            ->get();

        if ($financialRecords->isEmpty()) {
            Log::warning('FirstTimeCostAnalysisJob: No financial records found', ['user_id' => $this->userId]);
            return;
        }

        // Get company context from user profile
        $companyContext = $this->buildCompanyContext($user);

        // Initialize report sections
        $reportSections = [];
        $sessionId = 'first_time_analysis_' . $this->userId . '_' . time();

        try {
            // STEP 1: Cost Decomposition
            Log::info('Step 1: Running CostDecompositionAgent');
            $decompositionResult = $this->runCostDecomposition($sessionId, $financialRecords, $companyContext);
            $reportSections['decomposition'] = $decompositionResult;

            // STEP 2: Benchmark Analysis
            Log::info('Step 2: Running BenchmarkAgent');
            $benchmarkResult = $this->runBenchmarkAnalysis($sessionId, $companyContext, $decompositionResult);
            $reportSections['benchmark'] = $benchmarkResult;

            // STEP 3: CER Analysis
            Log::info('Step 3: Running CERAgent');
            $cerResult = $this->runCERAnalysis($sessionId, $benchmarkResult, $financialRecords);
            $reportSections['cer'] = $cerResult;

            // STEP 4: Root Cause Analysis
            Log::info('Step 4: Running RootAnalysisAgent');
            $rootCauseResult = $this->runRootCauseAnalysis($sessionId, $cerResult, $financialRecords);
            $reportSections['root_cause'] = $rootCauseResult;

            // STEP 5: Solution Generation
            Log::info('Step 5: Running SolutionGeneratorAgent');
            $solutionsResult = $this->runSolutionGeneration($sessionId, $rootCauseResult);
            $reportSections['solutions'] = $solutionsResult;

            // STEP 6: Cost Impact Simulation
            Log::info('Step 6: Running CostImpactSimulatorAgent');
            $simulationResult = $this->runCostImpactSimulation($sessionId, $solutionsResult);
            $reportSections['simulation'] = $simulationResult;

            // STEP 7: Value Mapping
            Log::info('Step 7: Running ValueMapper');
            $valueMappingResult = $this->runValueMapping($sessionId, $simulationResult, $companyContext);
            $reportSections['value_mapping'] = $valueMappingResult;

            // STEP 8: Smart Reduction (Final Recommendations)
            Log::info('Step 8: Running SmartReducer');
            $finalRecommendations = $this->runSmartReduction($sessionId, $valueMappingResult);
            $reportSections['recommendations'] = $finalRecommendations;

            // Compile final markdown report
            $finalReport = $this->compileReport($reportSections, $user);

            // Save to automations table
            $automation = Automation::create([
                'user_id' => $this->userId,
                'type' => 'first_time_cost_analysis',
                'name' => 'First-Time Cost Analysis Report',
                'description' => 'Comprehensive cost analysis performed after initial data ingestion',
                'status' => 'completed',
                'markdown_content' => $finalReport,
                'metadata' => [
                    'session_id' => $sessionId,
                    'records_analyzed' => $financialRecords->count(),
                    'completed_at' => now()->toIso8601String(),
                ],
            ]);

            Log::info('FirstTimeCostAnalysisJob completed successfully', [
                'user_id' => $this->userId,
                'automation_id' => $automation->id,
            ]);

            // Schedule Master Orchestrator to run 24 hours later
            MasterOrchestratorJob::dispatch(
                userId: $this->userId,
                additionalContext: ['trigger' => 'post_first_analysis']
            );
            // )->delay(now()->addDay());

            Log::info('Scheduled MasterOrchestratorJob for 24h later', ['user_id' => $this->userId]);

        } catch (\Exception $e) {
            Log::error('FirstTimeCostAnalysisJob failed', [
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Step 1: Cost Decomposition
     */
    private function runCostDecomposition(string $sessionId, $financialRecords, array $companyContext): string
    {
        $prompt = $this->buildDecompositionPrompt($financialRecords, $companyContext);
        $prompt .= "\n\n**IMPORTANT:** Use the following tools to enhance your analysis:\n";
        $prompt .= "1. Call `knowledge_base` to get detailed company context, products, and business model.\n";
        $prompt .= "2. Call `query_financial_records` with operation='spending_summary' to get overall spending patterns.\n";
        $prompt .= "3. Call `query_financial_records` with operation='category_breakdown' to see how costs are distributed.\n";
        $prompt .= "4. Use these insights to make data-driven cost allocations.\n";
        $prompt .= "\n**NOTE:** The tools automatically access the current user's data. You do NOT need to provide user_id.\n";
        
        // Bind user_id to container so tools can access it
        app()->instance('laragent.user_id', $this->userId);
        
        // Create user message with metadata
        $userMessage = new UserMessage($prompt, ['user_id' => $this->userId]);
        
        $response = CostDecompositionAgent::for($sessionId)->message($userMessage)->respond();
        
        return $response;
    }

    /**
     * Step 2: Benchmark Analysis
     */
    private function runBenchmarkAnalysis(string $sessionId, array $companyContext, string $previousSummary): string
    {
        $prompt = "Based on the following cost decomposition:\n\n{$previousSummary}\n\n";
        $prompt .= "Company Context:\n" . json_encode($companyContext, JSON_PRETTY_PRINT) . "\n\n";
        $prompt .= "**IMPORTANT INSTRUCTIONS:**\n";
        $prompt .= "1. First, call the `knowledge_base` tool to get comprehensive company information (products, services, industry, team size, business model).\n";
        $prompt .= "2. Use the web-related_operations tool (operation='search') to research industry benchmarks for companies similar to this one.\n";
        $prompt .= "3. Create a should-cost model based on actual company data and industry research.\n\n";
        $prompt .= "Research industry benchmarks and create a should-cost model.";
        
        // Bind user_id to container
        app()->instance('laragent.user_id', $this->userId);
        
        // Create user message with metadata
        $userMessage = new UserMessage($prompt, ['user_id' => $this->userId]);
        
        $response = BenchmarkAgent::for($sessionId)->message($userMessage)->respond();
        
        return $response;
    }

    /**
     * Step 3: CER Analysis
     */
    private function runCERAnalysis(string $sessionId, string $benchmarkData, $financialRecords): string
    {
        $actualSpend = $this->calculateActualSpend($financialRecords);
        
        $prompt = "Benchmark Data:\n{$benchmarkData}\n\n";
        $prompt .= "Actual Spend:\n" . json_encode($actualSpend, JSON_PRETTY_PRINT) . "\n\n";
        $prompt .= "Calculate cost efficiency ratios and identify high-priority variances.";
        
        $response = CERAgent::for($sessionId)->respond($prompt);
        
        return $response;
    }

    /**
     * Step 4: Root Cause Analysis
     */
    private function runRootCauseAnalysis(string $sessionId, string $cerData, $financialRecords): string
    {
        $transactionData = $this->formatTransactionData($financialRecords);
        
        $prompt = "CER Analysis (High-Priority Issues):\n{$cerData}\n\n";
        $prompt .= "Transaction Data:\n{$transactionData}\n\n";
        $prompt .= "**CRITICAL INSTRUCTIONS:**\n";
        $prompt .= "1. Call `knowledge_base` to understand company context and what each cost category represents.\n";
        $prompt .= "2. Call `query_financial_records` with operation='category_breakdown' to analyze spending patterns.\n";
        $prompt .= "3. Call `query_financial_records` with operation='top_expenses' and limit=50 to identify largest cost drivers.\n";
        $prompt .= "4. Call `query_financial_records` with operation='monthly_trend' to spot unusual patterns.\n";
        $prompt .= "5. Use this real data to identify specific root causes for all high-priority cost inefficiencies.\n";
        $prompt .= "\n**NOTE:** The tools automatically access the current user's data. You do NOT need to provide user_id.\n\n";
        $prompt .= "Identify root causes for all high-priority cost inefficiencies.";
        
        // Bind user_id to container
        app()->instance('laragent.user_id', $this->userId);
        
        // Create user message with metadata
        $userMessage = new UserMessage($prompt, ['user_id' => $this->userId]);
        
        $response = RootAnalysisAgent::for($sessionId)->message($userMessage)->respond();
        
        return $response;
    }

    /**
     * Step 5: Solution Generation
     */
    private function runSolutionGeneration(string $sessionId, string $rootCauseData): string
    {
        $prompt = "Root Cause Analysis:\n{$rootCauseData}\n\n";
        $prompt .= "**INSTRUCTIONS:**\n";
        $prompt .= "1. Call the `knowledge_base` tool to understand company products, business model, and strategic priorities.\n";
        $prompt .= "2. Generate specific, actionable cost optimization solutions that align with the company's business strategy.\n";
        $prompt .= "3. Ensure solutions don't harm critical business functions or customer satisfaction.\n\n";
        $prompt .= "Generate specific, actionable cost optimization solutions for each identified root cause.";
        
        // Bind user_id to container
        app()->instance('laragent.user_id', $this->userId);
        
        // Create user message with metadata
        $userMessage = new UserMessage($prompt, ['user_id' => $this->userId]);
        
        $response = SolutionGeneratorAgent::for($sessionId)->message($userMessage)->respond();
        
        return $response;
    }

    /**
     * Step 6: Cost Impact Simulation
     */
    private function runCostImpactSimulation(string $sessionId, string $solutionsData): string
    {
        $prompt = "Proposed Solutions:\n{$solutionsData}\n\n";
        $prompt .= "Evaluate each solution, estimate savings/effort/risk, and filter to create a validated cost-cutting portfolio.";
        
        $response = CostImpactSimulatorAgent::for($sessionId)->respond($prompt);
        
        return $response;
    }

    /**
     * Step 7: Value Mapping
     */
    private function runValueMapping(string $sessionId, string $portfolioData, array $companyContext): string
    {
        $prompt = "Cost-Cutting Portfolio:\n{$portfolioData}\n\n";
        $prompt .= "Company Context:\n" . json_encode($companyContext, JSON_PRETTY_PRINT) . "\n\n";
        $prompt .= "**INSTRUCTIONS:**\n";
        $prompt .= "1. Call the `knowledge_base` tool to get detailed company goals, values, and strategic priorities.\n";
        $prompt .= "2. Assess the true value impact of each optimization considering both tangible savings and intangible business impacts.\n";
        $prompt .= "3. Consider how each solution aligns with company strategy and long-term goals.\n\n";
        $prompt .= "Assess the true value impact of each optimization, considering both tangible and intangible factors.";
        
        // Bind user_id to container
        app()->instance('laragent.user_id', $this->userId);
        
        // Create user message with metadata
        $userMessage = new UserMessage($prompt, ['user_id' => $this->userId]);
        
        $response = ValueMapper::for($sessionId)->message($userMessage)->respond();
        
        return $response;
    }

    /**
     * Step 8: Smart Reduction (Final Recommendations)
     */
    private function runSmartReduction(string $sessionId, string $valueMappingData): string
    {
        $prompt = "Value-Assessed Portfolio:\n{$valueMappingData}\n\n";
        $prompt .= "Classify each optimization by value, filter out value-negative items, and create final executable tasks.";
        
        $response = SmartReducer::for($sessionId)->respond($prompt);
        
        return $response;
    }

    /**
     * Build company context from user profile
     */
    private function buildCompanyContext(User $user): array
    {
        return [
            'company_name' => $user->company_name ?? 'Unknown',
            'industry' => $user->industry ?? 'Unknown',
            'location' => $user->location ?? 'Unknown',
            'revenue' => $user->annual_revenue ?? 'Unknown',
            'employee_count' => $user->employee_count ?? 'Unknown',
            'business_model' => $user->business_model ?? 'Unknown',
            'products' => $user->products ?? [],
        ];
    }

    /**
     * Build decomposition prompt
     */
    private function buildDecompositionPrompt($financialRecords, array $companyContext): string
    {
        // Filter for direct costs using a closure since 'LIKE' doesn't work on Collections
        $directCosts = $financialRecords->filter(function ($record) {
            return stripos($record->tags ?? '', 'Direct') !== false;
        })->values();
        
        $prompt = "Company Context:\n" . json_encode($companyContext, JSON_PRETTY_PRINT) . "\n\n";
        $prompt .= "Direct Costs (" . $directCosts->count() . " items):\n";
        
        foreach ($directCosts->take(50) as $record) {
            $categoryName = $record->category->name ?? 'Uncategorized';
            $prompt .= "- {$record->description}: \${$record->amount} ({$categoryName})\n";
        }
        
        $prompt .= "\nAnalyze and decompose these costs by product.";
        
        return $prompt;
    }

    /**
     * Calculate actual spend by category
     */
    private function calculateActualSpend($financialRecords): array
    {
        $spendByCategory = [];
        
        foreach ($financialRecords->groupBy('category_id') as $categoryId => $records) {
            $category = $records->first()->category;
            $spendByCategory[$category->name ?? 'Uncategorized'] = $records->sum('amount');
        }
        
        return $spendByCategory;
    }

    /**
     * Format transaction data for analysis
     */
    private function formatTransactionData($financialRecords): string
    {
        $formatted = "Recent Transactions:\n";
        
        foreach ($financialRecords->take(100) as $record) {
            $categoryName = $record->category->name ?? 'Uncategorized';
            $formatted .= "- {$record->date->format('Y-m-d')}: {$record->description} - \${$record->amount} ({$categoryName})\n";
        }
        
        return $formatted;
    }

    /**
     * Compile final markdown report
     */
    private function compileReport(array $sections, User $user): string
    {
        $report = "# First-Time Cost Analysis Report\n\n";
        $report .= "**Company:** {$user->company_name}\n";
        $report .= "**Generated:** " . now()->format('F j, Y g:i A') . "\n\n";
        $report .= "---\n\n";
        
        $report .= "## Executive Summary\n\n";
        $report .= "This comprehensive analysis was performed immediately after your financial data ingestion. ";
        $report .= "It provides a deep dive into your cost structure, industry benchmarks, and optimization opportunities.\n\n";
        $report .= "---\n\n";
        
        $report .= "## 1. Cost Decomposition Analysis\n\n";
        $report .= $sections['decomposition'] . "\n\n";
        $report .= "---\n\n";
        
        $report .= "## 2. Industry Benchmark Analysis\n\n";
        $report .= $sections['benchmark'] . "\n\n";
        $report .= "---\n\n";
        
        $report .= "## 3. Cost Efficiency Ratio Analysis\n\n";
        $report .= $sections['cer'] . "\n\n";
        $report .= "---\n\n";
        
        $report .= "## 4. Root Cause Analysis\n\n";
        $report .= $sections['root_cause'] . "\n\n";
        $report .= "---\n\n";
        
        $report .= "## 5. Optimization Solutions\n\n";
        $report .= $sections['solutions'] . "\n\n";
        $report .= "---\n\n";
        
        $report .= "## 6. Validated Cost-Cutting Portfolio\n\n";
        $report .= $sections['simulation'] . "\n\n";
        $report .= "---\n\n";
        
        $report .= "## 7. Value Impact Assessment\n\n";
        $report .= $sections['value_mapping'] . "\n\n";
        $report .= "---\n\n";
        
        $report .= "## 8. Final Recommendations\n\n";
        $report .= $sections['recommendations'] . "\n\n";
        $report .= "---\n\n";
        
        $report .= "## Next Steps\n\n";
        $report .= "1. Review the recommended optimizations above\n";
        $report .= "2. Prioritize based on expected savings and risk level\n";
        $report .= "3. The Master Orchestrator will begin daily monitoring in 24 hours\n";
        $report .= "4. Track progress through your automation dashboard\n\n";
        
        return $report;
    }
}
