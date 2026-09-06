<?php

namespace Tests\Feature;

use App\Jobs\ProcessWorkflowAutoActions;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use App\Services\WorkflowEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WorkflowEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_assigned_approver_can_complete_a_sequential_workflow(): void
    {
        $initiator = User::factory()->create();
        $approver = User::factory()->create();
        Role::create(['name' => 'Manager']);
        $approver->assignRole('Manager');
        $workflow = Workflow::create(['title' => 'Purchase request', 'status' => 'active', 'created_by' => $initiator->id]);
        WorkflowStep::create(['workflow_id' => $workflow->id, 'name' => 'Manager approval', 'type' => 'sequential', 'step_order' => 1, 'assignee_role' => 'Manager']);

        $instance = app(WorkflowEngine::class)->start($workflow, $initiator, ['amount' => 100]);
        $completed = app(WorkflowEngine::class)->approve($instance->assignments()->firstOrFail(), $approver, 'Approved');

        $this->assertSame('approved', $completed->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'workflow.assignment.approved']);
    }

    public function test_auto_action_steps_are_processed_by_the_scheduled_job(): void
    {
        $initiator = User::factory()->create();
        $workflow = Workflow::create(['title' => 'Automatic approval', 'status' => 'active', 'created_by' => $initiator->id]);
        WorkflowStep::create(['workflow_id' => $workflow->id, 'name' => 'Automatic approval', 'type' => 'auto_action', 'step_order' => 1, 'rules' => ['action' => 'approve']]);
        $instance = app(WorkflowEngine::class)->start($workflow, $initiator);

        app(ProcessWorkflowAutoActions::class)->handle(app(WorkflowEngine::class));

        $this->assertSame('approved', $instance->refresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'workflow.auto_action.executed']);
    }

    public function test_approval_steps_must_have_an_assignee(): void
    {
        $initiator = User::factory()->create();
        $workflow = Workflow::create(['title' => 'Invalid workflow', 'status' => 'active', 'created_by' => $initiator->id]);
        WorkflowStep::create(['workflow_id' => $workflow->id, 'name' => 'Unassigned review', 'type' => 'sequential', 'step_order' => 1]);

        $this->expectException(\DomainException::class);
        app(WorkflowEngine::class)->start($workflow, $initiator);
    }
}
