<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class WorkflowAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_analytics_completion_times_endpoint(): void
    {
        $admin = User::factory()->create();
        $token = $admin->createToken('admin-token')->plainTextToken;

        Task::factory()->completed()->create([
            'created_at' => now()->subHours(4),
            'completed_at' => now()->subHours(2),
        ]);

        $response = $this->withToken($token)->getJson('/api/v1/analytics/completion-times');

        $response->assertOk()
            ->assertJsonStructure([
                'completed_count',
                'avg_duration_minutes',
                'min_duration_minutes',
                'max_duration_minutes',
                'median_duration_minutes',
            ]);
    }

    public function test_analytics_bottlenecks_endpoint(): void
    {
        $admin = User::factory()->create();
        $token = $admin->createToken('admin-token')->plainTextToken;

        $employee = User::factory()->create();
        $employee->assignRole('Employee');

        Task::factory()->create(['user_id' => $employee->id, 'status' => 'pending']);
        Task::factory()->overdue()->create(['user_id' => $employee->id]);

        $response = $this->withToken($token)->getJson('/api/v1/analytics/bottlenecks');

        $response->assertOk()
            ->assertJsonStructure([
                'bottlenecks' => [
                    '*' => [
                        'user_id',
                        'name',
                        'email',
                        'role',
                        'pending_tasks_count',
                        'overdue_tasks_count',
                        'overdue_rate',
                    ],
                ],
            ]);
    }

    public function test_analytics_department_performance_endpoint(): void
    {
        $admin = User::factory()->create();
        $token = $admin->createToken('admin-token')->plainTextToken;

        $manager = User::factory()->create();
        $manager->assignRole('Manager');
        Task::factory()->completed()->create(['user_id' => $manager->id]);

        $response = $this->withToken($token)->getJson('/api/v1/analytics/department-performance');

        $response->assertOk()
            ->assertJsonStructure([
                'departments' => [
                    '*' => [
                        'department',
                        'member_count',
                        'total_tasks',
                        'completed_tasks',
                        'overdue_tasks',
                        'completion_rate',
                        'sla_compliance_rate',
                    ],
                ],
            ]);
    }

    public function test_analytics_sla_compliance_endpoint(): void
    {
        $admin = User::factory()->create();
        $token = $admin->createToken('admin-token')->plainTextToken;

        Task::factory()->create(['status' => 'pending']);
        Task::factory()->overdue()->create();

        $response = $this->withToken($token)->getJson('/api/v1/analytics/sla-compliance');

        $response->assertOk()
            ->assertJsonStructure([
                'total_tasks',
                'on_time_tasks',
                'breached_tasks',
                'compliance_rate',
            ]);
    }

    public function test_analytics_efficiency_endpoint(): void
    {
        $admin = User::factory()->create();
        $token = $admin->createToken('admin-token')->plainTextToken;

        Task::factory()->create(['created_at' => now()->subDay()]);
        Task::factory()->completed()->create(['completed_at' => now()->subHours(2)]);

        $response = $this->withToken($token)->getJson('/api/v1/analytics/efficiency');

        $response->assertOk()
            ->assertJsonStructure([
                'window_days',
                'tasks_created',
                'tasks_completed',
                'efficiency_ratio',
                'net_velocity',
            ]);
    }

    public function test_analytics_caches_results_in_cache_store(): void
    {
        $admin = User::factory()->create();
        $token = $admin->createToken('admin-token')->plainTextToken;

        Cache::flush();

        $this->assertFalse(Cache::has('analytics:sla_compliance'));

        $this->withToken($token)->getJson('/api/v1/analytics/sla-compliance');

        $this->assertTrue(Cache::has('analytics:sla_compliance'));
    }
}
