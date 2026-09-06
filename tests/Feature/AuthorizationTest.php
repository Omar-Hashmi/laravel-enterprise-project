<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use App\Services\WorkflowEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_workflows(): void
    {
        $response = $this->getJson('/workflows');

        $response->assertUnauthorized();
    }

    public function test_user_without_workflow_permission_cannot_create_a_workflow(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/workflows', ['title' => 'Restricted']);

        $response->assertForbidden();
        $this->assertDatabaseMissing('workflows', ['title' => 'Restricted']);
    }

    public function test_assigned_approver_can_approve_through_the_authorized_endpoint(): void
    {
        $initiator = User::factory()->create();
        $approver = User::factory()->create();
        $this->grant($initiator, 'workflow.start');
        $this->grant($approver, 'workflow.approve');
        Role::create(['name' => 'Manager']);
        $approver->assignRole('Manager');
        $workflow = Workflow::create(['title' => 'Purchase request', 'status' => 'active', 'created_by' => $initiator->id]);
        WorkflowStep::create(['workflow_id' => $workflow->id, 'name' => 'Manager approval', 'type' => 'sequential', 'step_order' => 1, 'assignee_role' => 'Manager']);
        $instance = app(WorkflowEngine::class)->start($workflow, $initiator);

        $response = $this->actingAs($approver)->postJson('/workflow-assignments/'.$instance->assignments()->firstOrFail()->id.'/approve', ['comment' => 'Approved']);

        $response->assertOk()->assertJsonPath('status', 'approved');
        $this->assertDatabaseHas('audit_logs', ['action' => 'workflow.assignment.approved', 'user_id' => $approver->id]);
    }

    public function test_a_form_cannot_submit_against_an_unrelated_workflow_instance(): void
    {
        $user = User::factory()->create();
        $this->grant($user, 'form.submit');
        $firstWorkflow = Workflow::create(['title' => 'First', 'created_by' => $user->id]);
        $secondWorkflow = Workflow::create(['title' => 'Second', 'status' => 'active', 'created_by' => $user->id]);
        $form = Form::create(['workflow_id' => $firstWorkflow->id, 'title' => 'Details']);
        $instance = Workflow::find($secondWorkflow->id)->instances()->create([
            'initiated_by' => $user->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->postJson('/forms/'.$form->id.'/submissions', [
            'workflow_instance_id' => $instance->id,
            'data' => ['name' => 'Invalid context'],
        ]);

        $response->assertUnprocessable();
        $this->assertDatabaseCount('form_submissions', 0);
    }

    public function test_a_workflow_owner_can_create_a_form_without_form_manage_permission(): void
    {
        $user = User::factory()->create();
        $workflow = Workflow::create(['title' => 'Owned workflow', 'created_by' => $user->id]);

        $response = $this->actingAs($user)->postJson('/workflows/'.$workflow->id.'/forms', [
            'title' => 'Owned form',
            'fields' => [],
        ]);

        $response->assertCreated()->assertJsonPath('title', 'Owned form');
        $this->assertDatabaseHas('forms', ['workflow_id' => $workflow->id, 'title' => 'Owned form']);
    }

    public function test_a_workflow_owner_can_update_and_audit_a_definition(): void
    {
        $user = User::factory()->create();
        $workflow = Workflow::create(['title' => 'Draft workflow', 'created_by' => $user->id]);

        $response = $this->actingAs($user)->putJson('/workflows/'.$workflow->id, [
            'title' => 'Updated workflow',
            'status' => 'active',
            'steps' => [[
                'name' => 'Manager review',
                'type' => 'sequential',
                'step_order' => 1,
                'assignee_role' => 'Manager',
            ]],
        ]);

        $response->assertOk()->assertJsonPath('title', 'Updated workflow');
        $this->assertDatabaseHas('workflow_steps', ['workflow_id' => $workflow->id, 'name' => 'Manager review']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'workflow.updated', 'auditable_id' => $workflow->id]);
    }

    public function test_a_workflow_owner_can_cancel_an_instance_and_pending_assignments(): void
    {
        $user = User::factory()->create();
        $this->grant($user, 'workflow.start');
        $workflow = Workflow::create(['title' => 'Cancellable', 'status' => 'active', 'created_by' => $user->id]);
        WorkflowStep::create(['workflow_id' => $workflow->id, 'name' => 'Review', 'type' => 'sequential', 'step_order' => 1, 'assignee_role' => 'Manager']);
        $instance = app(WorkflowEngine::class)->start($workflow, $user);

        $response = $this->actingAs($user)->postJson('/workflow-instances/'.$instance->id.'/cancel');

        $response->assertOk()->assertJsonPath('status', 'cancelled');
        $this->assertDatabaseHas('workflow_assignments', ['workflow_instance_id' => $instance->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'workflow.status.changed', 'auditable_id' => $instance->id]);
    }

    private function grant(User $user, string $permission): void
    {
        Permission::firstOrCreate(['name' => $permission]);
        $user->givePermissionTo($permission);
    }
}
