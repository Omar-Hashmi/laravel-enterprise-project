<?php

namespace App\Jobs;

use App\Models\WorkflowAssignment;
use App\Services\AuditTrailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessWorkflowEscalations implements ShouldQueue
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
        WorkflowAssignment::query()->where('status', 'pending')->whereNotNull('due_at')->where('due_at', '<', now())->each(function (WorkflowAssignment $assignment) use ($audit): void {
            $assignment->update(['status' => 'escalated']);
            $audit->record('workflow.assignment.escalated', $assignment, ['status' => 'pending'], ['status' => 'escalated']);
        });
    }
}
