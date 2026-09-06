<?php

namespace App\Events;

use App\Models\User;
use App\Models\WorkflowInstance;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkflowStatusChanged
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public WorkflowInstance $instance, public string $fromStatus, public string $toStatus, public User $actor) {}
}
