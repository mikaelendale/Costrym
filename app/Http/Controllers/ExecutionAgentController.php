<?php

namespace App\Http\Controllers;

use App\Agents\ExecutionAgent;
use App\Agents\IntegrationIngestor;
use App\Services\PipedreamToolLoader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ExecutionAgentController extends Controller
{
    /**
     * Show the minimal test page
     */
    public function index(): Response
    {
        return Inertia::render('execution-agent');
    }

    /**
     * Endpoint to execute a structured instruction against the ExecutionAgent
     * Expect JSON payload: { "tool": "notion"|"xero_accounting_api", "instruction": "...", "params": { ... } }
     */
    public function execute(Request $request): JsonResponse
    {
        $request->validate([
            'tool' => 'required|string',
            'instruction' => 'required|string',
            'params' => 'sometimes|array',
        ]);

        $user = $request->user();

        if (! $user) {
            return response()->json(['success' => false, 'error' => 'Unauthenticated'], 401);
        }

        // Check if user has Xero connected (IntegrationIngestor currently only supports Xero)
        $toolLoader = app(PipedreamToolLoader::class);
        $connectedApps = $toolLoader->getConnectedAppNames($user->id);
        if (! in_array('xero_accounting_api', $connectedApps)) {
            return response()->json([
                'success' => false,
                'error' => 'Xero account not connected. Please connect your Xero account in Settings > Integrations first. (Note: IntegrationIngestor currently only supports Xero)',
            ], 400);
        }

        $tool = $request->input('tool');
        $instruction = $request->input('instruction');
        $params = $request->input('params', []);

        $sessionId = 'execution_agent_'.$user->id.'_'.time();

        try {
            // $response = ExecutionAgent::run($instruction)
            //     ->forUser($user)
            //     ->withSession($sessionId)
            //     ->go();

            $response = ExecutionAgent::run($instruction)->forUser($user)
                ->withSession($sessionId)
                ->go();

            // Normalize response to string
            $responseText = $this->extractResponseText($response);

            return response()->json([
                'success' => true,
                'response' => $responseText,
                'session_id' => $sessionId,
            ]);

        } catch (\Throwable $e) {
            Log::error('ExecutionAgentController execution error', [
                'user_id' => $user->id,
                'tool' => $tool,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function extractResponseText(mixed $response): string
    {
        if (is_string($response)) {
            return $response;
        }

        // Handle Prism Response objects
        if (is_object($response)) {
            if (method_exists($response, 'text')) {
                return (string) $response->text;
            }
            if (property_exists($response, 'text')) {
                return (string) $response->text;
            }
            if (method_exists($response, 'content')) {
                return (string) $response->content;
            }
            if (property_exists($response, 'content')) {
                return (string) $response->content;
            }
            if (method_exists($response, '__toString')) {
                return (string) $response;
            }
        }

        // Handle arrays
        if (is_array($response)) {
            if (isset($response['text'])) {
                return (string) $response['text'];
            }
            if (isset($response['content'])) {
                return (string) $response['content'];
            }
            if (isset($response['message']) && is_string($response['message'])) {
                return (string) $response['message'];
            }
        }

        // Fallback: convert to JSON string
        return json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
