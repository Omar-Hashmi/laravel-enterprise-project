<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkflowAssignment;
use App\Models\WorkflowInstance;

class WorkflowInstancePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('workflow.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, WorkflowInstance $workflowInstance): bool
    {
        return $user->can('workflow.view')
            || $workflowInstance->initiated_by === $user->id
            || $workflowInstance->assignments()->where(function ($query) use ($user): void {
                $query->where('assigned_to', $user->id)->orWhereIn('assigned_role', $user->getRoleNames());
            })->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('workflow.start');
    }

    public function approve(User $user, WorkflowInstance $workflowInstance, ?WorkflowAssignment $assignment = null): bool
    {
        return $user->can('workflow.manage') || ($user->can('workflow.approve') && $assignment?->instance()->is($workflowInstance) && $this->isAssignedApprover($user, $assignment));
    }

    public function reject(User $user, WorkflowInstance $workflowInstance, ?WorkflowAssignment $assignment = null): bool
    {
        return $this->approve($user, $workflowInstance, $assignment);
    }

    private function isAssignedApprover(User $user, WorkflowAssignment $assignment): bool
    {
        return $assignment->assigned_to === $user->id
            || ($assignment->assigned_role !== null && $user->hasRole($assignment->assigned_role));
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, WorkflowInstance $workflowInstance): bool
    {
        return $user->can('workflow.manage') || $workflowInstance->initiated_by === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, WorkflowInstance $workflowInstance): bool
    {
        return $user->can('workflow.delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, WorkflowInstance $workflowInstance): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, WorkflowInstance $workflowInstance): bool
    {
        return false;
    }
}
