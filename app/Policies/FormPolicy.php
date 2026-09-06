<?php

namespace App\Policies;

use App\Models\Form;
use App\Models\User;
use App\Models\Workflow;

class FormPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('workflow.view');
    }

    public function view(User $user, Form $form): bool
    {
        return $user->can('workflow.view') || $form->workflow?->created_by === $user->id;
    }

    public function create(User $user, ?Workflow $workflow = null): bool
    {
        return $user->can('form.manage') || $user->can('workflow.manage') || $workflow?->created_by === $user->id;
    }

    public function update(User $user, Form $form): bool
    {
        return $user->can('form.manage') || $user->can('workflow.manage') || $form->workflow?->created_by === $user->id;
    }

    public function delete(User $user, Form $form): bool
    {
        return $this->update($user, $form);
    }

    public function submit(User $user, Form $form): bool
    {
        return $user->can('form.submit');
    }
}
