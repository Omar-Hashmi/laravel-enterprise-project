<?php

namespace App\Jobs;

use App\Models\WorkflowAssignment;
use App\Services\AuditTrailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessWorkflowSlaMonitoring implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(AuditTrailService $audit): void
    {
        WorkflowAssignment::query()->where('status', 'pending')->whereNotNull('due_at')->where('due_at', '<=', now()->addHour())->each(function (WorkflowAssignment $assignment) use ($audit): void {
            $audit->record('workflow.sla.at_risk', $assignment, null, ['due_at' => $assignment->due_at?->toIso8601String()]);
        });
    }
}
