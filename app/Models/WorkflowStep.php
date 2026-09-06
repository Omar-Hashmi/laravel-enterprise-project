<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowStep extends Model
{
    protected $fillable = ['workflow_id', 'name', 'type', 'step_order', 'rules', 'sla_hours', 'assignee_role', 'assignee_user_id'];

    protected $casts = [
        'rules' => 'array',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_user_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(WorkflowAssignment::class);
    }
}
