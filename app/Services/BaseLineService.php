<?php

namespace App\Services;

use App\Agents\BaseLineAgent;
use App\Repositories\BaseLineRepository;
use App\Repositories\ExpenseRepository;
use Illuminate\Support\Facades\Log;

class BaseLineService
{
    public function __construct(
        private ExpenseRepository $expense,
        private BaseLineRepository $baseline
    ) {
        //
    }

    public function run(int $userId)
    {
        $rawExpense = $this->expense->getExpense($userId) ?? [];

        // Safely encode expense data for prompt (avoid array to string conversion)
        if (is_array($rawExpense) || $rawExpense instanceof \JsonSerializable || $rawExpense instanceof \Illuminate\Support\Collection) {
            $expenseForPrompt = json_encode($rawExpense, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            $expenseForPrompt = (string) $rawExpense;
        }

        $prompt = "use company context for more category response: $expenseForPrompt";

        Log::info('BaseLineJob: prepared baseline prompt', [
            'prompt_length' => strlen($prompt),
        ]);

        $baselineResponse = BaseLineAgent::run($prompt)->go();

        Log::info('BaseLineJob: raw baseline agent response received', [
            'response' => $baselineResponse,
        ]);

        // Defensive: if the LLM returned an empty string, bail early to avoid parsing errors.
        if (! is_string($baselineResponse) || trim($baselineResponse) === '') {
            Log::warning('BaseLineJob: LLM returned empty response, aborting baseline parsing');

            return [];
        }

        try {
            $parsed = CleanUpResponse::extractJsonPayload($baselineResponse);
            $data = $parsed['base_line_response'] ?? [];
        } catch (\Throwable $e) {
            Log::warning('BaseLineJob: failed to parse baseline response, storing empty array', [
                'error' => $e->getMessage(),
            ]);
            $data = [];

            return [];
        }

        $this->baseline->update($data, $userId);

        Log::info('BaseLineJob: baseline data persisted', [
            'items' => is_array($data) ? count($data) : 0,
        ]);

        return $parsed;
    }
}
