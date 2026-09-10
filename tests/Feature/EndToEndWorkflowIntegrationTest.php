<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\FormField;
use App\Models\Task;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use App\Services\FormSubmissionService;
use App\Services\WorkflowEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EndToEndWorkflowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /**
     * Test full end-to-end flow:
     * 1. Initiates a workflow instance.
     * 2. Integration listener creates an active task.
     * 3. Notification is dispatched/recorded for the assigned approver.
     * 4. Approver completes the task via /api/v1/tasks/{task:uuid}/complete.
     * 5. Admin dashboard and analytics endpoints immediately reflect updated state.
     */
    public function test_full_end_to_end_workflow_lifecycle(): void
    {
        $initiator = User::factory()->create(['name' => 'Jane Initiator', 'email' => 'jane@enterprise.test']);
        $approver = User::factory()->create(['name' => 'Marcus Manager', 'email' => 'marcus@enterprise.test']);

        Role::create(['name' => 'Manager']);
        $approver->assignRole('Manager');

        $workflow = Workflow::create([
            'title' => 'Hardware Purchase Order',
            'status' => 'active',
            'created_by' => $initiator->id,
        ]);

        WorkflowStep::create([
            'workflow_id' => $workflow->id,
            'name' => 'Manager Budget Approval',
            'type' => 'sequential',
            'step_order' => 1,
            'assignee_role' => 'Manager',
            'sla_hours' => 24,
        ]);

        // 1. Initiate workflow instance
        $instance = app(WorkflowEngine::class)->start($workflow, $initiator, [
            'item' => 'Developer Workstation',
            'estimated_cost' => 3500,
        ]);

        $this->assertSame('in_progress', $instance->status);

        // 2. Integration listener automatically creates an active task
        $task = Task::where('workflow_instance_id', $instance->id)->first();
        $this->assertNotNull($task);
        $this->assertSame('pending', $task->status);
        $this->assertSame($approver->id, $task->user_id);
        $this->assertStringContainsString('Manager Budget Approval', $task->title);
        $this->assertNotNull($task->due_at);

        // 3. Notification is recorded for the assigned approver
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $approver->id,
            'notifiable_type' => User::class,
        ]);
        $this->assertSame(1, $approver->notifications()->count());

        // 4. Approver approves / completes the task via REST API
        $response = $this->actingAs($approver)->postJson("/api/v1/tasks/{$task->uuid}/complete", [
            'action_notes' => 'Verified budget availability and approved purchase.',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.status', 'completed');
        $response->assertJsonPath('data.action_notes', 'Verified budget availability and approved purchase.');

        // Verify task persisted state
        $task->refresh();
        $this->assertSame('completed', $task->status);
        $this->assertNotNull($task->completed_at);

        // Verify workflow assignment is marked approved
        $this->assertDatabaseHas('workflow_assignments', [
            'workflow_instance_id' => $instance->id,
            'status' => 'approved',
        ]);

        // 5. Admin dashboard and analytics endpoints immediately reflect the state change
        $kpiResponse = $this->actingAs($approver)->getJson('/api/v1/dashboard/kpis');
        $kpiResponse->assertOk();
        $kpiData = $kpiResponse->json();
        $this->assertGreaterThanOrEqual(1, $kpiData['completed_24h']);
        $this->assertSame(0, $kpiData['active_tasks']);

        $analyticsResponse = $this->actingAs($approver)->getJson('/api/v1/analytics/completion-times');
        $analyticsResponse->assertOk();
        $analyticsData = $analyticsResponse->json();
        $this->assertSame(1, $analyticsData['completed_count']);
        $this->assertGreaterThanOrEqual(0, $analyticsData['avg_duration_minutes']);

        $approvalStatsResponse = $this->actingAs($approver)->getJson('/api/v1/dashboard/approval-statistics');
        $approvalStatsResponse->assertOk();
        $this->assertGreaterThanOrEqual(1, $approvalStatsResponse->json('approved'));
    }

    /**
     * Test that dynamic form submission automatically triggers Task creation.
     */
    public function test_form_submission_triggers_task_creation_and_notification(): void
    {
        $user = User::factory()->create(['name' => 'Alice Employee']);
        $manager = User::factory()->create(['name' => 'Bob Manager']);
        Role::create(['name' => 'Manager']);
        $manager->assignRole('Manager');

        $workflow = Workflow::create([
            'title' => 'Leave Request System',
            'status' => 'active',
            'created_by' => $manager->id,
        ]);

        $form = Form::create([
            'workflow_id' => $workflow->id,
            'title' => 'Leave Request Form',
        ]);

        FormField::create([
            'form_id' => $form->id,
            'key' => 'reason',
            'label' => 'Reason for Leave',
            'field_type' => 'text',
            'field_order' => 1,
            'is_required' => true,
        ]);

        $submission = app(FormSubmissionService::class)->submit($form->load('fields'), $user, [
            'reason' => 'Family vacation',
        ]);

        $this->assertNotNull($submission);

        // Verify task was created for the submission
        $task = Task::where('form_submission_id', $submission->id)->first();
        $this->assertNotNull($task);
        $this->assertSame('pending', $task->status);
        $this->assertSame($manager->id, $task->user_id);
        $this->assertStringContainsString('Leave Request Form', $task->title);

        // Verify notification dispatched
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $manager->id,
            'notifiable_type' => User::class,
        ]);
    }

    /**
     * Test that cancelling a workflow instance cancels its pending tasks.
     */
    public function test_workflow_instance_cancellation_cancels_associated_tasks(): void
    {
        $initiator = User::factory()->create();
        $approver = User::factory()->create();
        Role::create(['name' => 'Manager']);
        $approver->assignRole('Manager');

        $workflow = Workflow::create([
            'title' => 'Travel Request',
            'status' => 'active',
            'created_by' => $initiator->id,
        ]);

        WorkflowStep::create([
            'workflow_id' => $workflow->id,
            'name' => 'Trip Approval',
            'type' => 'sequential',
            'step_order' => 1,
            'assignee_role' => 'Manager',
        ]);

        $instance = app(WorkflowEngine::class)->start($workflow, $initiator);

        $task = Task::where('workflow_instance_id', $instance->id)->first();
        $this->assertNotNull($task);
        $this->assertSame('pending', $task->status);

        // Cancel the workflow instance
        app(WorkflowEngine::class)->cancel($instance, $initiator);

        $task->refresh();
        $this->assertSame('cancelled', $task->status);
        $this->assertNotNull($task->completed_at);
        $this->assertStringContainsString('cancelled', strtolower($task->action_notes));
    }

    /**
     * Test that direct workflow assignment approval via WorkflowEngine completes the Task and clears cache.
     */
    public function test_direct_workflow_assignment_approval_completes_task_and_clears_cache(): void
    {
        $initiator = User::factory()->create(['name' => 'John Requester']);
        $approver = User::factory()->create(['name' => 'Sarah Director']);
        Role::create(['name' => 'Director']);
        $approver->assignRole('Director');

        $workflow = Workflow::create([
            'title' => 'Equipment Procurement',
            'status' => 'active',
            'created_by' => $initiator->id,
        ]);

        WorkflowStep::create([
            'workflow_id' => $workflow->id,
            'name' => 'Director Sign-off',
            'type' => 'sequential',
            'step_order' => 1,
            'assignee_role' => 'Director',
            'sla_hours' => 48,
        ]);

        $instance = app(WorkflowEngine::class)->start($workflow, $initiator);

        $task = Task::where('workflow_instance_id', $instance->id)->first();
        $this->assertNotNull($task);
        $this->assertSame('pending', $task->status);

        $assignment = $instance->assignments()->first();
        $this->assertNotNull($assignment);

        // Approve assignment via WorkflowEngine
        app(WorkflowEngine::class)->approve($assignment, $approver, 'Approved by Director directly');

        $task->refresh();
        $this->assertSame('completed', $task->status);
        $this->assertNotNull($task->completed_at);
        $this->assertStringContainsString('Approved', $task->action_notes);

        // Verify dashboard KPI cache invalidation and updated stats
        $kpiResponse = $this->actingAs($approver)->getJson('/api/v1/dashboard/kpis');
        $kpiResponse->assertOk();
        $this->assertGreaterThanOrEqual(1, $kpiResponse->json('completed_24h'));
    }

    /**
     * Test that direct workflow assignment rejection cancels the Task and records rejection.
     */
    public function test_direct_workflow_assignment_rejection_cancels_task_and_clears_cache(): void
    {
        $initiator = User::factory()->create(['name' => 'Dev Requester']);
        $approver = User::factory()->create(['name' => 'Finance Approver']);
        Role::create(['name' => 'Finance']);
        $approver->assignRole('Finance');

        $workflow = Workflow::create([
            'title' => 'Software Subscription',
            'status' => 'active',
            'created_by' => $initiator->id,
        ]);

        WorkflowStep::create([
            'workflow_id' => $workflow->id,
            'name' => 'Finance Verification',
            'type' => 'sequential',
            'step_order' => 1,
            'assignee_role' => 'Finance',
        ]);

        $instance = app(WorkflowEngine::class)->start($workflow, $initiator);

        $task = Task::where('workflow_instance_id', $instance->id)->first();
        $this->assertNotNull($task);
        $this->assertSame('pending', $task->status);

        $assignment = $instance->assignments()->first();
        $this->assertNotNull($assignment);

        // Reject assignment via WorkflowEngine
        app(WorkflowEngine::class)->reject($assignment, $approver, 'Insufficient annual department budget');

        $task->refresh();
        $this->assertSame('cancelled', $task->status);
        $this->assertNotNull($task->completed_at);
        $this->assertStringContainsString('rejected', strtolower($task->action_notes));

        // Verify approval statistics endpoint shows the rejection
        $approvalStatsResponse = $this->actingAs($approver)->getJson('/api/v1/dashboard/approval-statistics');
        $approvalStatsResponse->assertOk();
        $this->assertGreaterThanOrEqual(1, $approvalStatsResponse->json('rejected'));
    }
}
