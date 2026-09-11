<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_guest_is_redirected_to_login_before_reaching_the_dashboard(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirectToRoute('login');
    }

    public function test_a_new_account_is_authenticated_with_the_employee_role(): void
    {
        $response = $this->post('/register', [
            'name' => 'New Employee',
            'email' => 'new.employee@example.com',
            'role' => 'Employee',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ]);

        $response->assertRedirectToRoute('dashboard');
        $this->assertAuthenticated();
        $this->assertAuthenticatedAs(User::where('email', 'new.employee@example.com')->firstOrFail());
        $this->assertTrue(Auth::user()->hasRole('Employee'));
    }

    public function test_signup_can_create_any_configured_role(): void
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'New Manager',
            'email' => 'new.manager@example.com',
            'role' => 'Super Admin',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ]);

        $response->assertRedirectToRoute('dashboard');
        $this->assertAuthenticated();
        $this->assertTrue(Auth::user()->hasRole('Super Admin'));
    }

    public function test_all_dev_one_and_dev_two_roles_are_seeded(): void
    {
        $roles = [
            'Super Admin', 'Department Admin', 'Manager', 'Employee', 'Auditor',
            'Task Coordinator', 'Notification Manager', 'Analytics Viewer', 'Dashboard Viewer',
        ];

        foreach ($roles as $role) {
            $this->assertDatabaseHas('roles', ['name' => $role]);
        }
    }

    public function test_role_detection_returns_the_account_role_without_accepting_a_role_input(): void
    {
        $manager = User::factory()->create(['email' => 'detected.manager@example.com']);
        $manager->assignRole('Manager');

        $response = $this->getJson('/login/role?email=detected.manager@example.com');

        $response->assertOk()->assertJson(['role' => 'Manager']);
    }

    public function test_a_seeded_role_account_can_log_in(): void
    {
        $user = User::factory()->create(['email' => 'demo-manager@flowline.test', 'password' => 'password']);
        $user->assignRole('Manager');

        $response = $this->post('/login', [
            'email' => 'demo-manager@flowline.test',
            'password' => 'password',
        ]);

        $response->assertRedirectToRoute('dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_an_employee_cannot_create_a_workflow(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('Employee');
        $response = $this->actingAs($employee)->postJson('/workflows', ['title' => 'Restricted workflow']);
        $response->assertForbidden();
    }

    public function test_logout_invalidates_the_authenticated_session(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Employee');
        $this->actingAs($user);

        $response = $this->post('/logout');

        $response->assertRedirectToRoute('login');
        $this->assertGuest();
    }
}
