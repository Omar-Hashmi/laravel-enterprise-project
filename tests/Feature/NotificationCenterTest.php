<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use App\Notifications\Channels\SimulatedSmsChannel;
use App\Notifications\Channels\WebhookChannel;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskEscalatedNotification;
use App\Services\NotificationPreferenceService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_user_can_retrieve_notifications_and_filter_read_unread(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $task = Task::factory()->create();

        // Dispatch 2 notifications
        $user->notify(new TaskAssignedNotification($task));
        $user->notify(new TaskEscalatedNotification($task));

        $this->assertSame(2, $user->notifications()->count());
        $this->assertSame(2, $user->unreadNotifications()->count());

        // Mark 1 as read
        $user->unreadNotifications()->first()->markAsRead();

        // 1. All notifications
        $allRes = $this->withToken($token)->getJson('/api/v1/notifications?filter=all');
        $allRes->assertOk();
        $this->assertCount(2, $allRes->json('data'));

        // 2. Unread notifications
        $unreadRes = $this->withToken($token)->getJson('/api/v1/notifications?filter=unread');
        $unreadRes->assertOk();
        $this->assertCount(1, $unreadRes->json('data'));

        // 3. Read notifications
        $readRes = $this->withToken($token)->getJson('/api/v1/notifications?filter=read');
        $readRes->assertOk();
        $this->assertCount(1, $readRes->json('data'));

        // 4. Unread count endpoint
        $countRes = $this->withToken($token)->getJson('/api/v1/notifications/unread-count');
        $countRes->assertOk()->assertJson(['unread_count' => 1]);
    }

    public function test_user_can_mark_single_and_all_notifications_as_read(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $task = Task::factory()->create();

        $user->notify(new TaskAssignedNotification($task));
        $user->notify(new TaskEscalatedNotification($task));

        $firstNotification = $user->unreadNotifications()->first();

        // Mark single as read
        $resSingle = $this->withToken($token)->postJson("/api/v1/notifications/{$firstNotification->id}/read");
        $resSingle->assertOk()->assertJsonPath('message', 'Notification marked as read');

        $this->assertSame(1, $user->fresh()->unreadNotifications()->count());

        // Mark all as read
        $resAll = $this->withToken($token)->postJson('/api/v1/notifications/read-all');
        $resAll->assertOk()->assertJsonPath('message', 'All notifications marked as read');

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }

    public function test_user_can_delete_notification(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $task = Task::factory()->create();

        $user->notify(new TaskAssignedNotification($task));
        $notification = $user->notifications()->first();

        $response = $this->withToken($token)->deleteJson("/api/v1/notifications/{$notification->id}");
        $response->assertOk()->assertJsonPath('message', 'Notification deleted successfully');

        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }

    public function test_user_can_get_and_update_notification_preferences(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Get initial preferences (empty)
        $getRes = $this->withToken($token)->getJson('/api/v1/notifications/preferences');
        $getRes->assertOk()->assertJsonStructure(['preferences']);

        // Update preferences
        $updateRes = $this->withToken($token)->putJson('/api/v1/notifications/preferences', [
            'preferences' => [
                ['channel' => 'mail', 'notification_type' => 'all', 'enabled' => false],
                ['channel' => 'sms', 'notification_type' => 'task_assigned', 'enabled' => true],
            ],
        ]);

        $updateRes->assertOk()
            ->assertJsonPath('message', 'Notification preferences updated successfully');

        $this->assertDatabaseHas('user_notification_preferences', [
            'user_id' => $user->id,
            'channel' => 'mail',
            'notification_type' => 'all',
            'enabled' => false,
        ]);

        $this->assertDatabaseHas('user_notification_preferences', [
            'user_id' => $user->id,
            'channel' => 'sms',
            'notification_type' => 'task_assigned',
            'enabled' => true,
        ]);
    }

    public function test_task_assigned_notification_delivers_to_database_when_enabled(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $user->id]);

        $user->notify(new TaskAssignedNotification($task));

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'notifiable_type' => $user->getMorphClass(),
        ]);
    }

    public function test_user_preferences_suppress_disabled_channels(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $user->id]);
        $prefService = app(NotificationPreferenceService::class);

        // Default channels when no preferences exist
        $defaultVia = (new TaskAssignedNotification($task))->via($user);
        $this->assertContains('database', $defaultVia);
        $this->assertContains('mail', $defaultVia);

        // Disable mail channel
        $prefService->updatePreferences($user, [
            ['channel' => 'mail', 'notification_type' => 'all', 'enabled' => false],
        ]);

        $this->assertFalse($prefService->shouldReceiveNotification($user, 'mail', 'task_assigned'));

        $filteredVia = (new TaskAssignedNotification($task))->via($user);
        $this->assertNotContains('mail', $filteredVia);
        $this->assertContains('database', $filteredVia);

        // Enable SMS and Slack
        $prefService->updatePreferences($user, [
            ['channel' => 'sms', 'notification_type' => 'task_assigned', 'enabled' => true],
            ['channel' => 'slack', 'notification_type' => 'task_assigned', 'enabled' => true],
        ]);

        $multiVia = (new TaskAssignedNotification($task))->via($user);
        $this->assertContains(SimulatedSmsChannel::class, $multiVia);
        $this->assertContains(WebhookChannel::class, $multiVia);
    }

    public function test_simulated_sms_channel_execution(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                return str_contains($message, 'Simulated SMS sent to');
            });

        $user = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $user->id]);

        $channel = new SimulatedSmsChannel;
        $notification = new TaskAssignedNotification($task);

        $channel->send($user, $notification);
    }

    public function test_webhook_channel_execution(): void
    {
        Http::fake([
            'https://hooks.slack.com/*' => Http::response(['ok' => true], 200),
        ]);

        config(['services.webhook.url' => 'https://hooks.slack.com/services/test-token']);

        $user = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $user->id]);

        $channel = new WebhookChannel;
        $notification = new TaskAssignedNotification($task);

        $channel->send($user, $notification);

        Http::assertSent(function ($request) use ($task): bool {
            return str_contains($request->url(), 'hooks.slack.com')
                && str_contains($request->data()['text'], $task->title);
        });
    }
}
