<?php

namespace App\Models;

use Database\Factories\WorkflowAssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowAssignment extends Model
{
    /** @use HasFactory<WorkflowAssignmentFactory> */
    use HasFactory;

    protected $fillable = ['workflow_instance_id', 'workflow_step_id', 'assigned_to', 'assigned_role', 'status', 'comment', 'due_at', 'responded_at'];

    protected function casts(): array
    {
        return ['due_at' => 'datetime', 'responded_at' => 'datetime'];
    }

    public function instance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class, 'workflow_instance_id');
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'workflow_step_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
