<?php

namespace App\Tools\BaseLineAnalysis;

use DateInterval;
use DateTime;
use Illuminate\Support\Facades\Log;
use Vizra\VizraADK\Contracts\ToolInterface;
use Vizra\VizraADK\Memory\AgentMemory;
use Vizra\VizraADK\System\AgentContext;

class RollingAggregateTool implements ToolInterface
{
    /**
     * Get the tool's definition for the LLM.
     * This structure should be JSON schema compatible.
     */
    public function definition(): array
    {
        return [
            'name' => 'rolling_aggregate',
            'description' => 'This tool will calculate rolling aggregates for 7 days, 30 days, 90 days and 365 days.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'use_this' => [
                        'type' => 'true',
                        'description' => 'use this tool',
                    ],
                ],
                'required' => ['use_this'],

            ],
        ];
    }

    /**
     * Execute the tool's logic.
     *
     * @param  array  $arguments  Arguments provided by the LLM, matching the parameters defined above.
     * @param  AgentContext  $context  The current agent context, providing access to session state etc.
     * @return string JSON string representation of the tool's result.
     */
    public function execute(array $arguments, AgentContext $context, AgentMemory $memory): string
    {
        // Access arguments: $location = $arguments['location'] ?? null;
        // Access context: $sessionId = $context->getSessionId();
        // Access state: $previousValue = $context->getState('some_key');
        Log::info('Rolling Tool started...');

        $transactions = require __DIR__.'/mockData.php';

        $currentCash = 100000;
        $revenue_month = 50000;
        $expenses_month = 80000;

        $periods = [
            '7_days' => 7,
            '30_days' => 30,
            '45_days' => 45,
            '90_days' => 90,
            '365_days' => 365,
        ];

        $currentDate = new DateTime;

        // Normalize transaction dates
        foreach ($transactions as &$txn) {
            $txn['date_obj'] = DateTime::createFromFormat('Y-m-d', $this->normalizeDate($txn['date']));
        }
        unset($txn);

        // Sort transactions by date descending
        usort($transactions, function ($a, $b) {
            return $b['date_obj'] <=> $a['date_obj'];
        });

        $rollingAggregate = [];
        foreach ($periods as $label => $days) {
            $fromDate = (clone $currentDate)->sub(new DateInterval("P{$days}D"));
            $sum = 0;
            $count = 0;

            foreach ($transactions as $txn) {
                if ($txn['date_obj'] >= $fromDate && $txn['date_obj'] <= $currentDate) {
                    $sum += (float) $txn['price'];
                    $count++;
                }
            }

            $rollingAggregate[$label] = [
                'total' => $sum,
                'count' => $count,
            ];
        }

        $burn_rate_monthly = ($expenses_month - $revenue_month);
        $runway = $burn_rate_monthly > 0 ? ($currentCash / $burn_rate_monthly) : 0;

        $metrics = [
            'burn_rate' => $burn_rate_monthly,
            'runway' => $runway,
        ];

        $result = [
            'rollingAggregate' => $rollingAggregate,
            'metrics' => $metrics,
            'transactions' => $transactions,
        ];

        Log::info('result: ', $result);

        return json_encode($result);
    }

    /**
     * Normalize various date formats to Y-m-d.
     */
    private function normalizeDate(string $date): string
    {
        // Try Y-m-d first
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }
        // Try d-m-Y or d/m/Y
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $date, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }
        // Try m-d-Y or m/d/Y
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $date, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[1], $m[2]);
        }

        // Fallback: return as is
        return $date;
    }
}
