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

class TaskEscalatedNotification extends Notification implements ShouldQueue
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

        if ($prefService->shouldReceiveNotification($notifiable, 'database', 'task_escalated')) {
            $channels[] = 'database';
        }

        if ($prefService->shouldReceiveNotification($notifiable, 'mail', 'task_escalated')) {
            $channels[] = 'mail';
        }

        if ($prefService->shouldReceiveNotification($notifiable, 'sms', 'task_escalated')) {
            $channels[] = SimulatedSmsChannel::class;
        }

        if ($prefService->shouldReceiveNotification($notifiable, 'slack', 'task_escalated')) {
            $channels[] = WebhookChannel::class;
        }

        return $channels;
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject("URGENT: Task SLA Breached - {$this->task->title}")
            ->greeting("Attention {$notifiable->name},")
            ->line("An urgent task has breached its SLA deadline: {$this->task->title}")
            ->line("Priority: {$this->task->priority}")
            ->line('Due Date: '.($this->task->due_at ? $this->task->due_at->toFormattedDateString() : 'N/A'))
            ->action('Review Task Now', url("/api/v1/tasks/{$this->task->uuid}"))
            ->line('Immediate action or reassignment is required.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(mixed $notifiable): array
    {
        return [
            'type' => 'task_escalated',
            'task_id' => $this->task->id,
            'task_uuid' => $this->task->uuid,
            'title' => $this->task->title,
            'priority' => $this->task->priority,
            'due_at' => $this->task->due_at?->toISOString(),
            'message' => "SLA Breach Alert: Task '{$this->task->title}' is overdue.",
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
            'message' => "URGENT SLA BREACH: Task '{$this->task->title}' is overdue. Please review immediately.",
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toWebhook(mixed $notifiable): array
    {
        return [
            'text' => ":warning: *SLA BREACH ALERT*: Task *{$this->task->title}* is overdue! Assignee: {$notifiable->name}",
            'task_uuid' => $this->task->uuid,
            'priority' => $this->task->priority,
        ];
    }
}
