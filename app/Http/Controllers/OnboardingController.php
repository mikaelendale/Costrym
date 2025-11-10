<?php

namespace App\Http\Controllers;

use App\Agents\OnboardingAgent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class OnboardingController extends Controller
{
    public function processCompanyInfo(Request $request): JsonResponse
    {
        $request->validate([
            'content' => 'required|string|min:100|max:200',
        ]);

        try {
            $content = $request->input('content');
            
            // Create a prompt for the agent
            $prompt = "Rewrite the company info to be concise, well-structured, and professional—no more than 3-4 lines. Summarize key details clearly and directly. no additional text or formatting, just the summary. and no emojis. no markdown formatting. just the summary. in a simple and direct way.\n\n" . $content;
            
            // Use the Vizra ADK fluent API to run the agent
            $agentCall = OnboardingAgent::run($prompt);
            
            // Add user context if authenticated
            if ($request->user()) {
                $agentCall->forUser($request->user());
            }
            
            $response = $agentCall->go();

            // Extract the text content from the response
            // The response might be a string or an object with content property
            $organizedContent = is_string($response) 
                ? $response 
                : (is_object($response) && isset($response->content) 
                    ? $response->content 
                    : (string) $response);

            return response()->json([
                'success' => true,
                'organized_content' => $organizedContent,
            ]);
        } catch (\Exception $e) {
            Log::error('Onboarding processing error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'content_length' => strlen($request->input('content', '')),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to process company information. Please try again.',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred while processing your request.',
            ], 500);
        }
    }
}

