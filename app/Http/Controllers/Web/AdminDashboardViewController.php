<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\AdminDashboardService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AdminDashboardViewController extends Controller
{
    /**
     * Display the Executive Admin Dashboard view.
     */
    public function index(Request $request, AdminDashboardService $dashboardService): View
    {
        if (! $request->user()->hasAnyRole(['Super Admin', 'Department Admin', 'Manager', 'Auditor']) && ! $request->user()->canAny(['workflow.manage', 'workflow.approve', 'audit.view'])) {
            abort(403, 'Unauthorized access to executive dashboard.');
        }

        $kpis = $dashboardService->getKpis();
        $delayedProcesses = $dashboardService->getDelayedProcesses(10);
        $userActivity = $dashboardService->getUserActivity(15);
        $approvalStats = $dashboardService->getApprovalStatistics();

        return view('admin.dashboard', compact('kpis', 'delayedProcesses', 'userActivity', 'approvalStats'));
    }
}
