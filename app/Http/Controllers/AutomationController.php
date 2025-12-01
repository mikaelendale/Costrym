<?php

namespace App\Http\Controllers;

use App\AiAgents\TaskExecutorAgent;
use App\Models\Automation;
use App\Services\PipedreamToolLoader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class AutomationController extends Controller
{
    /**
     * Display a list of all automations for the authenticated user
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Automation::where('user_id', $user->id)
            ->where('status', 'active');

        // Filter by type if provided
        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'ilike', "%{$request->search}%")
                    ->orWhere('description', 'ilike', "%{$request->search}%");
            });
        }

        $automations = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        // Get counts by type
        $typeCounts = Automation::where('user_id', $user->id)
            ->where('status', 'active')
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        return Inertia::render('Automations/Index', [
            'automations' => $automations,
            'filters' => [
                'type' => $request->type ?? 'all',
                'search' => $request->search ?? '',
            ],
            'typeCounts' => $typeCounts,
        ]);
    }

    /**
     * Display a single automation with formatted markdown
     */
    public function show(Request $request, Automation $automation)
    {
        // Ensure user owns this automation
        if ($automation->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        // Load relationships
        $automation->load('task', 'taskQueue');

        return Inertia::render('Automations/Show', [
            'automation' => $automation,
        ]);
    }

    /**
     * Execute an automation using TaskExecutorAgent + Pipedream integrations.
     */
    public function execute(Request $request, Automation $automation)
    {
        $user = $request->user();

        // Ensure user owns this automation
        if ($automation->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        // Require at least one connected Pipedream integration
        $toolLoader = app(PipedreamToolLoader::class);
        if (! $toolLoader->userHasConnectedAccounts($user->id)) {
            return back()->with('error', 'You must connect at least one integration in Settings → Integrations before executing this automation.');
        }

        // Load required-only tools; if none, block execution
        $requiredTools = $toolLoader->loadToolsForUser($user->id, true);
        if ($requiredTools->isEmpty()) {
            return back()->with('error', 'No required integrations available for execution. Please connect at least one required integration.');
        }

        $markdown = $automation->getContentFromStorage() ?? $automation->markdown_content;

        if (! $markdown) {
            return back()->with('error', 'This automation has no content to execute.');
        }

        try {
            $sessionId = "automation_exec_{$user->id}_{$automation->id}_".time();

            $prompt = <<<PROMPT
You are executing a cost optimization automation that has already been analyzed.

Your job now is to take the existing analysis and **execute the recommended actions using the available integrations and tools**.

You MUST:
- Use the available Pipedream tools to perform concrete actions (e.g. create docs, send notifications, update records).
- Respect the execution_result response schema.
- Generate an execution-focused markdown report summarizing what was actually done.

Below is the automation analysis markdown you should base your execution on:

--- AUTOMATION ANALYSIS (MARKDOWN) ---
{$markdown}
--- END OF ANALYSIS ---

Now, execute the plan.
PROMPT;

            /** @var TaskExecutorAgent $agent */
            $agent = app(TaskExecutorAgent::class)
                ->setUserId($user->id);

            $result = $agent::for($sessionId)->respond($prompt);

            // Optionally store last execution result in metadata for later display
            $metadata = $automation->metadata ?? [];
            $metadata['last_execution'] = [
                'run_at' => now()->toIso8601String(),
                'result' => $result,
            ];
            $automation->update(['metadata' => $metadata]);

            Log::info('Automation executed successfully', [
                'automation_id' => $automation->id,
                'user_id' => $user->id,
            ]);

            return back()->with('success', 'Execution started and completed. Refresh the page to see any updates created by integrations.');
        } catch (\Throwable $e) {
            Log::error('Automation execution failed', [
                'automation_id' => $automation->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Execution failed: '.$e->getMessage());
        }
    }

    /**
     * Archive an automation
     */
    public function archive(Request $request, Automation $automation)
    {
        // Ensure user owns this automation
        if ($automation->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        $automation->archive();

        return back()->with('success', 'Automation archived successfully');
    }

    /**
     * Download automation as MD file
     */
    public function download(Request $request, Automation $automation)
    {
        // Ensure user owns this automation
        if ($automation->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        $filename = str_replace(' ', '_', $automation->name).'.md';

        return response($automation->markdown_content)
            ->header('Content-Type', 'text/markdown')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}
