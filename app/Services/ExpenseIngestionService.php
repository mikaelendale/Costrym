<?php

namespace App\Services;

use App\Agents\ApprovalAgent;
use App\Agents\AutomationPlanningAgent;
use App\Agents\BaseLineAgent;
use App\Agents\BenchmarkingAgent;
use App\Agents\CategorizerAgent;
use App\Agents\CERAgent;
use App\Agents\CostDecompositionAgent;
use App\Agents\CostOptomizerAgent\CostOptomizerAgent;
use App\Agents\CostValueAlignerAgent;
use App\AiAgents\ExpenseIngestionAgent;
use Illuminate\Support\Facades\Log;

class ExpenseIngestionService
{
    public function ingest($payload)
    {
        // $agent = ExpenseIngestionAgent::for('expense_ingestion');

        // $input = is_string($payload)
        //     ? $payload
        //     : json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        // $result = $agent->respond('you are given an xl sheet turned to json');
        // // Log::info('ai result', ['result' => $result]);

        // $data = is_array($result) ? $result : (json_decode((string) $result, true) ?? []);

        // Log::info('Expense ingestion agent result', [
        //     'expenses_count' => is_array($data['expenses'] ?? null) ? count($data['expenses']) : 0,
        //     $data ?? null,
        //     'errors' => $data['errors'] ?? [],
        // ]);

        // $categorizer_input = json_encode($data['expenses'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $mockdata = <<<'JSON'
{"expenses_count":9,"0":{"expenses":[{"expense_name":"Salaries & Wages","provider":null,"account_id":null,"txn_id":null,"timestamp":null,"amount":8400000,"currency":null,"merchant":null,"raw_description":"8400000","metadata":{"Category":"Expense","Debit (YTD)":"8400000","Credit (YTD)":"0","Balance (D-C)":"8400000","Account Code":"5100","Account Name":"Salaries & Wages"},"type":"debit"},{"expense_name":"Payroll Taxes & Benefits","provider":null,"account_id":null,"txn_id":null,"timestamp":null,"amount":840000,"currency":null,"merchant":null,"raw_description":"840000","metadata":{"Category":"Expense","Debit (YTD)":"840000","Credit (YTD)":"0","Balance (D-C)":"840000","Account Code":"5110","Account Name":"Payroll Taxes & Benefits"},"type":"debit"},{"expense_name":"Rent & Occupancy","provider":null,"account_id":null,"txn_id":null,"timestamp":null,"amount":240000,"currency":null,"merchant":null,"raw_description":"240000","metadata":{"Category":"Expense","Debit (YTD)":"240000","Credit (YTD)":"0","Balance (D-C)":"240000","Account Code":"5200","Account Name":"Rent & Occupancy"},"type":"debit"},{"expense_name":"Events & Demo Day","provider":null,"account_id":null,"txn_id":null,"timestamp":null,"amount":770000,"currency":null,"merchant":null,"raw_description":"770000","metadata":{"Category":"Expense","Debit (YTD)":"770000","Credit (YTD)":"0","Balance (D-C)":"770000","Account Code":"5300","Account Name":"Events & Demo Day"},"type":"debit"},{"expense_name":"Marketing & PR","provider":null,"account_id":null,"txn_id":null,"timestamp":null,"amount":329000,"currency":null,"merchant":null,"raw_description":"329000","metadata":{"Category":"Expense","Debit (YTD)":"329000","Credit (YTD)":"0","Balance (D-C)":"329000","Account Code":"5500","Account Name":"Marketing & PR"},"type":"debit"},{"expense_name":"Professional Fees","provider":null,"account_id":null,"txn_id":null,"timestamp":null,"amount":101000,"currency":null,"merchant":null,"raw_description":"181000","metadata":{"Category":"Expense","Debit (YTD)":"181000","Credit (YTD)":"80000","Balance (D-C)":"101000","Account Code":"5600","Account Name":"Professional Fees"},"type":"debit"},{"expense_name":"Software Subscriptions","provider":null,"account_id":null,"txn_id":null,"timestamp":null,"amount":144000,"currency":null,"merchant":null,"raw_description":"144000","metadata":{"Category":"Expense","Debit (YTD)":"144000","Credit (YTD)":"0","Balance (D-C)":"144000","Account Code":"5700","Account Name":"Software Subscriptions"},"type":"debit"},{"expense_name":"Depreciation Expense","provider":null,"account_id":null,"txn_id":null,"timestamp":null,"amount":24000,"currency":null,"merchant":null,"raw_description":"24000","metadata":{"Category":"Expense","Debit (YTD)":"24000","Credit (YTD)":"0","Balance (D-C)":"24000","Account Code":"5800","Account Name":"Depreciation Expense"},"type":"debit"},{"expense_name":"Miscellaneous Expense","provider":null,"account_id":null,"txn_id":null,"timestamp":null,"amount":102915,"currency":null,"merchant":null,"raw_description":"102915","metadata":{"Category":"Expense","Debit (YTD)":"102915","Credit (YTD)":"0","Balance (D-C)":"102915","Account Code":"5900","Account Name":"Miscellaneous Expense"},"type":"debit"}],"revenues":[{"revenue_name":"Program Fees Revenue","provider":null,"account_id":null,"txn_id":null,"timestamp":null,"amount":4600000,"currency":null,"customer":null,"raw_description":"4600000","metadata":{"Category":"Revenue","Debit (YTD)":"0","Credit (YTD)":"4600000","Balance (D-C)":"-4600000","Account Code":"4000","Account Name":"Program Fees Revenue"},"type":"sale"},{"revenue_name":"Sponsorship Revenue","provider":null,"account_id":null,"txn_id":null,"timestamp":null,"amount":600000,"currency":null,"customer":null,"raw_description":"600000","metadata":{"Category":"Revenue","Debit (YTD)":"0","Credit (YTD)":"600000","Balance (D-C)":"-600000","Account Code":"4010","Account Name":"Sponsorship Revenue"},"type":"sale"},{"revenue_name":"Service
 Revenue","provider":null,"account_id":null,"txn_id":null,"timestamp":null,"amount":240000,"currency":null,"customer":null,"raw_description":"240000","metadata":{"Category":"Revenue","Debit (YTD)":"0","Credit (YTD)":"240000","Balance (D-C)":"-240000","Account Code":"4020","Account Name":"Service Revenue"},"type":"sale"},{"revenue_name":"Realized Gains on Investments","provider":null,"account_id":null,"txn_id":null,"timestamp":null,"amount":2000000,"currency":null,"customer":null,"raw_description":"2000000","metadata":{"Category":"Revenue","Debit (YTD)":"0","Credit (YTD)":"2000000","Balance (D-C)":"-2000000","Account Code":"4100","Account Name":"Realized Gains on Investments"},"type":"sale"}],"other":[{"other_name":"Cash - Bank (DBS)","provider":null,"account_id":"1000","txn_id":null,"timestamp":null,"amount":null,"currency":null,"party":null,"raw_description":"-1499353","metadata":{"Category":"Asset","Debit (YTD)":"16996562","Credit (YTD)":"18495915","Balance (D-C)":"-1499353","Account Name":"Cash - Bank (DBS)"},"type":"balance"},{"other_name":"Cash - Stripe","provider":null,"account_id":"1010","txn_id":null,"timestamp":null,"amount":200000,"currency":null,"party":null,"raw_description":"200000","metadata":{"Category":"Asset","Debit (YTD)":"200000","Credit (YTD)":"0","Balance (D-C)":"200000","Account Name":"Cash - Stripe"},"type":"balance"},{"other_name":"Accounts Receivable","provider":null,"account_id":"1100","txn_id":null,"timestamp":null,"amount":null,"currency":null,"party":null,"raw_description":"-406562","metadata":{"Category":"Asset","Debit (YTD)":"346000","Credit (YTD)":"752562","Balance (D-C)":"-406562","Account Name":"Accounts Receivable"},"type":"balance"},{"other_name":"Investments - Held for Sale","provider":null,"account_id":"1200","txn_id":null,"timestamp":null,"amount":null,"currency":null,"party":null,"raw_description":"0","metadata":{"Category":"Asset","Debit (YTD)":"0","Credit (YTD)":"0","Balance (D-C)":"0","Account Name":"Investments - Held for Sale"},"type":"balance"},{"other_name":"Investments - At Cost (Portfolio)","provider":null,"account_id":"1210","txn_id":null,"timestamp":null,"amount":null,"currency":null,"party":null,"raw_description":"10800000","metadata":{"Category":"Asset","Debit (YTD)":"13200000","Credit (YTD)":"2400000","Balance (D-C)":"10800000","Account Name":"Investments - At Cost (Portfolio)"},"type":"balance"},{"other_name":"Prepaid Expenses","provider":null,"account_id":"1300","txn_id":null,"timestamp":null,"amount":null,"currency":null,"party":null,"raw_description":"0","metadata":{"Category":"Asset","Debit (YTD)":"0","Credit (YTD)":"0","Balance (D-C)":"0","Account Name":"Prepaid Expenses"},"type":"balance"},{"other_name":"Fixed Assets (Net)","provider":null,"account_id":"1400","txn_id":null,"timestamp":null,"amount":null,"currency":null,"party":null,"raw_description":"226000","metadata":{"Category":"Asset","Debit (YTD)":"250000","Credit (YTD)":"24000","Balance (D-C)":"226000","Account Name":"Fixed Assets (Net)"},"type":"balance"},{"other_name":"Accounts Payable","provider":null,"account_id":"2000","txn_id":null,"timestamp":null,"amount":null,"currency":null,"party":null,"raw_description":"-171000","metadata":{"Category":"Liability","Debit (YTD)":"360000","Credit (YTD)":"531000","Balance (D-C)":"-171000","Account Name":"Accounts Payable"},"type":"balance"},{"other_name":"Deferred Revenue","provider":null,"account_id":"2100","txn_id":null,"timestamp":null,"amount":null,"currency":null,"party":null,"raw_description":"-5000000","metadata":{"Category":"Liability","Debit (YTD)":"0","Credit (YTD)":"5000000","Balance (D-C)":"-5000000","Account Name":"Deferred Revenue"},"type":"balance"},{"other_name":"Accrued Expenses","provider":null,"account_id":"2200","txn_id":null,"timestamp":null,"amount":null,"currency":null,"party":null,"raw_description":"-760000","metadata":{"Category":"Liability","Debit (YTD)":"80000","Credit (YTD)":"840000","Balance (D-C)":"-760000","Account Name":"Accrued Expenses"},"type":"balance"},{"other_name":"Common
 Stock","provider":null,"account_id":"3000","txn_id":null,"timestamp":null,"amount":null,"currency":null,"party":null,"raw_description":"0","metadata":{"Category":"Equity","Debit (YTD)":"0","Credit (YTD)":"0","Balance (D-C)":"0","Account Name":"Common Stock"},"type":"balance"},{"other_name":"Retained Earnings","provider":null,"account_id":"3100","txn_id":null,"timestamp":null,"amount":null,"currency":null,"party":null,"raw_description":"-5450000","metadata":{"Category":"Equity","Debit (YTD)":"0","Credit (YTD)":"5450000","Balance (D-C)":"-5450000","Account Name":"Retained Earnings"},"type":"balance"}],"errors":[]},"errors":[]}


JSON;

        $mockcompanyprofile = <<<'JSON'
 {
  "company_name": "Innovate Ventures Inc.",
  "company_description": "A leading accelerator program for early-stage startups, providing funding, mentorship, and resources to foster innovation and growth.",
  "product_name": "Seedling Accelerator Program",
  "product_description": "Our flagship program offers a structured curriculum, access to a network of mentors and investors, and initial seed funding to help startups launch and scale.",
 }
JSON;

        $categorizer_response = CategorizerAgent::run($mockdata)->go();

        // $parsedResponse = CleanUpResponse::extractJsonPayload($categorizer_response);

        Log::info('categorized_response', [
            'response' => $categorizer_response,
        ]);

        $baseline = BaseLineAgent::run('use company context for more category response'.$categorizer_response)->go();
        // Normalize BaselineAgent output to compact JSON without tabs/newlines for cleaner logs
        try {
            $baselineArray = CleanUpResponse::extractJsonPayload($baseline);
            $baselineCompact = json_encode($baselineArray, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (\Throwable $e) {
            // Fallback: if not valid JSON, at least strip tab characters
            $baselineCompact = is_string($baseline) ? str_replace("\t", '', (string) $baseline) : $baseline;
        }

        Log::info('baseline response', [
            'response' => $baselineCompact,
        ]);

        $costdecomposition = CostDecompositionAgent::run('use categorized expense to decompose costs '.$categorizer_response)->go();
        Log::info('costdecomposition response', [
            'response' => $costdecomposition,
        ]);

        $parsed_categorizer_response = CleanUpResponse::extractJsonPayload($categorizer_response);
        Log::info('parsed categorizer response', [
            'response' => $parsed_categorizer_response,
        ]);

        $opexBreakdown = $this->logOpexPercentages($parsed_categorizer_response);

        // Return only the by-category percent mapping and log it for later use
        $byCategory = $opexBreakdown['by_category_percent'] ?? [];
        Log::info('opex_by_category_percent', ['by_category_percent' => $byCategory]);

        $benchmark = BenchmarkingAgent::run($mockcompanyprofile)->go();
        Log::info('benchmarking response', [
            'response' => $benchmark,
        ]);

        sleep(60);
        // Build a clean JSON payload for CERAgent: include actual OPEX by category percent and the raw benchmark text
        $cerInput = [
            'actual_opex' => $byCategory, // map of Category => percent
            'benchmark' => $benchmark,    // CER agent will parse into a map
        ];
        $cerPayload = json_encode($cerInput, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $cerResponse = CERAgent::run($cerPayload)->go();
        Log::info('cer response', [
            'response' => $cerResponse,
        ]);

        $cutcostoptimizer = CostOptomizerAgent::run(' categoryAgentResponse: '.$categorizer_response.'benchMarkData'.$cerResponse)->go();

        Log::info('cutcostoptimizer response', [
            'response' => $cutcostoptimizer,
        ]);

        sleep(60);
        $costAllignmantresponse = CostValueAlignerAgent::run('cutcostoptimizer: '.$cutcostoptimizer)->go();

        Log::info('cutcostaligner response', [
            'response' => $costAllignmantresponse,
        ]);

        $automations = AutomationPlanningAgent::run($costAllignmantresponse)->go();
        Log::info('automation planning response', [
            'response' => $automations,
        ]);

        $approvalLayer = ApprovalAgent::run(input: $automations)->go();
        Log::info('approval layer response', [
            'response' => $approvalLayer,
        ]);

        return $byCategory;
    }

    /**
     * Compute and log OPEX (operational expenses) percentages over total OPEX.
     * - Groups by expense_name and by tags (e.g., Direct/Indirect/Uncategorized)
     */
    protected function logOpexPercentages(array $parsed_categorizer_response): array
    {
        try {
            // Locate expenses array from the parsed response
            $categoryPayload = $parsed_categorizer_response['category'] ?? [];
            $expenses = [];
            if (is_array($categoryPayload) && isset($categoryPayload['expenses']) && is_array($categoryPayload['expenses'])) {
                $expenses = $categoryPayload['expenses'];
            } elseif (isset($parsed_categorizer_response['expenses']) && is_array($parsed_categorizer_response['expenses'])) {
                // fallback if structure is different
                $expenses = $parsed_categorizer_response['expenses'];
            }

            // Treat all parsed expenses as OPEX items (domain categories like 'Payroll & Compensation', 'Marketing', etc.)
            $opex = is_array($expenses) ? array_values($expenses) : [];

            // Keep only positive amounts to avoid skew from refunds/negatives
            $opex = array_values(array_filter($opex, function ($e) {
                return (float) ($e['amount'] ?? 0) > 0;
            }));

            // Total OPEX cost
            $totalOpex = 0.0;
            foreach ($opex as $e) {
                $amt = (float) ($e['amount'] ?? 0);
                $totalOpex += max(0, $amt);
            }

            // Guard against division by zero
            if ($totalOpex <= 0) {
                Log::info('opex_percentage_over_total', [
                    'note' => 'No OPEX amounts found (total is 0).',
                    'total_opex' => $totalOpex,
                    'by_category_percent' => [],
                    'by_expense_name_percent' => [],
                    'by_tag_percent' => [],
                ]);

                return [
                    'total_opex' => $totalOpex,
                    'by_category_percent' => [],
                    'by_expense_name_percent' => [],
                    'by_tag_percent' => [],
                    'compact_by_category' => '',
                ];
            }

            // Group by expense_name
            $sumByName = [];
            foreach ($opex as $e) {
                $name = trim((string) ($e['expense_name'] ?? 'Unknown')) ?: 'Unknown';
                $amt = max(0, (float) ($e['amount'] ?? 0));
                $sumByName[$name] = ($sumByName[$name] ?? 0) + $amt;
            }

            $percentByName = [];
            foreach ($sumByName as $name => $sum) {
                $percentByName[$name] = round(($sum / $totalOpex) * 100, 2);
            }
            arsort($percentByName); // highest first

            // Group by domain category (e.g., 'Payroll & Compensation', 'Marketing', ...)
            $sumByDomainCategory = [];
            foreach ($opex as $e) {
                $amt = max(0, (float) ($e['amount'] ?? 0));
                $cat = trim((string) ($e['category'] ?? 'Uncategorized')) ?: 'Uncategorized';
                $sumByDomainCategory[$cat] = ($sumByDomainCategory[$cat] ?? 0) + $amt;
            }

            $percentByDomainCategory = [];
            foreach ($sumByDomainCategory as $cat => $sum) {
                $percentByDomainCategory[$cat] = round(($sum / $totalOpex) * 100, 2);
            }
            arsort($percentByDomainCategory);

            // Group by tag (Direct/Indirect/Uncategorized). If multiple tags, split amount evenly across them.
            $sumByTag = [];
            foreach ($opex as $e) {
                $amt = max(0, (float) ($e['amount'] ?? 0));
                $tags = $e['tags'] ?? [];
                if (! is_array($tags) || count($tags) === 0) {
                    $sumByTag['Uncategorized'] = ($sumByTag['Uncategorized'] ?? 0) + $amt;

                    continue;
                }
                $split = $amt / max(1, count($tags));
                foreach ($tags as $tag) {
                    $label = trim((string) $tag) ?: 'Uncategorized';
                    $sumByTag[$label] = ($sumByTag[$label] ?? 0) + $split;
                }
            }

            $percentByTag = [];
            foreach ($sumByTag as $tag => $sum) {
                $percentByTag[$tag] = round(($sum / $totalOpex) * 100, 2);
            }
            arsort($percentByTag);

            // Detailed arrays
            Log::info('opex_percentage_over_total', [
                'total_opex' => $totalOpex,
                'by_category_percent' => $percentByDomainCategory,
                'by_expense_name_percent' => $percentByName,
                'by_tag_percent' => $percentByTag,
            ]);

            // Compact mapping log using domain categories (e.g., "Payroll & Compensation -> 78.0")
            $compact = [];
            foreach ($percentByDomainCategory as $cat => $pct) {
                $compact[] = $cat.' -> '.$pct;
            }
            Log::info('opex_percentages_compact', ['mapping' => implode(', ', $compact)]);

            return [
                'total_opex' => $totalOpex,
                'by_category_percent' => $percentByDomainCategory,
                'by_expense_name_percent' => $percentByName,
                'by_tag_percent' => $percentByTag,
                'compact_by_category' => implode(', ', $compact),
            ];
        } catch (\Throwable $e) {
            Log::warning('Failed to compute OPEX percentages', [
                'error' => $e->getMessage(),
            ]);

            return [
                'total_opex' => 0,
                'by_category_percent' => [],
                'by_expense_name_percent' => [],
                'by_tag_percent' => [],
                'compact_by_category' => '',
            ];
        }
    }
}
