<?php

namespace App\Http\Controllers\Api;

use App\Exports\AnalyticsSummaryExport;
use App\Exports\TasksExport;
use App\Http\Controllers\Controller;
use App\Services\WorkflowAnalyticsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportExportController extends Controller
{
    public function __construct(protected WorkflowAnalyticsService $analytics) {}

    /**
     * Download Excel export of tasks.
     */
    public function tasksExcel(): BinaryFileResponse
    {
        $filename = 'tasks_export_'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(new TasksExport, $filename);
    }

    /**
     * Download Excel export of workflow analytics summary.
     */
    public function analyticsExcel(): BinaryFileResponse
    {
        $data = $this->analytics->getDepartmentPerformance();
        $filename = 'analytics_summary_'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(new AnalyticsSummaryExport($data), $filename);
    }

    /**
     * Generate and download DomPDF executive compliance report.
     */
    public function compliancePdf(Request $request): Response
    {
        $sla = $this->analytics->getSlaComplianceMetrics();
        $data = [
            'generated_at' => now()->toDayDateTimeString(),
            'generated_by' => $request->user()?->name ?? 'Administrator',
            'compliance_rate' => $sla['compliance_rate'],
            'total_tasks' => $sla['total_tasks'],
            'on_time_tasks' => $sla['on_time_tasks'],
            'breached_tasks' => $sla['breached_tasks'],
            'department_stats' => $this->analytics->getDepartmentPerformance(),
            'bottlenecks' => $this->analytics->getBottlenecks(),
        ];

        $pdf = Pdf::loadView('reports.workflow_compliance_report', $data);
        $filename = 'workflow_compliance_report_'.now()->format('Ymd_His').'.pdf';

        return $pdf->download($filename);
    }
}
