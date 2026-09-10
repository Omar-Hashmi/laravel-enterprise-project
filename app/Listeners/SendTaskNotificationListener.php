<?php

namespace App\Listeners;

use App\Events\TaskAssignedEvent;
use App\Events\TaskOverdueEvent;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskEscalatedNotification;

class SendTaskNotificationListener
{
    /**
     * Handle incoming task events and dispatch notifications.
     */
    public function handle(object $event): void
    {
        if ($event instanceof TaskAssignedEvent) {
            $task = $event->task;
            if ($task->user) {
                $task->user->notify(new TaskAssignedNotification($task));
            }
        } elseif ($event instanceof TaskOverdueEvent) {
            $task = $event->task;
            if ($task->user) {
                $task->user->notify(new TaskEscalatedNotification($task));
            }
        }
    }
}
