<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('task.view') || $user->can('task.manage') || $user->can('workflow.manage');
    }

    public function view(User $user, Task $task): bool
    {
        return $user->can('task.manage')
            || $task->user_id === $user->id
            || $task->creator_id === $user->id
            || $task->delegated_to_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('task.manage') || $user->can('workflow.manage');
    }

    public function complete(User $user, Task $task): bool
    {
        return $user->can('task.manage') || $task->user_id === $user->id;
    }

    public function delegate(User $user, Task $task): bool
    {
        return $user->can('task.manage') && ($task->creator_id === $user->id || $task->user_id === $user->id || $user->can('workflow.manage'));
    }
}
