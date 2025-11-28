<?php

namespace App\Tools;

use App\Models\FinancialRecord;
use Illuminate\Support\Facades\DB;
use LarAgent\Tool;

class LarAgentQueryFinancialRecordsTool extends Tool
{
    protected string $name = 'query_financial_records';

    protected string $description = 'Query and analyze financial transactions from the database. Supports filtering by category, date range, amount, and aggregations. Use this to understand spending patterns, identify high-cost areas, and find cost-saving opportunities.';

    protected array $properties = [
        'query_type' => [
            'type' => 'string',
            'enum' => ['list', 'summary', 'by_category', 'top_expenses', 'recent', 'uncategorized'],
            'description' => 'Type of query: list (get transactions), summary (totals), by_category (grouped), top_expenses (highest amounts), recent (last N days), uncategorized (no category assigned)',
            'default' => 'summary',
        ],
        'category_id' => [
            'type' => 'integer',
            'description' => 'Filter by specific category ID',
        ],
        'date_from' => [
            'type' => 'string',
            'description' => 'Filter transactions from this date (YYYY-MM-DD)',
        ],
        'date_to' => [
            'type' => 'string',
            'description' => 'Filter transactions until this date (YYYY-MM-DD)',
        ],
        'min_amount' => [
            'type' => 'number',
            'description' => 'Filter transactions with amount >= this value',
        ],
        'max_amount' => [
            'type' => 'number',
            'description' => 'Filter transactions with amount <= this value',
        ],
        'limit' => [
            'type' => 'integer',
            'description' => 'Maximum number of records to return (default: 20, max: 100)',
            'default' => 20,
        ],
        'days' => [
            'type' => 'integer',
            'description' => 'For "recent" query type, number of days to look back (default: 30)',
            'default' => 30,
        ],
    ];

    protected array $required = ['query_type'];

    public function execute(array $input): mixed
    {
        try {
            // Get user ID from service container
            $userId = app('laragent.user_id') ?? null;

            if (! $userId) {
                return json_encode([
                    'success' => false,
                    'error' => 'User ID not found in context.',
                ]);
            }

            $queryType = $input['query_type'] ?? 'summary';
            $limit = min($input['limit'] ?? 20, 100);

            // Build base query
            $query = FinancialRecord::where('user_id', $userId);

            // Apply filters
            if (isset($input['category_id'])) {
                $query->where('category_id', $input['category_id']);
            }

            if (isset($input['date_from'])) {
                $query->where('date', '>=', $input['date_from']);
            }

            if (isset($input['date_to'])) {
                $query->where('date', '<=', $input['date_to']);
            }

            if (isset($input['min_amount'])) {
                $query->where('amount', '>=', $input['min_amount']);
            }

            if (isset($input['max_amount'])) {
                $query->where('amount', '<=', $input['max_amount']);
            }

            // Execute query based on type
            $result = match ($queryType) {
                'list' => $this->getList($query, $limit),
                'summary' => $this->getSummary($userId, $input),
                'by_category' => $this->getByCategory($userId, $input),
                'top_expenses' => $this->getTopExpenses($query, $limit),
                'recent' => $this->getRecent($userId, $input['days'] ?? 30, $limit),
                'uncategorized' => $this->getUncategorized($userId, $limit),
                default => ['error' => 'Invalid query_type'],
            };

            return json_encode([
                'success' => true,
                'query_type' => $queryType,
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
     * Get list of transactions
     */
    protected function getList($query, int $limit): array
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
                    'date' => $record->date?->format('Y-m-d'),
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

    /**
     * Get summary statistics
     */
    protected function getSummary(int $userId, array $filters): array
    {
        $query = FinancialRecord::where('user_id', $userId);

        // Apply filters
        if (isset($filters['date_from'])) {
            $query->where('date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('date', '<=', $filters['date_to']);
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
     * Get spending grouped by category
     */
    protected function getByCategory(int $userId, array $filters): array
    {
        $query = DB::table('financial_records')
            ->leftJoin('financial_categories', 'financial_records.category_id', '=', 'financial_categories.id')
            ->where('financial_records.user_id', $userId);

        // Apply filters
        if (isset($filters['date_from'])) {
            $query->where('financial_records.date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('financial_records.date', '<=', $filters['date_to']);
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
     * Get top N expenses
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
                    'date' => $record->date?->format('Y-m-d'),
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
    protected function getRecent(int $userId, int $days, int $limit): array
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
                    'date' => $record->date?->format('Y-m-d'),
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
                    'date' => $record->date?->format('Y-m-d'),
                    'description' => $record->description,
                    'amount' => $record->amount,
                    'currency' => $record->currency,
                ];
            })->toArray(),
        ];
    }
}
