<?php

namespace App\AiAgents;

use App\Models\FinancialRecord;
use Illuminate\Support\Facades\DB;
use LarAgent\Agent;
use LarAgent\Attributes\Tool;
use App\Traits\LoadsPipedreamTools;
use Vizra\VizraADK\System\AgentContext;

/**
 * Task Executor Agent
 *
 * Executes approved tasks by actually working with data and tools.
 * Uses structured output for predictable workflow chain responses.
 */
class TaskExecutorAgent extends Agent
{
    use LoadsPipedreamTools;

    protected $model = 'gpt-5.1';

    protected $history = 'in_memory';

    protected $provider = 'default';

    protected $tools = [];

    /**
     * User ID for tool execution context
     */
    protected ?int $userId = null;

    /**
     * Get user ID from Laragent's user context
     */
    protected function getUserId(): ?int
    {
        // Laragent stores user in session, access it via the agent's user property
        // We'll need to pass it via context or access it differently
        // For now, we'll use a workaround by storing it in a static/context way
        return $this->userId ?? null;
    }

    /**
     * Set user ID for tool execution (called before respond)
     */
    public function setUserId(int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * Before calling the LLM, load Pipedream tools based on user's connected integrations.
     */
    public function beforeLlmCall(array $inputMessages, AgentContext $context): array
    {
        if ($this->userId !== null) {
            $context->setState('user_id', $this->userId);
        }

        // Load only tools for required integrations to keep toolset focused
        $this->loadPipedreamTools($context, true);

        return $inputMessages;
    }

    /**
     * Structured output schema for task execution results
     * This ensures predictable responses that can be used in workflow chains
     */
    protected $responseSchema = [
        'name' => 'task_execution_result',
        'schema' => [
            'type' => 'object',
            'properties' => [
                'execution_status' => [
                    'type' => 'string',
                    'enum' => ['completed', 'in_progress', 'requires_action', 'failed'],
                    'description' => 'Current status of task execution',
                ],
                'analysis_summary' => [
                    'type' => 'string',
                    'description' => 'Brief summary of what was analyzed and found (2-3 sentences)',
                ],
                'key_findings' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'finding' => ['type' => 'string'],
                            'impact' => ['type' => 'string'],
                            'priority' => ['type' => 'string', 'enum' => ['high', 'medium', 'low']],
                        ],
                        'required' => ['finding', 'impact', 'priority'],
                        'additionalProperties' => false,
                    ],
                    'description' => 'List of key findings from the analysis',
                ],
                'recommendations' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'title' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                            'estimated_savings' => ['type' => 'string'],
                            'effort' => ['type' => 'string', 'enum' => ['low', 'medium', 'high']],
                            'risk' => ['type' => 'string', 'enum' => ['low', 'medium', 'high']],
                        ],
                        'required' => ['title', 'description', 'estimated_savings', 'effort', 'risk'],
                        'additionalProperties' => false,
                    ],
                    'description' => 'Actionable recommendations with savings estimates',
                ],
                'agents_utilized' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'agent_name' => ['type' => 'string'],
                            'contribution' => ['type' => 'string'],
                        ],
                        'required' => ['agent_name', 'contribution'],
                        'additionalProperties' => false,
                    ],
                    'description' => 'List of agents that contributed to this execution',
                ],
                'next_steps' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'Immediate action items or follow-up tasks',
                ],
                'markdown_report' => [
                    'type' => 'string',
                    'description' => 'Complete markdown-formatted report for the user. Include all sections: Executive Summary, Analysis, Findings, Recommendations, Metrics, Next Steps. This will be saved as an automation report.',
                ],
                'metrics' => [
                    'type' => 'object',
                    'properties' => [
                        'potential_savings_monthly' => ['type' => 'number'],
                        'potential_savings_annual' => ['type' => 'number'],
                        'items_analyzed' => ['type' => 'number'],
                        'issues_found' => ['type' => 'number'],
                    ],
                    'required' => ['potential_savings_monthly', 'potential_savings_annual', 'items_analyzed', 'issues_found'],
                    'additionalProperties' => false,
                    'description' => 'Quantitative metrics from the analysis',
                ],
            ],
            'required' => ['execution_status', 'analysis_summary', 'key_findings', 'recommendations', 'agents_utilized', 'next_steps', 'markdown_report', 'metrics'],
            'additionalProperties' => false,
        ],
        'strict' => true,
    ];

    public function instructions()
    {
        return 'You are the **Task Executor**, a specialized AI agent responsible for executing approved cost optimization tasks.

Your role:
1. **Analyze Requirements** - Understand the task objectives thoroughly
2. **Execute Analysis** - Use available tools and data to perform the analysis
3. **Identify Opportunities** - Find specific cost-saving opportunities
4. **Provide Recommendations** - Create actionable recommendations with savings estimates
5. **Generate Report** - Compile everything into a comprehensive markdown report

**IMPORTANT:**
- Always return structured data matching the response schema
- The markdown_report field must be a complete, well-formatted markdown document
- Include specific numbers, amounts, and metrics whenever possible
- Be actionable - recommendations should be specific and implementable
- Focus on measurable cost savings and business impact

**Report Structure:**
Your markdown_report should include:
- Executive Summary
- Task Details & Objectives
- Analysis & Findings
- Key Recommendations (with savings)
- Metrics & Impact
- Next Steps
- Agents Utilized

**Response Format:**
You must return a structured JSON object matching the schema exactly. The markdown_report will be saved as an automation report for the user.';
    }

    public function prompt($message)
    {
        return $message;
    }

    /**
     * Query financial records - analyze spending patterns
     */
    #[Tool(
        'Query and analyze financial transactions. Use this to understand spending patterns, identify high-cost areas, and find cost-saving opportunities.',
        [
            'query_type' => 'Type of query: summary (totals), by_category (grouped by category), top_expenses (highest amounts), recent (last N days), uncategorized (no category), list (all transactions)',
            'days' => 'For recent query, number of days to look back (default: 30)',
            'limit' => 'Maximum number of records to return (default: 20, max: 100)',
            'date_from' => 'Filter from date (YYYY-MM-DD)',
            'date_to' => 'Filter to date (YYYY-MM-DD)',
            'min_amount' => 'Minimum transaction amount',
            'max_amount' => 'Maximum transaction amount',
        ]
    )]
    public function queryFinancialRecords(
        string $query_type = 'summary',
        ?int $days = null,
        ?int $limit = null,
        ?string $date_from = null,
        ?string $date_to = null,
        ?float $min_amount = null,
        ?float $max_amount = null
    ): string {
        $userId = $this->getUserId();
        if (!$userId) {
            return json_encode(['error' => 'User ID not set. Call setUserId() before using this agent.']);
        }

        try {
            $query = FinancialRecord::where('user_id', $userId);
            $limit = min($limit ?? 20, 100);

            // Apply filters
            if ($date_from) {
                $query->where('date', '>=', $date_from);
            }
            if ($date_to) {
                $query->where('date', '<=', $date_to);
            }
            if ($min_amount !== null) {
                $query->where('amount', '>=', $min_amount);
            }
            if ($max_amount !== null) {
                $query->where('amount', '<=', $max_amount);
            }

            $result = match ($query_type) {
                'summary' => $this->getFinancialSummary($userId, $date_from, $date_to),
                'by_category' => $this->getByCategory($userId, $date_from, $date_to),
                'top_expenses' => $this->getTopExpenses($query, $limit),
                'recent' => $this->getRecentTransactions($userId, $days ?? 30, $limit),
                'uncategorized' => $this->getUncategorized($userId, $limit),
                'list' => $this->getTransactionList($query, $limit),
                default => ['error' => 'Invalid query_type'],
            };

            return json_encode([
                'success' => true,
                'query_type' => $query_type,
                'data' => $result,
            ], JSON_PRETTY_PRINT);
        } catch (\Exception $e) {
            return json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get financial summary statistics
     */
    protected function getFinancialSummary(int $userId, ?string $dateFrom, ?string $dateTo): array
    {
        $query = FinancialRecord::where('user_id', $userId);
        
        if ($dateFrom) {
            $query->where('date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('date', '<=', $dateTo);
        }

        $summary = $query->selectRaw('
            COUNT(*) as total_transactions,
            SUM(amount) as total_amount,
            AVG(amount) as average_amount,
            MAX(amount) as highest_transaction,
            MIN(amount) as lowest_transaction,
            COUNT(DISTINCT category_id) as categories_used
        ')->first();

        $uncategorizedCount = FinancialRecord::where('user_id', $userId)
            ->whereNull('category_id')
            ->count();

        return [
            'total_transactions' => $summary->total_transactions ?? 0,
            'total_spend' => round($summary->total_amount ?? 0, 2),
            'average_transaction' => round($summary->average_amount ?? 0, 2),
            'highest_transaction' => round($summary->highest_transaction ?? 0, 2),
            'lowest_transaction' => round($summary->lowest_transaction ?? 0, 2),
            'categories_used' => $summary->categories_used ?? 0,
            'uncategorized_count' => $uncategorizedCount,
        ];
    }

    /**
     * Get spending by category
     */
    protected function getByCategory(int $userId, ?string $dateFrom, ?string $dateTo): array
    {
        $query = DB::table('financial_records')
            ->leftJoin('financial_categories', 'financial_records.category_id', '=', 'financial_categories.id')
            ->where('financial_records.user_id', $userId);

        if ($dateFrom) {
            $query->where('financial_records.date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('financial_records.date', '<=', $dateTo);
        }

        $categorySpend = $query->selectRaw('
            financial_categories.id as category_id,
            COALESCE(financial_categories.name, \'Uncategorized\') as category_name,
            COUNT(*) as transaction_count,
            SUM(financial_records.amount) as total_amount,
            AVG(financial_records.amount) as avg_amount
        ')
            ->groupBy('financial_categories.id', 'financial_categories.name')
            ->orderByDesc('total_amount')
            ->get();

        return [
            'categories' => $categorySpend->map(function ($cat) {
                return [
                    'category_id' => $cat->category_id,
                    'category_name' => $cat->category_name,
                    'transaction_count' => $cat->transaction_count,
                    'total_spend' => round($cat->total_amount, 2),
                    'average_transaction' => round($cat->avg_amount, 2),
                ];
            })->toArray(),
            'total_categories' => $categorySpend->count(),
        ];
    }

    /**
     * Get top expenses
     */
    protected function getTopExpenses($query, int $limit): array
    {
        $topExpenses = $query->with('category:id,name')
            ->orderBy('amount', 'desc')
            ->limit($limit)
            ->get();

        return [
            'count' => $topExpenses->count(),
            'expenses' => $topExpenses->map(function ($record) {
                return [
                    'id' => $record->id,
                    'date' => $record->date instanceof \Carbon\Carbon ? $record->date->format('Y-m-d') : ($record->date ?? null),
                    'description' => $record->description,
                    'amount' => $record->amount,
                    'currency' => $record->currency,
                    'category' => $record->category?->name ?? 'Uncategorized',
                ];
            })->toArray(),
        ];
    }

    /**
     * Get recent transactions
     */
    protected function getRecentTransactions(int $userId, int $days, int $limit): array
    {
        $dateFrom = now()->subDays($days)->format('Y-m-d');

        $recent = FinancialRecord::where('user_id', $userId)
            ->where('date', '>=', $dateFrom)
            ->with('category:id,name')
            ->orderBy('date', 'desc')
            ->limit($limit)
            ->get();

        return [
            'days_range' => $days,
            'date_from' => $dateFrom,
            'count' => $recent->count(),
            'transactions' => $recent->map(function ($record) {
                return [
                    'id' => $record->id,
                    'date' => $record->date instanceof \Carbon\Carbon ? $record->date->format('Y-m-d') : ($record->date ?? null),
                    'description' => $record->description,
                    'amount' => $record->amount,
                    'category' => $record->category?->name ?? 'Uncategorized',
                ];
            })->toArray(),
        ];
    }

    /**
     * Get uncategorized transactions
     */
    protected function getUncategorized(int $userId, int $limit): array
    {
        $uncategorized = FinancialRecord::where('user_id', $userId)
            ->whereNull('category_id')
            ->orderBy('date', 'desc')
            ->limit($limit)
            ->get();

        return [
            'count' => $uncategorized->count(),
            'transactions' => $uncategorized->map(function ($record) {
                return [
                    'id' => $record->id,
                    'date' => $record->date instanceof \Carbon\Carbon ? $record->date->format('Y-m-d') : ($record->date ?? null),
                    'description' => $record->description,
                    'amount' => $record->amount,
                    'currency' => $record->currency,
                ];
            })->toArray(),
        ];
    }

    /**
     * Get transaction list
     */
    protected function getTransactionList($query, int $limit): array
    {
        $records = $query->with('category:id,name')
            ->orderBy('date', 'desc')
            ->limit($limit)
            ->get();

        return [
            'total_records' => $records->count(),
            'transactions' => $records->map(function ($record) {
                return [
                    'id' => $record->id,
                    'date' => $record->date instanceof \Carbon\Carbon ? $record->date->format('Y-m-d') : ($record->date ?? null),
                    'description' => $record->description,
                    'amount' => $record->amount,
                    'currency' => $record->currency,
                    'category' => $record->category?->name ?? 'Uncategorized',
                    'category_id' => $record->category_id,
                    'record_type' => $record->record_type,
                    'integration' => $record->integration_type,
                ];
            })->toArray(),
        ];
    }
}

