<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Telescope\Watchers\ExceptionWatcher;
use Laravel\Telescope\Watchers\JobWatcher;
use Laravel\Telescope\Watchers\NotificationWatcher;
use Laravel\Telescope\Watchers\QueryWatcher;
use Tests\TestCase;

class InfrastructureSetupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_unauthorized_user_cannot_access_horizon_dashboard(): void
    {
        $guestResponse = $this->get('/horizon');
        $guestResponse->assertForbidden();

        $employee = User::factory()->create();
        $employee->assignRole('Employee');

        $employeeResponse = $this->actingAs($employee)->get('/horizon');
        $employeeResponse->assertForbidden();
    }

    public function test_authorized_super_admin_can_access_horizon_dashboard(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $response = $this->actingAs($admin)->get('/horizon');
        $response->assertOk();
    }

    public function test_unauthorized_user_cannot_access_telescope_dashboard(): void
    {
        $guestResponse = $this->get('/telescope');
        $guestResponse->assertForbidden();

        $employee = User::factory()->create();
        $employee->assignRole('Employee');

        $employeeResponse = $this->actingAs($employee)->get('/telescope');
        $employeeResponse->assertForbidden();
    }

    public function test_authorized_super_admin_can_access_telescope_dashboard(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $response = $this->actingAs($admin)->get('/telescope');
        $response->assertOk();
    }

    public function test_redis_connection_and_horizon_configuration_resolution(): void
    {
        // Redis Client and Store Configuration
        $this->assertSame('predis', config('database.redis.client'));
        $this->assertSame('redis', config('queue.connections.redis.driver'));
        $this->assertSame('redis', config('cache.stores.redis.driver'));

        // Horizon Supervisor Queue & Scaling Configuration
        $supervisorQueues = config('horizon.defaults.supervisor-1.queue');
        $this->assertContains('default', $supervisorQueues);
        $this->assertContains('notifications', $supervisorQueues);
        $this->assertContains('analytics', $supervisorQueues);
        $this->assertContains('tasks', $supervisorQueues);
        $this->assertSame('auto', config('horizon.defaults.supervisor-1.balance'));

        $this->assertSame(10, config('horizon.environments.production.supervisor-1.maxProcesses'));
        $this->assertSame(3, config('horizon.environments.local.supervisor-1.maxProcesses'));

        // Telescope Watchers Configuration
        $this->assertTrue(config('telescope.watchers.'.QueryWatcher::class.'.enabled'));
        $this->assertTrue(config('telescope.watchers.'.JobWatcher::class));
        $this->assertTrue(config('telescope.watchers.'.NotificationWatcher::class));
        $this->assertTrue(config('telescope.watchers.'.ExceptionWatcher::class));
    }
}
