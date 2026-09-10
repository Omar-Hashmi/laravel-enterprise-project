<?php

namespace App\Listeners;

use App\Events\FormSubmitted;
use App\Events\TaskAssignedEvent;
use App\Events\WorkflowStatusChanged;
use App\Events\WorkflowStepActivated;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkflowAssignment;
use App\Services\AdminDashboardService;
use App\Services\WorkflowAnalyticsService;
use Illuminate\Events\Dispatcher;

class WorkflowEventListener
{
    /**
     * Handle when a workflow step is activated.
     */
    public function handleStepActivated(WorkflowStepActivated $event): void
    {
        $instance = $event->instance->loadMissing('workflow');
        $step = $event->step;

        $assignments = WorkflowAssignment::where('workflow_instance_id', $instance->id)
            ->where('workflow_step_id', $step->id)
            ->where('status', 'pending')
            ->get();

        if ($assignments->isNotEmpty()) {
            foreach ($assignments as $assignment) {
                $user = null;
                if ($assignment->assigned_to) {
                    $user = User::find($assignment->assigned_to);
                } elseif ($assignment->assigned_role) {
                    $user = $this->findUserByRole($assignment->assigned_role);
                }

                $existingTask = Task::where('workflow_instance_id', $instance->id)
                    ->where('status', '!=', 'cancelled')
                    ->where(function ($query) use ($step, $assignment): void {
                        $query->whereJsonContains('metadata->step_id', $step->id)
                            ->orWhereJsonContains('metadata->assignment_id', $assignment->id);
                    })->first();

                if (! $existingTask) {
                    $dueAt = $assignment->due_at ?? ($step->sla_hours ? now()->addHours($step->sla_hours) : now()->addDays(2));

                    $task = Task::create([
                        'title' => "Approval Required: {$step->name}",
                        'description' => "Action required on workflow '{$instance->workflow->title}' - Step: {$step->name}",
                        'workflow_instance_id' => $instance->id,
                        'form_submission_id' => $instance->formSubmissions()->latest()->first()?->id,
                        'user_id' => $user?->id,
                        'creator_id' => $instance->initiated_by,
                        'status' => 'pending',
                        'priority' => 'medium',
                        'due_at' => $dueAt,
                        'metadata' => [
                            'workflow_id' => $instance->workflow_id,
                            'step_id' => $step->id,
                            'step_name' => $step->name,
                            'assignment_id' => $assignment->id,
                            'role' => $assignment->assigned_role ?? $step->assignee_role,
                        ],
                    ]);

                    TaskAssignedEvent::dispatch($task);
                }
            }
        } else {
            $user = null;
            if ($step->assignee_user_id) {
                $user = User::find($step->assignee_user_id);
            } elseif ($step->assignee_role) {
                $user = $this->findUserByRole($step->assignee_role);
            }

            $existingTask = Task::where('workflow_instance_id', $instance->id)
                ->where('status', '!=', 'cancelled')
                ->whereJsonContains('metadata->step_id', $step->id)
                ->first();

            if (! $existingTask) {
                $task = Task::create([
                    'title' => "Approval Required: {$step->name}",
                    'description' => "Action required on workflow '{$instance->workflow->title}' - Step: {$step->name}",
                    'workflow_instance_id' => $instance->id,
                    'form_submission_id' => $instance->formSubmissions()->latest()->first()?->id,
                    'user_id' => $user?->id,
                    'creator_id' => $instance->initiated_by,
                    'status' => 'pending',
                    'priority' => 'medium',
                    'due_at' => $step->sla_hours ? now()->addHours($step->sla_hours) : now()->addDays(2),
                    'metadata' => [
                        'workflow_id' => $instance->workflow_id,
                        'step_id' => $step->id,
                        'step_name' => $step->name,
                        'role' => $step->assignee_role,
                    ],
                ]);

                TaskAssignedEvent::dispatch($task);
            }
        }

        $this->clearCaches();
    }

    /**
     * Handle when a form submission is created.
     */
    public function handleFormSubmitted(FormSubmitted $event): void
    {
        $submission = $event->submission;
        $submission->loadMissing(['form.workflow', 'submitter']);

        if ($submission->workflow_instance_id) {
            $existingTask = Task::where('workflow_instance_id', $submission->workflow_instance_id)
                ->whereNull('form_submission_id')
                ->first();

            if ($existingTask) {
                $existingTask->update(['form_submission_id' => $submission->id]);

                return;
            }
        }

        if (Task::where('form_submission_id', $submission->id)->exists()) {
            return;
        }

        $assigneeId = $submission->form?->workflow?->created_by;
        if (! $assigneeId) {
            $assigneeId = $this->findUserByRole('Manager')?->id ?? $submission->submitted_by;
        }

        $task = Task::create([
            'title' => "Form Submission Review: {$submission->form->title}",
            'description' => "Review submitted dynamic form data for '{$submission->form->title}'.",
            'workflow_instance_id' => $submission->workflow_instance_id,
            'form_submission_id' => $submission->id,
            'user_id' => $assigneeId,
            'creator_id' => $submission->submitted_by,
            'status' => 'pending',
            'priority' => 'medium',
            'due_at' => now()->addDays(2),
            'metadata' => [
                'form_id' => $submission->form_id,
                'form_title' => $submission->form->title,
            ],
        ]);

        TaskAssignedEvent::dispatch($task);
        $this->clearCaches();
    }

    /**
     * Handle when a workflow instance status changes.
     */
    public function handleStatusChanged(WorkflowStatusChanged $event): void
    {
        $instance = $event->instance;
        $to = strtolower($event->toStatus);

        if (in_array($to, ['approved', 'completed'], true)) {
            Task::where('workflow_instance_id', $instance->id)
                ->whereIn('status', ['pending', 'in_progress'])
                ->get()
                ->each(function (Task $task): void {
                    $task->markComplete('Completed via workflow instance transition');
                });
        } elseif (in_array($to, ['rejected', 'cancelled'], true)) {
            Task::where('workflow_instance_id', $instance->id)
                ->whereIn('status', ['pending', 'in_progress'])
                ->update([
                    'status' => 'cancelled',
                    'completed_at' => now(),
                    'action_notes' => "Workflow instance status changed to {$to}",
                ]);
        }

        $this->clearCaches();
    }

    /**
     * Handle when a workflow assignment is updated.
     */
    public function handleAssignmentUpdated(WorkflowAssignment $assignment): void
    {
        if ($assignment->status === 'approved') {
            Task::where('workflow_instance_id', $assignment->workflow_instance_id)
                ->where('status', '!=', 'completed')
                ->get()
                ->filter(function (Task $task) use ($assignment): bool {
                    $meta = $task->metadata ?? [];

                    return (isset($meta['assignment_id']) && (int) $meta['assignment_id'] === (int) $assignment->id)
                        || (isset($meta['step_id']) && (int) $meta['step_id'] === (int) $assignment->workflow_step_id);
                })
                ->each(function (Task $task): void {
                    $task->markComplete('Approved via workflow assignment response');
                });
        } elseif (in_array($assignment->status, ['rejected', 'cancelled'], true)) {
            Task::where('workflow_instance_id', $assignment->workflow_instance_id)
                ->whereIn('status', ['pending', 'in_progress'])
                ->get()
                ->filter(function (Task $task) use ($assignment): bool {
                    $meta = $task->metadata ?? [];

                    return (isset($meta['assignment_id']) && (int) $meta['assignment_id'] === (int) $assignment->id)
                        || (isset($meta['step_id']) && (int) $meta['step_id'] === (int) $assignment->workflow_step_id);
                })
                ->each(function (Task $task) use ($assignment): void {
                    $task->update([
                        'status' => 'cancelled',
                        'completed_at' => now(),
                        'action_notes' => "Workflow assignment was {$assignment->status}",
                    ]);
                });
        }

        $this->clearCaches();
    }

    /**
     * Safely find a user by role without throwing RoleDoesNotExist exception.
     */
    private function findUserByRole(?string $role): ?User
    {
        if (! $role) {
            return null;
        }

        try {
            return User::role($role)->first();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Invalidate analytics and dashboard cache keys.
     */
    public function clearCaches(): void
    {
        try {
            app(WorkflowAnalyticsService::class)->clearAnalyticsCache();
            app(AdminDashboardService::class)->clearDashboardCache();
        } catch (\Throwable) {
            // Silently continue if services are not bound or cache is unavailable
        }
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @return array<string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            WorkflowStepActivated::class => 'handleStepActivated',
            WorkflowStatusChanged::class => 'handleStatusChanged',
            FormSubmitted::class => 'handleFormSubmitted',
            'eloquent.updated: App\Models\WorkflowAssignment' => 'handleAssignmentUpdated',
        ];
    }
}
