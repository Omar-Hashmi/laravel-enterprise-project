<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\WorkflowAnalyticsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class WorkflowAnalyticsViewController extends Controller
{
    /**
     * Display the Workflow Analytics view.
     */
    public function index(Request $request, WorkflowAnalyticsService $analyticsService): View
    {
        if (! $request->user()->hasAnyRole(['Super Admin', 'Department Admin', 'Manager', 'Auditor']) && ! $request->user()->canAny(['workflow.manage', 'workflow.approve', 'audit.view'])) {
            abort(403, 'Unauthorized access to workflow analytics.');
        }

        $completionStats = $analyticsService->getCompletionTimeStats();
        $bottlenecks = $analyticsService->getBottlenecks();
        $departmentPerformance = $analyticsService->getDepartmentPerformance();
        $slaCompliance = $analyticsService->getSlaComplianceMetrics();
        $efficiency = $analyticsService->getProcessEfficiency();

        return view('analytics.index', compact(
            'completionStats',
            'bottlenecks',
            'departmentPerformance',
            'slaCompliance',
            'efficiency'
        ));
    }
}
