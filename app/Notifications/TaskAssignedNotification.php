<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use App\Notifications\Channels\SimulatedSmsChannel;
use App\Notifications\Channels\WebhookChannel;
use App\Services\NotificationPreferenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Task $task)
    {
        $this->onQueue('notifications');
    }

    /**
     * Get the notification's delivery channels dynamically based on user preferences.
     *
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        if (! $notifiable instanceof User) {
            return ['database'];
        }

        $prefService = app(NotificationPreferenceService::class);
        $channels = [];

        if ($prefService->shouldReceiveNotification($notifiable, 'database', 'task_assigned')) {
            $channels[] = 'database';
        }

        if ($prefService->shouldReceiveNotification($notifiable, 'mail', 'task_assigned')) {
            $channels[] = 'mail';
        }

        if ($prefService->shouldReceiveNotification($notifiable, 'sms', 'task_assigned')) {
            $channels[] = SimulatedSmsChannel::class;
        }

        if ($prefService->shouldReceiveNotification($notifiable, 'slack', 'task_assigned')) {
            $channels[] = WebhookChannel::class;
        }

        return $channels;
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New Task Assigned: {$this->task->title}")
            ->greeting("Hello {$notifiable->name},")
            ->line("You have been assigned a new task: {$this->task->title}")
            ->line("Priority: {$this->task->priority}")
            ->line('Due Date: '.($this->task->due_at ? $this->task->due_at->toFormattedDateString() : 'N/A'))
            ->action('View Task', url("/api/v1/tasks/{$this->task->uuid}"))
            ->line('Thank you for using Flowline Operations OS.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(mixed $notifiable): array
    {
        return [
            'type' => 'task_assigned',
            'task_id' => $this->task->id,
            'task_uuid' => $this->task->uuid,
            'title' => $this->task->title,
            'priority' => $this->task->priority,
            'due_at' => $this->task->due_at?->toISOString(),
            'message' => "You have been assigned task: {$this->task->title}",
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(mixed $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    /**
     * @return array<string, mixed>
     */
    public function toSms(mixed $notifiable): array
    {
        return [
            'phone' => $notifiable->phone ?? 'simulated-phone',
            'message' => "Flowline: New task assigned - {$this->task->title}. Due: ".($this->task->due_at ? $this->task->due_at->toDateString() : 'N/A'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toWebhook(mixed $notifiable): array
    {
        return [
            'text' => "Task Assigned: *{$this->task->title}* to {$notifiable->name} (Priority: {$this->task->priority})",
            'task_uuid' => $this->task->uuid,
            'assignee' => $notifiable->email,
        ];
    }
}
