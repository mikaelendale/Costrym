<?php

namespace App\Services;

use App\Agents\BaseLineAgent;
use App\Agents\CategorizerAgent;
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

        $categorizer_response = CategorizerAgent::run('use company context')->go();

        // $parsedResponse = CleanUpResponse::extractJsonPayload($categorizer_response);

        Log::info('categorized_response', [
            'response' => $categorizer_response,
        ]);

        $baseline = BaseLineAgent::run('use company context for more category response'.$categorizer_response)->go();
        Log::info('baseline response', [
            'response' => $baseline,
        ]);

        // return $data;
    }
}
