<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_dashboard_kpis_endpoint(): void
    {
        $admin = User::factory()->create();
        $token = $admin->createToken('admin-token')->plainTextToken;

        Task::factory()->count(2)->create(['status' => 'pending']);
        Task::factory()->overdue()->create();

        $response = $this->withToken($token)->getJson('/api/v1/dashboard/kpis');

        $response->assertOk()
            ->assertJsonStructure([
                'active_tasks',
                'overdue_tasks',
                'pending_approvals',
                'running_workflows',
                'completed_24h',
                'completion_rate_24h',
            ]);
    }

    public function test_dashboard_delayed_processes_endpoint(): void
    {
        $admin = User::factory()->create();
        $token = $admin->createToken('admin-token')->plainTextToken;

        $task = Task::factory()->overdue()->create(['title' => 'Stalled Workflow Action']);

        $response = $this->withToken($token)->getJson('/api/v1/dashboard/delayed-processes');

        $response->assertOk()
            ->assertJsonStructure([
                'delayed_processes' => [
                    '*' => [
                        'id',
                        'uuid',
                        'title',
                        'status',
                        'priority',
                        'assignee',
                        'due_at',
                        'delayed_hours',
                        'sla_breached',
                    ],
                ],
            ]);

        $uuids = collect($response->json('delayed_processes'))->pluck('uuid');
        $this->assertTrue($uuids->contains($task->uuid));
    }

    public function test_dashboard_user_activity_endpoint(): void
    {
        $admin = User::factory()->create();
        $token = $admin->createToken('admin-token')->plainTextToken;

        Task::factory()->create(['title' => 'Activity Test Task', 'action_notes' => 'Status updated']);

        $response = $this->withToken($token)->getJson('/api/v1/dashboard/user-activity');

        $response->assertOk()
            ->assertJsonStructure([
                'activity' => [
                    '*' => [
                        'id',
                        'uuid',
                        'title',
                        'status',
                        'actor',
                        'action_notes',
                        'timestamp',
                    ],
                ],
            ]);
    }

    public function test_dashboard_approval_statistics_endpoint(): void
    {
        $admin = User::factory()->create();
        $token = $admin->createToken('admin-token')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/v1/dashboard/approval-statistics');

        $response->assertOk()
            ->assertJsonStructure([
                'approved',
                'rejected',
                'pending',
                'escalated',
                'delegated',
            ]);
    }
}
