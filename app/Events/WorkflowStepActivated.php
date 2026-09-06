<?php

namespace App\Events;

use App\Models\WorkflowInstance;
use App\Models\WorkflowStep;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkflowStepActivated
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public WorkflowInstance $instance, public WorkflowStep $step) {}
}
