<?php

namespace App\Services;

use App\Events\WorkflowStatusChanged;
use App\Events\WorkflowStepActivated;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowAssignment;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStep;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class WorkflowEngine
{
    public function __construct(private WorkflowStateMachine $stateMachine, private WorkflowConditionEvaluator $conditions, private AuditTrailService $audit) {}

    /** @param array<string, mixed> $data */
    public function start(Workflow $workflow, User $initiator, array $data = []): WorkflowInstance
    {
        if ($workflow->status !== 'active') {
            throw new \DomainException('Only active workflows can be started.');
        }

        return DB::transaction(function () use ($workflow, $initiator, $data): WorkflowInstance {
            $instance = WorkflowInstance::create(['workflow_id' => $workflow->id, 'initiated_by' => $initiator->id, 'status' => 'pending', 'data' => $data, 'started_at' => now()]);
            $this->transition($instance, 'in_progress', $initiator);
            $this->activateNextStep($instance);

            return $instance->refresh();
        });
    }

    public function approve(WorkflowAssignment $assignment, User $actor, ?string $comment = null): WorkflowInstance
    {
        return $this->respond($assignment, $actor, 'approved', $comment);
    }

    public function reject(WorkflowAssignment $assignment, User $actor, ?string $comment = null): WorkflowInstance
    {
        return $this->respond($assignment, $actor, 'rejected', $comment);
    }

    public function cancel(WorkflowInstance $instance, User $actor): WorkflowInstance
    {
        return DB::transaction(function () use ($instance, $actor): WorkflowInstance {
            $instance = $instance->newQuery()->lockForUpdate()->findOrFail($instance->id);
            $this->transition($instance, 'cancelled', $actor);
            $instance->assignments()->where('status', 'pending')->update(['status' => 'cancelled', 'responded_at' => now()]);

            return $instance->refresh();
        });
    }

    public function processAutoAction(WorkflowInstance $instance): WorkflowInstance
    {
        return DB::transaction(function () use ($instance): WorkflowInstance {
            $instance = $instance->newQuery()->lockForUpdate()->with('currentStep')->findOrFail($instance->id);
            if ($instance->status !== 'in_progress' || $instance->currentStep?->type !== 'auto_action') {
                return $instance;
            }
            $this->applyAutoAction($instance, $instance->currentStep);

            return $instance->refresh();
        });
    }

    private function respond(WorkflowAssignment $assignment, User $actor, string $decision, ?string $comment): WorkflowInstance
    {
        return DB::transaction(function () use ($assignment, $actor, $decision, $comment): WorkflowInstance {
            $assignment->refresh();
            if ($assignment->status !== 'pending' || ! ($assignment->assigned_to === $actor->id || ($assignment->assigned_role !== null && $actor->hasRole($assignment->assigned_role)))) {
                throw new AuthorizationException('This approval is not available to the current user.');
            }
            $instance = $assignment->instance()->lockForUpdate()->firstOrFail();
            if ($instance->status !== 'in_progress' || $assignment->workflow_step_id !== $instance->current_step_id) {
                throw new \DomainException('This workflow assignment is no longer active.');
            }
            $assignment->update(['status' => $decision, 'comment' => $comment, 'responded_at' => now()]);
            $this->audit->record("workflow.assignment.{$decision}", $assignment, ['status' => 'pending'], ['status' => $decision], $actor->id);
            if ($decision === 'rejected') {
                $this->transition($instance, 'rejected', $actor);
            } elseif (! $instance->assignments()->where('workflow_step_id', $assignment->workflow_step_id)->where('status', 'pending')->exists()) {
                $this->activateNextStep($instance);
            }

            return $instance->refresh();
        });
    }

    private function activateNextStep(WorkflowInstance $instance): void
    {
        $previousOrder = $instance->currentStep?->step_order ?? 0;
        $step = $instance->workflow->steps()->where('step_order', '>', $previousOrder)->get()->first(fn (WorkflowStep $step) => ! in_array($step->type, ['conditional', 'decision'], true) || $this->conditions->passes($step->rules['conditions'] ?? [], $instance->data ?? []));
        if ($step === null) {
            $this->transition($instance, 'approved', $instance->initiator);

            return;
        }
        $instance->update(['current_step_id' => $step->id]);
        if ($step->type === 'auto_action') {
            $this->applyAutoAction($instance, $step);

            return;
        }
        $approvers = $step->rules['approvers'] ?? [['user_id' => $step->assignee_user_id, 'role' => $step->assignee_role]];
        if (collect($approvers)->contains(fn (array $approver): bool => empty($approver['user_id']) && empty($approver['role']))) {
            throw new \DomainException("Workflow step {$step->id} has no approver.");
        }
        foreach ($approvers as $approver) {
            WorkflowAssignment::create([
                'workflow_instance_id' => $instance->id,
                'workflow_step_id' => $step->id,
                'assigned_to' => $approver['user_id'] ?? null,
                'assigned_role' => $approver['role'] ?? null,
                'due_at' => $step->sla_hours ? now()->addHours($step->sla_hours) : null,
            ]);
        }
        $this->audit->record('workflow.step.activated', $instance, null, ['step_id' => $step->id], $instance->initiated_by);
        WorkflowStepActivated::dispatch($instance->fresh(), $step);
    }

    private function applyAutoAction(WorkflowInstance $instance, WorkflowStep $step): void
    {
        $action = $step->rules['action'] ?? null;
        $this->audit->record('workflow.auto_action.executed', $instance, null, ['step_id' => $step->id, 'action' => $action], $instance->initiated_by);
        if ($action === 'reject') {
            $this->transition($instance, 'rejected', $instance->initiator);

            return;
        }
        if ($action === 'approve') {
            $this->transition($instance, 'approved', $instance->initiator);

            return;
        }
        if ($action !== null) {
            throw new \DomainException("Unsupported workflow auto-action: {$action}.");
        }
        $this->activateNextStep($instance);
    }

    private function transition(WorkflowInstance $instance, string $to, User $actor): void
    {
        $from = $instance->status;
        $this->stateMachine->transition($instance, $to);
        $this->audit->record('workflow.status.changed', $instance, ['status' => $from], ['status' => $to], $actor->id);
        WorkflowStatusChanged::dispatch($instance->fresh(), $from, $to, $actor);
    }
}
