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
use Laravel\Paddle\Exceptions\PaddleException;

/**
 * Onboarding Controller
 *
 * Handles the multi-step onboarding flow for new users:
 * 1. Company information processing
 * 2. AI-powered chat conversation
 * 3. Value proposition estimation
 * 4. Plan selection and Paddle checkout
 * 5. Onboarding completion
 *
 * Features:
 * - AI agent integration for intelligent conversations
 * - Paddle subscription checkout with discount support
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
    // PLAN SELECTION & PADDLE CHECKOUT
    // ============================================================================

    /**
     * Handles plan selection and creates Paddle checkout session.
     *
     * Flow:
     * 1. Validates selected plan against available options
     * 2. Checks if user already has active subscription (redirects if yes)
     * 3. Saves plan preference to user profile
     * 4. Creates Paddle checkout session with discount applied
     * 5. Returns checkout options via Inertia (non-reloading)
     *
     * The checkout options are used by the frontend PaddleCheckout component
     * to open Paddle's overlay checkout. Discounts are automatically applied
     * based on plan configuration in services.php.
     *
     * @param  Request  $request  HTTP request containing the selected plan
     * @return \Inertia\Response|\Illuminate\Http\RedirectResponse
     */
    public function selectPlan(Request $request)
    {
        $request->validate([
            'plan' => 'required|string|in:startup-monthly,startup-annual,enterprise-annual',
        ]);

        try {
            $user = $request->user();

            if (! $user) {
                return redirect()->back()->withErrors(['error' => 'User not authenticated']);
            }

            // Rate limiting: prevent too many checkout attempts
            $key = 'onboarding-checkout:'.$user->id;
            if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, 5)) {
                $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($key);
                Log::warning('Rate limit exceeded for checkout attempts', [
                    'user_id' => $user->id,
                    'seconds_remaining' => $seconds,
                ]);

                return Inertia::render('onboarding', [
                    'checkout_error' => 'Too many checkout attempts. Please wait a moment and try again.',
                ]);
            }
            \Illuminate\Support\Facades\RateLimiter::hit($key, 60); // 5 attempts per minute

            // Check if user already has an active subscription
            if ($user->subscribed('default')) {
                Log::info('User already subscribed, redirecting to onboarding', [
                    'user_id' => $user->id,
                    'selected_plan' => $request->input('plan'),
                ]);

                return redirect()->route('onboarding')->with('info', 'You are already subscribed.');
            }

            // Save plan preference to user profile for tracking
            $user->plan = $request->input('plan');
            $user->save();

            Log::info('Plan selected during onboarding', [
                'user_id' => $user->id,
                'plan' => $request->input('plan'),
            ]);

            // Create Paddle checkout session with discount support
            try {
                $plan = $request->input('plan');

                // Map plan identifiers to Paddle price IDs
                $plans = [
                    'startup-monthly' => config('services.paddle.startup_monthly_price_id'),
                    'startup-annual' => config('services.paddle.startup_annual_price_id'),
                    'enterprise-annual' => config('services.paddle.enterprise_annual_price_id'),
                ];

                // Map plans to discount IDs (configured in services.php)
                $discounts = [
                    'startup-monthly' => config('services.paddle.startup_monthly_discount'),
                    'startup-annual' => config('services.paddle.startup_annual_discount'),
                    'enterprise-annual' => config('services.paddle.enterprise_annual_discount'),
                ];

                // Validate plan exists and has price ID
                if (! isset($plans[$plan]) || empty($plans[$plan])) {
                    throw new \RuntimeException('Invalid plan selected');
                }

                $priceId = $plans[$plan];

                // Log detailed information before attempting checkout
                Log::info('Attempting to create Paddle checkout', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'plan' => $plan,
                    'price_id' => $priceId,
                    'has_paddle_customer' => $user->paddle_id !== null,
                    'paddle_customer_id' => $user->paddle_id,
                    'app_env' => config('app.env', 'unknown'),
                ]);

                // Ensure user has a Paddle customer ID
                // Laravel Cashier should create this automatically, but we'll check first
                if (! $user->paddle_id) {
                    Log::info('User does not have Paddle customer ID, Cashier will create one', [
                        'user_id' => $user->id,
                        'user_email' => $user->email,
                    ]);

                    // Try to manually create customer first to get better error messages
                    // This helps diagnose API key permission issues
                    try {
                        $user->createAsCustomer();
                        Log::info('Paddle customer created successfully', [
                            'user_id' => $user->id,
                            'paddle_id' => $user->paddle_id,
                        ]);
                    } catch (PaddleException $e) {
                        // If customer creation fails, log detailed error but continue
                        // The subscribe() call will also try to create customer
                        Log::warning('Failed to pre-create Paddle customer, will retry in subscribe()', [
                            'user_id' => $user->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                // Create Paddle checkout session using Laravel Cashier
                // Returns to onboarding page to maintain single-page experience
                $checkout = $user->subscribe($priceId, 'default')
                    ->returnTo(route('onboarding'));

                Log::info('Paddle checkout object created', [
                    'user_id' => $user->id,
                    'plan' => $plan,
                    'price_id' => $plans[$plan],
                    'discount_id' => $discounts[$plan] ?? null,
                    'checkout_class' => get_class($checkout),
                ]);

                // Laravel Cashier Paddle returns a Checkout object (not a URL)
                // We extract options to pass to frontend Paddle.js SDK
                if (! method_exists($checkout, 'options')) {
                    throw new \RuntimeException('Checkout object does not have options method');
                }

                $checkoutOptions = $checkout->options();

                // Apply discount if configured for this plan
                // Paddle.js Checkout.open() accepts discountId in options
                $discountId = $discounts[$plan] ?? null;
                if ($discountId && ! empty($discountId)) {
                    $checkoutOptions['discountId'] = $discountId;

                    Log::info('Discount applied to checkout', [
                        'user_id' => $user->id,
                        'plan' => $plan,
                        'discount_id' => $discountId,
                    ]);
                } else {
                    Log::info('No discount configured for plan', [
                        'user_id' => $user->id,
                        'plan' => $plan,
                    ]);
                }

                // Package checkout options for frontend
                // Frontend PaddleCheckout component will use Paddle.Checkout.open()
                $checkoutData = [
                    'type' => 'paddle_checkout',
                    'options' => $checkoutOptions,
                ];

                Log::info('Checkout options created during plan selection', [
                    'user_id' => $user->id,
                    'plan' => $plan,
                    'has_options' => ! empty($checkoutOptions),
                ]);

                // Return Inertia response with checkout data (non-reloading)
                // Frontend automatically opens checkout overlay when this prop is received
                return Inertia::render('onboarding', [
                    'checkout_data' => $checkoutData,
                    'checkout_plan' => $plan,
                ]);
            } catch (PaddleException $e) {
                // Enhanced error logging for Paddle API errors
                $errorMessage = $e->getMessage();
                $errorCode = method_exists($e, 'getCode') ? $e->getCode() : null;

                Log::error('Paddle checkout creation error in plan selection', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'plan' => $request->input('plan'),
                    'price_id' => $plans[$plan] ?? null,
                    'error' => $errorMessage,
                    'error_code' => $errorCode,
                    'error_class' => get_class($e),
                    'has_paddle_customer' => $user->paddle_id !== null,
                    'paddle_customer_id' => $user->paddle_id,
                    'app_env' => config('app.env', 'unknown'),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Provide more specific error messages based on error type
                $userFriendlyMessage = 'Failed to create checkout. Please try again.';

                if (str_contains($errorMessage, "aren't permitted")) {
                    $userFriendlyMessage = 'Unable to process payment request. Please contact support if this persists.';
                    Log::warning('Paddle permissions error - possible API key or customer setup issue', [
                        'user_id' => $user->id,
                        'plan' => $request->input('plan'),
                        'has_paddle_api_key' => ! empty(env('PADDLE_API_KEY')),
                        'paddle_api_key_length' => strlen(env('PADDLE_API_KEY', '')),
                        'paddle_api_key_prefix' => substr(env('PADDLE_API_KEY', ''), 0, 10).'...',
                    ]);

                    // Additional diagnostic: Check if this is a customer creation issue
                    if (! $user->paddle_id && str_contains($errorMessage, "aren't permitted")) {
                        Log::error('CRITICAL: Paddle API key may not have customer creation permissions', [
                            'user_id' => $user->id,
                            'issue' => 'API key lacks permission to create customers',
                            'solution' => 'Verify PADDLE_API_KEY has correct permissions in Paddle dashboard',
                        ]);
                    }
                } elseif (str_contains($errorMessage, 'rate limit') || str_contains($errorMessage, 'too many')) {
                    $userFriendlyMessage = 'Too many requests. Please wait a moment and try again.';
                } elseif (str_contains($errorMessage, 'invalid') || str_contains($errorMessage, 'not found')) {
                    $userFriendlyMessage = 'Invalid plan selected. Please refresh the page and try again.';
                }

                return Inertia::render('onboarding', [
                    'checkout_error' => $userFriendlyMessage,
                ]);
            } catch (\Exception $e) {
                Log::error('Checkout URL creation error', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'plan' => $request->input('plan'),
                    'error' => $e->getMessage(),
                    'error_class' => get_class($e),
                    'trace' => $e->getTraceAsString(),
                ]);

                return Inertia::render('onboarding', [
                    'checkout_error' => 'An unexpected error occurred. Please try again.',
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Plan selection error', [
                'user_id' => $request->user()?->id,
                'plan' => $request->input('plan'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->withErrors(['error' => 'Failed to save plan selection. Please try again.']);
        }
    }

    // ============================================================================
    // SUBSCRIPTION STATUS CHECK
    // ============================================================================

    /**
     * Checks the current subscription status for the authenticated user.
     *
     * Returns comprehensive subscription information including:
     * - Subscription state (active, trial, grace period, etc.)
     * - Current plan identifier
     * - Subscription validity and status flags
     *
     * Note: This endpoint is available but subscription status is primarily
     * accessed via Inertia shared props (HandleInertiaRequests middleware)
     * for better performance and real-time updates.
     *
     * @param  Request  $request  HTTP request
     * @return JsonResponse Subscription status information
     */
    public function checkSubscriptionStatus(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not authenticated',
                ], 401);
            }

            // Check subscription status using Laravel Cashier methods
            $subscription = $user->subscription('default');

            $status = [
                'subscribed' => $user->subscribed('default'),
                'has_subscription' => $subscription !== null,
                'valid' => $subscription && $subscription->valid(),
                'active' => $subscription && $subscription->active(),
                'recurring' => $subscription && $subscription->recurring(),
                'on_trial' => $subscription && $subscription->onTrial(),
                'on_grace_period' => $subscription && $subscription->onGracePeriod(),
                'canceled' => $subscription && $subscription->canceled(),
                'paused' => $subscription && $subscription->paused(),
                'past_due' => $subscription && $subscription->pastDue(),
            ];

            // Get current plan if subscribed
            $currentPlan = null;
            $plans = [
                'startup-monthly' => config('services.paddle.startup_monthly_price_id'),
                'startup-annual' => config('services.paddle.startup_annual_price_id'),
                'enterprise-annual' => config('services.paddle.enterprise_annual_price_id'),
            ];

            if ($subscription) {
                foreach ($plans as $planKey => $priceId) {
                    if ($user->subscribedToPrice($priceId, 'default')) {
                        $currentPlan = $planKey;
                        break;
                    }
                }
            }

            return response()->json([
                'success' => true,
                'status' => $status,
                'current_plan' => $currentPlan,
                'subscription_id' => $subscription?->id,
                'paddle_id' => $subscription?->paddle_id,
            ]);
        } catch (\Exception $e) {
            Log::error('Subscription status check error', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to check subscription status',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred',
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
