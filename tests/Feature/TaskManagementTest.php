<?php

namespace Tests\Feature;

use App\Events\TaskOverdueEvent;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TaskManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_user_can_create_task_via_api(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Task Coordinator');
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/v1/tasks', [
            'title' => 'Process vendor invoice',
            'description' => 'Review and verify invoice #9823',
            'priority' => 'high',
            'due_at' => now()->addDays(3)->toIso8601String(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Process vendor invoice')
            ->assertJsonPath('data.priority', 'high')
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('tasks', [
            'title' => 'Process vendor invoice',
            'creator_id' => $user->id,
            'priority' => 'high',
            'status' => 'pending',
        ]);
    }

    public function test_user_can_retrieve_assigned_tasks(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Task Coordinator');
        $otherUser = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $assignedTask = Task::factory()->create([
            'title' => 'Task for current user',
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $otherTask = Task::factory()->create([
            'title' => 'Task for other user',
            'user_id' => $otherUser->id,
            'status' => 'pending',
        ]);

        $response = $this->withToken($token)->getJson('/api/v1/tasks?assigned_to_me=true');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertSame($assignedTask->uuid, $data[0]['uuid']);
    }

    public function test_filtering_tasks_by_status(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Task Coordinator');
        $token = $user->createToken('test-token')->plainTextToken;

        $pendingTask = Task::factory()->create(['title' => 'Pending Task', 'status' => 'pending']);
        $completedTask = Task::factory()->completed()->create(['title' => 'Completed Task']);
        $delegatedTask = Task::factory()->delegated()->create(['title' => 'Delegated Task']);
        $overdueTask = Task::factory()->overdue()->create(['title' => 'Overdue Task']);

        // Filter: pending
        $resPending = $this->withToken($token)->getJson('/api/v1/tasks?status=pending');
        $resPending->assertOk();
        $uuidsPending = collect($resPending->json('data'))->pluck('uuid');
        $this->assertTrue($uuidsPending->contains($pendingTask->uuid));
        $this->assertFalse($uuidsPending->contains($completedTask->uuid));

        // Filter: completed
        $resCompleted = $this->withToken($token)->getJson('/api/v1/tasks?status=completed');
        $resCompleted->assertOk();
        $uuidsCompleted = collect($resCompleted->json('data'))->pluck('uuid');
        $this->assertTrue($uuidsCompleted->contains($completedTask->uuid));
        $this->assertFalse($uuidsCompleted->contains($pendingTask->uuid));

        // Filter: delegated
        $resDelegated = $this->withToken($token)->getJson('/api/v1/tasks?status=delegated');
        $resDelegated->assertOk();
        $uuidsDelegated = collect($resDelegated->json('data'))->pluck('uuid');
        $this->assertTrue($uuidsDelegated->contains($delegatedTask->uuid));

        // Filter: overdue
        $resOverdue = $this->withToken($token)->getJson('/api/v1/tasks?status=overdue');
        $resOverdue->assertOk();
        $uuidsOverdue = collect($resOverdue->json('data'))->pluck('uuid');
        $this->assertTrue($uuidsOverdue->contains($overdueTask->uuid));
    }

    public function test_user_can_complete_a_task(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $task = Task::factory()->create([
            'user_id' => $user->id,
            'status' => 'in_progress',
        ]);

        $response = $this->withToken($token)->postJson("/api/v1/tasks/{$task->uuid}/complete", [
            'action_notes' => 'Completed verification checklist.',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.action_notes', 'Completed verification checklist.');

        $task->refresh();
        $this->assertSame('completed', $task->status);
        $this->assertNotNull($task->completed_at);
        $this->assertSame('Completed verification checklist.', $task->action_notes);
    }

    public function test_user_can_delegate_a_task(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Task Coordinator');
        $colleague = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $task = Task::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $response = $this->withToken($token)->postJson("/api/v1/tasks/{$task->uuid}/delegate", [
            'target_user_id' => $colleague->id,
            'action_notes' => 'Please take over during my absence.',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'delegated')
            ->assertJsonPath('data.action_notes', 'Please take over during my absence.');

        $task->refresh();
        $this->assertSame('delegated', $task->status);
        $this->assertSame($colleague->id, $task->user_id);
        $this->assertSame($colleague->id, $task->delegated_to_id);
        $this->assertSame($user->id, $task->delegated_by_id);
        $this->assertNotNull($task->delegated_at);
    }

    public function test_summary_metrics_endpoint(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Task Coordinator');
        $token = $user->createToken('test-token')->plainTextToken;

        // 2 assigned to user
        Task::factory()->count(2)->create(['user_id' => $user->id, 'status' => 'pending']);
        // 1 pending task for someone else
        Task::factory()->create(['status' => 'pending']);
        // 1 completed task
        Task::factory()->completed()->create();
        // 1 delegated task
        Task::factory()->delegated()->create();
        // 1 overdue task
        Task::factory()->overdue()->create();

        $response = $this->withToken($token)->getJson('/api/v1/tasks/summary');

        $response->assertOk()
            ->assertJsonStructure([
                'assigned_count',
                'pending_count',
                'completed_count',
                'delegated_count',
                'overdue_count',
            ])
            ->assertJsonPath('assigned_count', 2);
    }

    public function test_sla_command_flags_overdue_tasks(): void
    {
        Event::fake([TaskOverdueEvent::class]);

        $overdueTask = Task::factory()->create([
            'title' => 'SLA Breach Task',
            'status' => 'pending',
            'due_at' => now()->subHours(2),
            'sla_breached' => false,
        ]);

        $futureTask = Task::factory()->create([
            'title' => 'On Time Task',
            'status' => 'pending',
            'due_at' => now()->addHours(5),
            'sla_breached' => false,
        ]);

        $this->artisan('tasks:check-sla')
            ->expectsOutputToContain('Processed 1 overdue tasks with SLA breaches.')
            ->assertSuccessful();

        $overdueTask->refresh();
        $futureTask->refresh();

        $this->assertTrue($overdueTask->sla_breached);
        $this->assertFalse($futureTask->sla_breached);

        Event::assertDispatched(TaskOverdueEvent::class, fn (TaskOverdueEvent $e) => $e->task->id === $overdueTask->id);
    }
}
