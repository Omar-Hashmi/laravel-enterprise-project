<?php

namespace App\Jobs;

use App\Models\WorkflowInstance;
use App\Services\WorkflowEngine;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessWorkflowAutoActions implements ShouldQueue
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
    public function handle(WorkflowEngine $engine): void
    {
        WorkflowInstance::query()
            ->where('status', 'in_progress')
            ->whereHas('currentStep', fn ($query) => $query->where('type', 'auto_action'))
            ->each(fn (WorkflowInstance $instance) => $engine->processAutoAction($instance));
    }
}
