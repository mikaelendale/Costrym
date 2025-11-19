<?php

namespace App\Http\Controllers;

use App\Agents\NotionAgent;
use App\Services\PipedreamToolLoader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Vizra\VizraADK\System\AgentContext;

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
            // Create agent instance
            $agent = new NotionAgent;

            // Create context with user ID
            $context = new AgentContext($sessionId);
            $context->setState('user_id', $user->id);

            // Execute agent
            $response = $agent->execute($message, $context);

            return response()->json([
                'success' => true,
                'response' => $response,
                'session_id' => $sessionId,
            ]);
        } catch (\Exception $e) {
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
}
