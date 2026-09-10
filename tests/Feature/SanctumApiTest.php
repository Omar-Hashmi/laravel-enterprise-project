<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SanctumApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_user_can_login_with_valid_credentials_and_receive_token(): void
    {
        $user = User::factory()->create([
            'email' => 'api.user@example.com',
            'password' => 'secret-password',
        ]);
        $user->assignRole('Manager');

        $response = $this->postJson('/api/v1/login', [
            'email' => 'api.user@example.com',
            'password' => 'secret-password',
            'device_name' => 'test-suite',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'token',
                'token_type',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'roles',
                    'permissions',
                ],
            ])
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.email', 'api.user@example.com');

        $token = $response->json('token');
        $this->assertNotEmpty($token);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'test-suite',
        ]);
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'api.user@example.com',
            'password' => 'secret-password',
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'api.user@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_authenticated_user_can_access_me_endpoint(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Employee');
        $token = $user->createToken('auth-token')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/v1/me');

        $response->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'name',
                    'email',
                    'roles',
                    'permissions',
                    'created_at',
                    'updated_at',
                ],
            ]);
    }

    public function test_unauthenticated_user_cannot_access_me_endpoint(): void
    {
        $response = $this->getJson('/api/v1/me');

        $response->assertUnauthorized();
    }

    public function test_user_can_logout_and_revoke_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('logout-token')->plainTextToken;
        [$tokenId] = explode('|', $token, 2);

        $this->assertDatabaseHas('personal_access_tokens', ['id' => $tokenId]);

        $response = $this->withToken($token)->postJson('/api/v1/logout');

        $response->assertOk()
            ->assertJson(['message' => 'Token revoked successfully']);

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);

        // Clear resolved guard state and verify revoked token cannot access protected endpoints
        $this->app['auth']->forgetGuards();
        $unauthorizedResponse = $this->withToken($token)->getJson('/api/v1/me');
        $unauthorizedResponse->assertUnauthorized();
    }
}
