<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebNavigationAndViewsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_tasks_view_requires_authentication(): void
    {
        $response = $this->get('/tasks');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_tasks_view_with_filters(): void
    {
        $user = User::factory()->create(['name' => 'Sam Worker']);
        $user->assignRole('Employee');

        $task = Task::create([
            'title' => 'Review Customer Contract',
            'user_id' => $user->id,
            'creator_id' => $user->id,
            'status' => 'pending',
            'priority' => 'high',
            'due_at' => now()->addDay(),
        ]);

        $response = $this->actingAs($user)->get('/tasks');
        $response->assertOk();
        $response->assertSee('Task Operations');
        $response->assertSee('Review Customer Contract');
        $response->assertSee('My Tasks');

        // Test filter: status=pending
        $pendingResponse = $this->actingAs($user)->get('/tasks?status=pending');
        $pendingResponse->assertOk();
        $pendingResponse->assertSee('Review Customer Contract');

        // Test filter: assigned_to_me=1
        $assignedResponse = $this->actingAs($user)->get('/tasks?assigned_to_me=1');
        $assignedResponse->assertOk();
        $assignedResponse->assertSee('Review Customer Contract');
    }

    public function test_user_can_complete_task_from_web_view(): void
    {
        $user = User::factory()->create();
        $task = Task::create([
            'title' => 'Verify Purchase Order',
            'user_id' => $user->id,
            'creator_id' => $user->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->post("/tasks/{$task->uuid}/complete", [
            'action_notes' => 'Validated and completed by web user',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $task->refresh();
        $this->assertSame('completed', $task->status);
        $this->assertSame('Validated and completed by web user', $task->action_notes);
    }

    public function test_user_can_delegate_task_from_web_view(): void
    {
        $user = User::factory()->create();
        $colleague = User::factory()->create(['name' => 'Colleague']);
        $task = Task::create([
            'title' => 'Inventory Audit',
            'user_id' => $user->id,
            'creator_id' => $user->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->post("/tasks/{$task->uuid}/delegate", [
            'target_user_id' => $colleague->id,
            'action_notes' => 'Delegating to colleague for shift coverage',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $task->refresh();
        $this->assertSame($colleague->id, $task->user_id);
        $this->assertSame('delegated', $task->status);
    }

    public function test_admin_dashboard_view_access_control(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('Employee');

        $manager = User::factory()->create();
        $manager->assignRole('Manager');

        // Employee without elevated permissions is forbidden
        $forbiddenResponse = $this->actingAs($employee)->get('/admin/dashboard');
        $forbiddenResponse->assertForbidden();

        // Manager can access executive admin dashboard
        $managerResponse = $this->actingAs($manager)->get('/admin/dashboard');
        $managerResponse->assertOk();
        $managerResponse->assertSee('Admin Dashboard');
        $managerResponse->assertSee('Active Tasks');
        $managerResponse->assertSee('Approval Statistics');
    }

    public function test_workflow_analytics_view_access_control(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('Employee');

        $manager = User::factory()->create();
        $manager->assignRole('Manager');

        // Employee without elevated permissions is forbidden
        $forbiddenResponse = $this->actingAs($employee)->get('/analytics');
        $forbiddenResponse->assertForbidden();

        // Manager can access analytics view
        $managerResponse = $this->actingAs($manager)->get('/analytics');
        $managerResponse->assertOk();
        $managerResponse->assertSee('Workflow Analytics');
        $managerResponse->assertSee('Average Cycle Time');
        $managerResponse->assertSee('Operational Bottlenecks');
    }
}
