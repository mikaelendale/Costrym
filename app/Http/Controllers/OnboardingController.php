<?php

namespace App\Http\Controllers;

use App\AiAgents\FinanceFileAnalystAgent;
use App\AiAgents\OnboardingAgent;
use App\AiAgents\OnboardingEstimationAgent;
use App\Jobs\DataIngestionJob;
use App\Models\KnowledgeBase;
use App\Services\ExcelToJsonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

/**
 * Onboarding Controller
 *
 * Handles the multi-step onboarding flow for new users:
 * 1. Company information processing
 * 2. AI-powered chat conversation
 * 3. Value proposition estimation
 * 4. Onboarding completion
 *
 * Features:
 * - AI agent integration for intelligent conversations
 * - Knowledge base persistence for user context
 * - Graceful error handling and logging
 */
class OnboardingController extends Controller
{
    public function __construct(
        private ExcelToJsonService $excelService
    ) {}

    // ============================================================================
    // AI CHAT CONVERSATION
    // ============================================================================

    /**
     * Handles AI-powered chat conversation during onboarding.
     *
     * Uses OnboardingAgent with structured output to maintain conversation context
     * and extract key information about the user's company. The agent automatically
     * manages conversation history using a session-based approach.
     *
     * Flow:
     * 1. User sends message
     * 2. AI agent processes with conversation context
     * 3. Returns structured response with understanding and completion status
     * 4. When complete, triggers transition to next step
     *
     * @param  Request  $request  HTTP request containing user message and conversation history
     * @return JsonResponse AI response with understanding, organized content, and completion status
     */
    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'conversation_history' => 'nullable|array',
        ]);

        try {
            $userMessage = $request->input('message');
            $userId = $request->user()?->id ?? $request->ip();

            // Get previous understanding from request to help agent accumulate information
            $previousUnderstanding = $request->input('previous_understanding', '');

            // Create session-based agent instance for conversation continuity
            // LarAgent automatically manages conversation history via session ID
            $sessionId = 'onboarding_'.$userId;
            $agent = OnboardingAgent::for($sessionId);

            // Enhance message with previous understanding context to ensure accumulation
            $enhancedMessage = $userMessage;
            if (! empty($previousUnderstanding)) {
                $enhancedMessage = "Previous understanding so far:\n".$previousUnderstanding."\n\n".
                    'New user message: '.$userMessage."\n\n".
                    'IMPORTANT: Update the understanding field to include ALL previous information PLUS any new information from this message. Do NOT delete or replace previous information.';
            }

            // Get structured response (automatically parsed by LarAgent)
            $response = $agent->respond($enhancedMessage);

            // Extract organized content if available
            $organizedContent = $response['organized_content'] ?? '';

            return response()->json([
                'success' => true,
                'response' => $response['response'] ?? '',
                'understanding' => $response['understanding'] ?? '',
                'complete' => $response['complete'] ?? false,
                'organized_content' => ! empty($organizedContent) ? $organizedContent : null,
            ]);
        } catch (\Exception $e) {
            Log::error('Onboarding chat error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'message_length' => strlen($request->input('message', '')),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to process message. Please try again.',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred while processing your request.',
            ], 500);
        }
    }

    // ============================================================================
    // VALUE PROPOSITION ESTIMATION
    // ============================================================================

    /**
     * Generates AI-powered value proposition estimation for the user.
     *
     * Creates a personalized estimation based on:
     * - User's company information from knowledge base
     * - Industry-specific benchmarks
     * - Realistic savings projections
     *
     * The estimation is displayed in step 3 of onboarding to show users
     * the potential value Costrym can provide for their specific business.
     *
     * @param  Request  $request  HTTP request containing understanding and organized content
     * @return JsonResponse AI-generated value proposition estimation
     */
    public function estimation(Request $request): JsonResponse
    {
        $request->validate([
            'understanding' => 'nullable|string',
            'organized_content' => 'nullable|string',
        ]);

        try {
            $user = $request->user();
            $userId = $user?->id ?? $request->ip();

            // Get user's company information from knowledge base
            $knowledgeBase = KnowledgeBase::where('user_id', $user?->id)->first();
            $context = $knowledgeBase?->context ?? [];

            // Build prompt with company information
            $prompt = 'Based on the company information below, provide a concise 2-3 line estimation. ';
            $prompt .= 'First line: What Costrym can specifically save for this business in their industry (be specific with dollar amounts). ';
            $prompt .= 'Second line: How much competitors in their industry are saving with Costrym (provide realistic benchmark numbers). ';
            $prompt .= 'Third line (optional): One key cost optimization opportunity for their business type. ';
            $prompt .= "Be factual, specific, and realistic. No fluff, just numbers and facts. Format as 2-3 short sentences, no markdown.\n\n";

            if (! empty($context['understanding'])) {
                $prompt .= "Company Understanding:\n".$context['understanding']."\n\n";
            }

            if (! empty($context['organized_content'])) {
                $prompt .= "Company Summary:\n".$context['organized_content']."\n\n";
            }

            if (empty($context)) {
                $prompt .= 'Note: Limited company information available. Provide a general industry-based estimation with typical savings ranges.';
            }

            // Use OnboardingEstimationAgent
            $sessionId = 'estimation_'.$userId;
            $agent = OnboardingEstimationAgent::for($sessionId);

            // Disable structured output for free-form text
            $agent->responseSchema(null);

            $response = $agent->respond($prompt);

            // Extract text content
            $content = is_string($response)
                ? $response
                : (is_array($response) && isset($response['response'])
                    ? $response['response']
                    : (string) $response);

            return response()->json([
                'success' => true,
                'content' => trim($content),
            ]);
        } catch (\Exception $e) {
            Log::error('Estimation generation error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to generate estimation. Please try again.',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred.',
            ], 500);
        }
    }

    // ============================================================================
    // PLAN SELECTION & SUBSCRIPTION
    // ============================================================================

    /**
     * Handle plan selection during onboarding and redirect to Stripe checkout.
     *
     * @param  Request  $request  HTTP request containing the selected plan
     * @return \Illuminate\Http\RedirectResponse Redirects to Stripe checkout
     */
    public function selectPlan(Request $request)
    {
        $request->validate([
            'plan' => 'required|string|in:startup-monthly,startup-annual,enterprise-annual',
        ]);

        try {
            $user = $request->user();
            $plan = $request->input('plan');

            // Map plan IDs to Stripe price IDs
            $priceMap = [
                'startup-monthly' => env('STRIPE_PRICE_STARTER_MONTHLY'),
                'startup-annual' => env('STRIPE_PRICE_STARTER_ANNUAL'),
                'enterprise-annual' => env('STRIPE_PRICE_ENTERPRISE_ANNUAL'),
            ];

            $priceId = $priceMap[$plan] ?? null;

            if (!$priceId) {
                return back()->withErrors(['error' => 'Invalid plan selected']);
            }

            // Save plan preference
            $user->plan = $plan;
            $user->save();

            // Create Stripe checkout session
            $checkout = $user->newSubscription('default', $priceId);
            
            // Apply coupon if enabled
            if (env('STRIPE_COUPONS_ENABLED', false)) {
                $couponMap = [
                    'startup-monthly' => env('STRIPE_COUPON_STARTER_MONTHLY'),
                    'startup-annual' => env('STRIPE_COUPON_STARTER_ANNUAL'),
                    'enterprise-annual' => env('STRIPE_COUPON_ENTERPRISE_ANNUAL'),
                ];
                
                $couponCode = $couponMap[$plan] ?? null;
                if ($couponCode) {
                    $checkout->withCoupon($couponCode);
                }
            } else {
                // Allow user to enter their own promo codes
                $checkout->allowPromotionCodes();
            }
            
            $checkout = $checkout->checkout([
                'success_url' => route('onboarding') . '?session_id={CHECKOUT_SESSION_ID}&plan=' . $plan,
                'cancel_url' => route('onboarding') . '?cancelled=1',
            ]);

            return $checkout;
        } catch (\Exception $e) {
            Log::error('Plan selection error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'plan' => $request->input('plan'),
            ]);

            return back()->withErrors(['error' => 'Failed to process plan selection. Please try again.']);
        }
    }

    /**
     * Check subscription status (called after returning from Stripe checkout).
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function checkSubscriptionStatus(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            return response()->json([
                'success' => true,
                'subscribed' => $user->subscribed('default'),
                'plan' => $user->subscription('default')?->stripe_price ?? null,
                'current_plan' => $user->plan,
            ]);
        } catch (\Exception $e) {
            Log::error('Subscription status check error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to check subscription status',
            ], 500);
        }
    }

    // ============================================================================
    // ONBOARDING COMPLETION
    // ============================================================================

    /**
     * Completes onboarding and saves learned data to knowledge base.
     *
     * Persists:
     * - AI understanding of user's company
     * - Organized company content
     * - Marks user's onboarding as complete
     *
     * This data is used throughout the application to provide personalized
     * experiences and recommendations.
     *
     * @param  Request  $request  HTTP request containing understanding and organized content
     * @return RedirectResponse Redirects to dashboard with success message
     */
    public function complete(Request $request): RedirectResponse
    {
        $request->validate([
            'understanding' => 'nullable|string',
            'organized_content' => 'nullable|string',
            'json_file' => 'nullable|string',
        ]);

        try {
            $user = $request->user();
            // Build context from onboarding data
            $context = [];
            if ($request->input('understanding')) {
                $context['understanding'] = $request->input('understanding');
            }
            if ($request->input('organized_content')) {
                $context['organized_content'] = $request->input('organized_content');
            }

            // Save to knowledge base
            if (! empty($context)) {
                KnowledgeBase::updateOrCreate(
                    [
                        'user_id' => $user->id,
                    ],
                    [
                        'context' => $context,
                    ]
                );
            }

            // Mark onboarding as complete
            $user->onboarding_status = true;
            $user->save();

            // Check for uploaded JSON file
            $jsonFilePath = null;
            if ($request->input('json_file')) {
                // Security: ensure filename is just a basename to prevent traversal
                $filename = basename($request->input('json_file'));
                $potentialPath = 'financial_data/'.$user->id.'/'.$filename;
                
                Log::info('Onboarding completion: Checking for JSON file', [
                    'user_id' => $user->id,
                    'json_file_input' => $request->input('json_file'),
                    'filename' => $filename,
                    'potential_path' => $potentialPath,
                ]);
                
                if (Storage::disk('local')->exists($potentialPath)) {
                    $jsonFilePath = $potentialPath;
                    Log::info('Onboarding completion: JSON file found', [
                        'user_id' => $user->id,
                        'path' => $jsonFilePath,
                    ]);
                } else {
                    Log::warning('Onboarding completion: JSON file specified but not found', [
                        'user_id' => $user->id,
                        'path' => $potentialPath,
                        'all_storage_files' => Storage::disk('local')->files('financial_data/'.$user->id),
                    ]);
                }
            } else {
                Log::info('Onboarding completion: No JSON file in request', [
                    'user_id' => $user->id,
                    'all_inputs' => $request->all(),
                ]);
            }

            // Dispatch DataIngestionJob to fetch financial data from connected integrations
            // MasterOrchestratorJob will be dispatched automatically after ingestion completes
            DataIngestionJob::dispatch($user->id, isInitialSync: true, jsonFilePath: $jsonFilePath);

            Log::info('Onboarding completed, DataIngestionJob dispatched', [
                'user_id' => $user->id,
                'has_json_file' => ! is_null($jsonFilePath),
                'json_file_path' => $jsonFilePath,
                'note' => 'MasterOrchestratorJob will run after data ingestion completes',
            ]);

            return redirect()->route('dashboard')->with('success', 'Onboarding completed successfully');
        } catch (\Exception $e) {
            Log::error('Onboarding completion error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Failed to complete onboarding. Please try again.');
        }
    }

    // ============================================================================
    // FINANCIAL DATA FILE UPLOAD
    // ============================================================================

    /**
     * Handles financial data file upload (CSV/Excel) with AI analysis.
     *
     * Flow:
     * 1. Upload and validate file (max 100MB)
     * 2. Convert Excel/CSV to JSON
     * 3. Store JSON file in private storage
     * 4. Analyze with AI agent to check transaction requirements
     * 5. Return analysis results
     *
     * @param  Request  $request  HTTP request containing the uploaded file
     * @return JsonResponse Upload status, session ID for progress tracking, and analysis results
     */
    public function uploadFinancialData(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:102400', // Max 100MB
            'type' => 'required|string|in:financial_data',
        ]);

        try {
            $user = $request->user();

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not authenticated',
                ], 401);
            }

            // Increase execution time and memory for large files
            set_time_limit(600); // 10 minutes
            ini_set('memory_limit', '1024M');

            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $fileSize = $file->getSize();

            // Generate session ID for progress tracking
            $sessionId = 'upload_'.$user->id.'_'.time();

            // Initialize progress tracking
            Cache::put("upload_progress_{$sessionId}", [
                'status' => 'uploading',
                'progress' => 0,
                'message' => 'Uploading file...',
            ], 600); // 10 minutes

            Log::info('Financial data file upload started', [
                'user_id' => $user->id,
                'file_name' => $originalName,
                'file_size' => $fileSize,
                'session_id' => $sessionId,
            ]);

            // Store original file temporarily
            $tempFilePath = $file->getRealPath();

            // Update progress: Converting to JSON
            Cache::put("upload_progress_{$sessionId}", [
                'status' => 'converting',
                'progress' => 30,
                'message' => 'Converting file to JSON format...',
            ], 600);

            // Convert Excel/CSV to JSON
            $conversionResult = $this->excelService->convertToJson($tempFilePath);

            if (! $conversionResult['success']) {
                Cache::put("upload_progress_{$sessionId}", [
                    'status' => 'error',
                    'progress' => 0,
                    'message' => 'Failed to convert file: '.($conversionResult['error'] ?? 'Unknown error'),
                ], 600);

                return response()->json([
                    'success' => false,
                    'error' => 'Failed to convert file: '.($conversionResult['error'] ?? 'Unknown error'),
                ], 500);
            }

            // Update progress: Storing JSON file
            Cache::put("upload_progress_{$sessionId}", [
                'status' => 'storing',
                'progress' => 50,
                'message' => 'Storing processed data...',
            ], 600);

            // Store JSON file in local storage
            $jsonFileName = 'financial_data_'.$user->id.'_'.time().'.json';
            $jsonFilePath = 'financial_data/'.$user->id.'/'.$jsonFileName;
            Storage::disk('local')->put($jsonFilePath, $conversionResult['json']);

            Log::info('JSON file stored', [
                'user_id' => $user->id,
                'json_file' => $jsonFilePath,
                'json_size' => strlen($conversionResult['json']),
            ]);

            // Update progress: Analyzing with AI
            Cache::put("upload_progress_{$sessionId}", [
                'status' => 'analyzing',
                'progress' => 70,
                'message' => 'Analyzing financial data with AI...',
            ], 600);

            // Analyze with AI agent
            // For large JSON files, we'll send a summary/sample to the AI
            $jsonData = $conversionResult['data'];
            $jsonString = $conversionResult['json'];

            // Optimize: If JSON is too large (>500KB), create a summary for AI
            $dataForAI = $jsonString;
            if (strlen($jsonString) > 500000) {
                // Create a summary with first 100 rows of each sheet
                $summaryData = [];
                foreach ($jsonData as $sheetName => $sheetData) {
                    $summaryData[$sheetName] = array_slice($sheetData, 0, 100);
                }
                $dataForAI = json_encode($summaryData, JSON_PRETTY_PRINT);

                Log::info('Large file detected, using summary for AI analysis', [
                    'original_size' => strlen($jsonString),
                    'summary_size' => strlen($dataForAI),
                ]);
            }

            // Create AI agent instance
            $agent = FinanceFileAnalystAgent::for('financial_analysis_'.$user->id.'_'.time());

            // Prepare prompt with financial data
            $prompt = "Analyze the following financial data from an Excel/CSV file. Determine if the company has monthly transactions above $1000.\n\n";
            $prompt .= "Financial Data:\n";
            $prompt .= $dataForAI;
            $prompt .= "\n\nPlease analyze this data and provide your assessment.";

            // Get AI analysis
            $analysis = $agent->respond($prompt);

            Log::info('AI analysis completed', [
                'user_id' => $user->id,
                'meets_requirement' => $analysis['meets_requirement'] ?? false,
                'monthly_transaction_amount' => $analysis['monthly_transaction_amount'] ?? 0,
            ]);

            // Update progress: Complete
            Cache::put("upload_progress_{$sessionId}", [
                'status' => 'completed',
                'progress' => 100,
                'message' => 'Analysis complete!',
                'analysis' => $analysis,
            ], 600);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded and analyzed successfully.',
                'session_id' => $sessionId,
                'file' => [
                    'name' => $originalName,
                    'size' => $fileSize,
                    'type' => $extension,
                    'json_file' => $jsonFileName,
                ],
                'analysis' => $analysis,
                'meets_requirement' => $analysis['meets_requirement'] ?? false,
            ]);
        } catch (\Exception $e) {
            Log::error('Financial data upload error', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to upload file. Please try again.',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred while uploading your file.',
            ], 500);
        }
    }

    /**
     * Get upload and analysis progress status.
     *
     * Used by frontend to poll for progress updates during file processing.
     *
     * @param  Request  $request  HTTP request
     * @param  string  $sessionId  Session ID from upload response
     * @return JsonResponse Current progress status
     */
    public function getUploadStatus(Request $request, string $sessionId): JsonResponse
    {
        try {
            $progress = Cache::get("upload_progress_{$sessionId}");

            if (! $progress) {
                return response()->json([
                    'success' => false,
                    'error' => 'Session not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'progress' => $progress,
            ]);
        } catch (\Exception $e) {
            Log::error('Get upload status error', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get status',
            ], 500);
        }
    }
}
