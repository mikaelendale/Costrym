<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class WorkflowController extends Controller
{
    //
    public function index()
    {
        $mockData = [
            'notification_title' => 'Workflow Approval Needed',
            'body' => 'A new workflow plan has been generated and requires your approval.',
            'update_summary' => 'This workflow aims to optimize costs by automating resource management tasks.',
            'details' => [
                'what_to_do' => 'Review the proposed steps and approve or reject the workflow.',
                'why' => 'To ensure cost efficiency and resource optimization.',
                'impact' => 'Successful implementation will reduce operational costs by 15%.',
                'dependencies' => 'Requires access to cloud resource management tools.',
                'risk' => 'Minimal risk; changes can be rolled back if issues arise.',
            ],
        ];

        return Inertia::render('ai/Index', ['mockData' => $mockData]);
    }
}
