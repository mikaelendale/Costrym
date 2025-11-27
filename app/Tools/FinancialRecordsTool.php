<?php

namespace App\Tools;

use App\Models\FinancialRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use LarAgent\Tool;

/**
 * Financial Records Tool
 *
 * Allows agents to query and analyze financial records from the database.
 * The user_id is automatically retrieved from the message metadata.
 */
class FinancialRecordsTool extends Tool
{
    protected string $name = 'query_financial_records';

    protected string $description = 'Query and analyze financial records from the database. Use this to get transaction details, spending patterns, category breakdowns, time-based analysis, and specific financial data for cost analysis and optimization. The user_id is automatically determined from the context.';

    protected array $properties = [
        'operation' => [
            'type' => 'string',
            'description' => 'The type of query to perform',
            'enum' => ['get_all', 'by_category', 'by_date_range', 'by_amount_range', 'spending_summary', 'top_expenses', 'category_breakdown', 'monthly_trend', 'list_categories'],
        ],
        'category_id' => [
            'type' => 'integer',
            'description' => 'Filter by category ID (for by_category operation)',
        ],
        'start_date' => [
            'type' => 'string',
            'description' => 'Start date for filtering (YYYY-MM-DD format, for by_date_range)',
        ],
        'end_date' => [
            'type' => 'string',
            'description' => 'End date for filtering (YYYY-MM-DD format, for by_date_range)',
        ],
        'min_amount' => [
            'type' => 'number',
            'description' => 'Minimum amount for filtering (for by_amount_range)',
        ],
        'max_amount' => [
            'type' => 'number',
            'description' => 'Maximum amount for filtering (for by_amount_range)',
        ],
        'limit' => [
            'type' => 'integer',
            'description' => 'Maximum number of records to return (default: 100, max: 1000)',
        ],
    ];

    protected array $required = ['operation'];

    public function execute(array $input): mixed
    {
        $operation = $input['operation'];
        $limit = min($input['limit'] ?? 100, 1000);
        
        // Get user_id from the global agent context
        // LarAgent passes metadata through app() container
        $userId = app('laragent.user_id') ?? null;
        
        if (!$userId) {
            return json_encode([
                'error' => 'User context not available. This tool requires user authentication.'
            ]);
        }

        try {
            Log::info('FinancialRecordsTool: Executing query', [
                'operation' => $operation,
                'user_id' => $userId,
            ]);

            switch ($operation) {
                case 'get_all':
                    return $this->getAllRecords($userId, $limit);

                case 'by_category':
                    if (!isset($input['category_id'])) {
                        return json_encode(['error' => 'category_id is required for by_category operation']);
                    }
                    return $this->getByCategory($userId, $input['category_id'], $limit);

                case 'by_date_range':
                    if (!isset($input['start_date']) || !isset($input['end_date'])) {
                        return json_encode(['error' => 'start_date and end_date are required for by_date_range operation']);
                    }
                    return $this->getByDateRange($userId, $input['start_date'], $input['end_date'], $limit);

                case 'by_amount_range':
                    if (!isset($input['min_amount']) || !isset($input['max_amount'])) {
                        return json_encode(['error' => 'min_amount and max_amount are required for by_amount_range operation']);
                    }
                    return $this->getByAmountRange($userId, $input['min_amount'], $input['max_amount'], $limit);

                case 'spending_summary':
                    return $this->getSpendingSummary($userId);

                case 'top_expenses':
                    return $this->getTopExpenses($userId, $limit);

                case 'category_breakdown':
                    return $this->getCategoryBreakdown($userId);

                case 'monthly_trend':
                    return $this->getMonthlyTrend($userId);

                case 'list_categories':
                    return $this->listCategories($userId);

                default:
                    return json_encode(['error' => 'Invalid operation. Must be one of: get_all, by_category, by_date_range, by_amount_range, spending_summary, top_expenses, category_breakdown, monthly_trend, list_categories']);
            }
        } catch (\Exception $e) {
            Log::error('FinancialRecordsTool: Query failed', [
                'operation' => $operation,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return json_encode([
                'error' => 'Query failed: ' . $e->getMessage(),
            ]);
        }
    }

    private function getAllRecords(int $userId, int $limit): string
    {
        $records = FinancialRecord::where('user_id', $userId)
            ->with('category')
            ->orderBy('date', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($record) {
                return [
                    'id' => $record->id,
                    'date' => $record->date->format('Y-m-d'),
                    'amount' => $record->amount,
                    'currency' => $record->currency,
                    'description' => $record->description,
                    'category' => $record->category->name ?? 'Uncategorized',
                    'category_id' => $record->category_id,
                    'record_type' => $record->record_type,
                    'integration_type' => $record->integration_type,
                ];
            });

        return json_encode([
            'success' => true,
            'operation' => 'get_all',
            'count' => $records->count(),
            'records' => $records,
        ], JSON_PRETTY_PRINT);
    }

    private function getByCategory(int $userId, int $categoryId, int $limit): string
    {
        $records = FinancialRecord::where('user_id', $userId)
            ->where('category_id', $categoryId)
            ->with('category')
            ->orderBy('date', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($record) {
                return [
                    'id' => $record->id,
                    'date' => $record->date->format('Y-m-d'),
                    'amount' => $record->amount,
                    'description' => $record->description,
                    'category' => $record->category->name ?? 'Uncategorized',
                ];
            });

        $totalAmount = FinancialRecord::where('user_id', $userId)
            ->where('category_id', $categoryId)
            ->sum('amount');

        return json_encode([
            'success' => true,
            'operation' => 'by_category',
            'category_id' => $categoryId,
            'count' => $records->count(),
            'total_amount' => $totalAmount,
            'records' => $records,
        ], JSON_PRETTY_PRINT);
    }

    private function getByDateRange(int $userId, string $startDate, string $endDate, int $limit): string
    {
        $records = FinancialRecord::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->with('category')
            ->orderBy('date', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($record) {
                return [
                    'id' => $record->id,
                    'date' => $record->date->format('Y-m-d'),
                    'amount' => $record->amount,
                    'description' => $record->description,
                    'category' => $record->category->name ?? 'Uncategorized',
                ];
            });

        $totalAmount = FinancialRecord::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        return json_encode([
            'success' => true,
            'operation' => 'by_date_range',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'count' => $records->count(),
            'total_amount' => $totalAmount,
            'records' => $records,
        ], JSON_PRETTY_PRINT);
    }

    private function getByAmountRange(int $userId, float $minAmount, float $maxAmount, int $limit): string
    {
        $records = FinancialRecord::where('user_id', $userId)
            ->whereBetween('amount', [$minAmount, $maxAmount])
            ->with('category')
            ->orderBy('amount', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($record) {
                return [
                    'id' => $record->id,
                    'date' => $record->date->format('Y-m-d'),
                    'amount' => $record->amount,
                    'description' => $record->description,
                    'category' => $record->category->name ?? 'Uncategorized',
                ];
            });

        return json_encode([
            'success' => true,
            'operation' => 'by_amount_range',
            'min_amount' => $minAmount,
            'max_amount' => $maxAmount,
            'count' => $records->count(),
            'records' => $records,
        ], JSON_PRETTY_PRINT);
    }

    private function getSpendingSummary(int $userId): string
    {
        $totalSpend = FinancialRecord::where('user_id', $userId)->sum('amount');
        $recordCount = FinancialRecord::where('user_id', $userId)->count();
        $averageTransaction = $recordCount > 0 ? $totalSpend / $recordCount : 0;

        $dateRange = FinancialRecord::where('user_id', $userId)
            ->selectRaw('MIN(date) as earliest, MAX(date) as latest')
            ->first();

        $categoryCounts = FinancialRecord::where('user_id', $userId)
            ->whereNotNull('category_id')
            ->select('category_id', DB::raw('count(*) as count'))
            ->groupBy('category_id')
            ->get()
            ->count();

        return json_encode([
            'success' => true,
            'operation' => 'spending_summary',
            'summary' => [
                'total_spend' => round($totalSpend, 2),
                'total_records' => $recordCount,
                'average_transaction' => round($averageTransaction, 2),
                'date_range' => [
                    'earliest' => $dateRange->earliest,
                    'latest' => $dateRange->latest,
                ],
                'categories_used' => $categoryCounts,
            ],
        ], JSON_PRETTY_PRINT);
    }

    private function getTopExpenses(int $userId, int $limit): string
    {
        $topExpenses = FinancialRecord::where('user_id', $userId)
            ->with('category')
            ->orderBy('amount', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($record) {
                return [
                    'id' => $record->id,
                    'date' => $record->date->format('Y-m-d'),
                    'amount' => $record->amount,
                    'description' => $record->description,
                    'category' => $record->category->name ?? 'Uncategorized',
                ];
            });

        return json_encode([
            'success' => true,
            'operation' => 'top_expenses',
            'count' => $topExpenses->count(),
            'top_expenses' => $topExpenses,
        ], JSON_PRETTY_PRINT);
    }

    private function getCategoryBreakdown(int $userId): string
    {
        $breakdown = FinancialRecord::where('user_id', $userId)
            ->with('category')
            ->get()
            ->groupBy('category_id')
            ->map(function ($records) {
                $category = $records->first()->category;
                return [
                    'category_id' => $records->first()->category_id,
                    'category_name' => $category->name ?? 'Uncategorized',
                    'total_amount' => $records->sum('amount'),
                    'transaction_count' => $records->count(),
                    'average_amount' => round($records->avg('amount'), 2),
                    'percentage' => 0, // Will calculate after
                ];
            })
            ->values();

        $totalSpend = $breakdown->sum('total_amount');

        // Calculate percentages
        $breakdown = $breakdown->map(function ($item) use ($totalSpend) {
            $item['percentage'] = $totalSpend > 0 ? round(($item['total_amount'] / $totalSpend) * 100, 2) : 0;
            return $item;
        })->sortByDesc('total_amount')->values();

        return json_encode([
            'success' => true,
            'operation' => 'category_breakdown',
            'total_spend' => round($totalSpend, 2),
            'category_count' => $breakdown->count(),
            'breakdown' => $breakdown,
        ], JSON_PRETTY_PRINT);
    }

    private function getMonthlyTrend(int $userId): string
    {
        // PostgreSQL-compatible date formatting
        $monthlyData = FinancialRecord::where('user_id', $userId)
            ->selectRaw("TO_CHAR(date, 'YYYY-MM') as month, SUM(amount) as total, COUNT(*) as count")
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(12)
            ->get()
            ->map(function ($item) {
                return [
                    'month' => $item->month,
                    'total_spend' => round($item->total, 2),
                    'transaction_count' => $item->count,
                    'average_transaction' => round($item->total / $item->count, 2),
                ];
            });

        return json_encode([
            'success' => true,
            'operation' => 'monthly_trend',
            'months_analyzed' => $monthlyData->count(),
            'trend' => $monthlyData,
        ], JSON_PRETTY_PRINT);
    }

    private function listCategories(int $userId): string
    {
        // Get all unique categories used by this user's financial records
        $categories = FinancialRecord::where('user_id', $userId)
            ->with('category')
            ->get()
            ->pluck('category')
            ->unique('id')
            ->filter() // Remove null categories
            ->map(function ($category) use ($userId) {
                // Get transaction count and total for this category
                $stats = FinancialRecord::where('user_id', $userId)
                    ->where('category_id', $category->id)
                    ->selectRaw('COUNT(*) as count, SUM(amount) as total')
                    ->first();

                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'description' => $category->description,
                    'transaction_count' => $stats->count ?? 0,
                    'total_amount' => round($stats->total ?? 0, 2),
                ];
            })
            ->sortByDesc('total_amount')
            ->values();

        return json_encode([
            'success' => true,
            'operation' => 'list_categories',
            'category_count' => $categories->count(),
            'categories' => $categories,
        ], JSON_PRETTY_PRINT);
    }
}
