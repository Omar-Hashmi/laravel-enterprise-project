<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WorkflowAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkflowAnalyticsController extends Controller
{
    public function __construct(protected WorkflowAnalyticsService $analytics) {}

    public function completionTimes(Request $request): JsonResponse
    {
        return response()->json(
            $this->analytics->getCompletionTimeStats(
                $request->query('from'),
                $request->query('to')
            )
        );
    }

    public function bottlenecks(): JsonResponse
    {
        return response()->json([
            'bottlenecks' => $this->analytics->getBottlenecks(),
        ]);
    }

    public function departmentPerformance(): JsonResponse
    {
        return response()->json([
            'departments' => $this->analytics->getDepartmentPerformance(),
        ]);
    }

    public function slaCompliance(): JsonResponse
    {
        return response()->json(
            $this->analytics->getSlaComplianceMetrics()
        );
    }

    public function efficiency(): JsonResponse
    {
        return response()->json(
            $this->analytics->getProcessEfficiency()
        );
    }
}
