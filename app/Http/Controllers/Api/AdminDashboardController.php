<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AdminDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function __construct(protected AdminDashboardService $dashboard) {}

    public function kpis(): JsonResponse
    {
        return response()->json(
            $this->dashboard->getKpis()
        );
    }

    public function delayedProcesses(Request $request): JsonResponse
    {
        return response()->json([
            'delayed_processes' => $this->dashboard->getDelayedProcesses($request->integer('limit', 10)),
        ]);
    }

    public function userActivity(Request $request): JsonResponse
    {
        return response()->json([
            'activity' => $this->dashboard->getUserActivity($request->integer('limit', 15)),
        ]);
    }

    public function approvalStatistics(): JsonResponse
    {
        return response()->json(
            $this->dashboard->getApprovalStatistics()
        );
    }
}
