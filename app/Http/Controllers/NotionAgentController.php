<?php

namespace App\Http\Controllers;

use App\Agents\NotionAgent;
use App\Services\PipedreamToolLoader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class NotionAgentController extends Controller
{
    /**
     * Show the Notion agent chat page
     */
    public function index(): Response
    {
        return Inertia::render('notion-agent');
    }

    /**
     * Chat with the Notion agent
     */
    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string',
            'session_id' => 'nullable|string',
        ]);

        $user = $request->user();
        $message = $request->input('message');
        $sessionId = $request->input('session_id', 'notion_'.$user->id.'_'.time());

        // Check if Notion is connected
        $toolLoader = app(PipedreamToolLoader::class);
        $connectedApps = $toolLoader->getConnectedAppNames($user->id);

        if (! in_array('notion', $connectedApps)) {
            return response()->json([
                'success' => false,
                'error' => 'Notion account not connected. Please connect your Notion account in Settings > Integrations first.',
            ], 400);
        }

        try {
            // Execute agent using fluent API
            $response = NotionAgent::run($message)
                ->forUser($user)
                ->withSession($sessionId)
                ->go();

            // Extract text content from response (handles both string and object responses)
            $responseText = $this->extractResponseText($response);

            return response()->json([
                'success' => true,
                'response' => $responseText,
                'session_id' => $sessionId,
            ]);
        } catch (\Vizra\VizraADK\Exceptions\AgentExecutionException $e) {
            // Handle Vizra ADK specific errors
            $errorMessage = $e->getMessage();
            $previousException = $e->getPrevious();

            Log::error('NotionAgent execution error', [
                'user_id' => $user->id,
                'message' => $message,
                'error' => $errorMessage,
                'previous_error' => $previousException?->getMessage(),
                'previous_trace' => $previousException?->getTraceAsString(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Provide more user-friendly error message
            $userFriendlyError = 'Agent execution failed. ';
            if (str_contains($errorMessage, 'HTTP request returned status code 400')) {
                $userFriendlyError .= 'The request was invalid. This might be due to too many tools or an invalid configuration.';
            } elseif (str_contains($errorMessage, 'HTTP request returned status code 401')) {
                $userFriendlyError .= 'Authentication failed. Please check your API keys.';
            } elseif (str_contains($errorMessage, 'HTTP request returned status code 429')) {
                $userFriendlyError .= 'Rate limit exceeded. Please try again later.';
            } else {
                $userFriendlyError .= $errorMessage;
            }

            return response()->json([
                'success' => false,
                'error' => $userFriendlyError,
            ], 500);
        } catch (\Exception $e) {
            Log::error('NotionAgent chat error', [
                'user_id' => $user->id,
                'message' => $message,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Agent execution failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available Notion actions for the authenticated user
     */
    public function getAvailableActions(Request $request): JsonResponse
    {
        $user = $request->user();

        $toolLoader = app(PipedreamToolLoader::class);
        $summary = $toolLoader->getToolsSummary($user->id);

        // Filter for Notion only
        $notionSummary = collect($summary)->firstWhere('app_name', 'notion');

        return response()->json([
            'success' => true,
            'actions' => $notionSummary ? $notionSummary['tools'] : [],
            'count' => $notionSummary ? $notionSummary['tool_count'] : 0,
        ]);
    }

    /**
     * Extract text content from agent response (handles both string and object responses)
     */
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
