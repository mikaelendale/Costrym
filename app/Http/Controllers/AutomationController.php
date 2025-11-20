<?php

namespace App\Http\Controllers;

use App\Models\Automation;
use Illuminate\Http\Request;
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
