<?php

namespace App\Http\Controllers;

use App\AiAgents\ChatAgent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ChatController extends Controller
{
    /**
     * Show the Chat page
     */
    public function index(): Response
    {
        return Inertia::render('chat');
    }

    /**
     * Chat with the AI agent using LarAgent
     */
    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string',
            'session_id' => 'nullable|string',
        ]);

        $user = $request->user();
        $message = $request->input('message');
        $sessionId = $request->input('session_id', 'chat_'.$user->id.'_'.time());

        try {
            // Bind user_id to service container for tools to access
            app()->instance('laragent.user_id', $user->id);

            // Create session-based agent instance for conversation continuity
            $agent = ChatAgent::for($sessionId);

            // Get response from agent
            $response = $agent->respond($message);

            // Extract text content from response
            $responseText = $this->extractResponseText($response);

            return response()->json([
                'success' => true,
                'response' => $responseText,
                'session_id' => $sessionId,
            ]);
        } catch (\Exception $e) {
            Log::error('ChatAgent chat error', [
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
     * Extract text content from agent response (handles both string and array responses)
     */
    private function extractResponseText(mixed $response): string
    {
        if (is_string($response)) {
            return $response;
        }

        // Handle arrays
        if (is_array($response)) {
            if (isset($response['text'])) {
                return (string) $response['text'];
            }
            if (isset($response['content'])) {
                return (string) $response['content'];
            }
            if (isset($response['response'])) {
                return (string) $response['response'];
            }
            if (isset($response['message']) && is_string($response['message'])) {
                return (string) $response['message'];
            }
        }

        // Fallback: convert to string
        return (string) $response;
    }
}
