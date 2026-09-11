<?php

namespace App\Providers;

use App\Events\TaskAssignedEvent;
use App\Events\TaskOverdueEvent;
use App\Listeners\SendTaskNotificationListener;
use App\Listeners\WorkflowEventListener;
use App\Models\Form;
use App\Models\Task;
use App\Models\Workflow;
use App\Models\WorkflowInstance;
use App\Policies\FormPolicy;
use App\Policies\TaskPolicy;
use App\Policies\WorkflowInstancePolicy;
use App\Policies\WorkflowPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Form::class, FormPolicy::class);
        Gate::policy(Workflow::class, WorkflowPolicy::class);
        Gate::policy(WorkflowInstance::class, WorkflowInstancePolicy::class);
        Gate::policy(Task::class, TaskPolicy::class);

        Event::listen(TaskAssignedEvent::class, [SendTaskNotificationListener::class, 'handle']);
        Event::listen(TaskOverdueEvent::class, [SendTaskNotificationListener::class, 'handle']);
        Event::subscribe(WorkflowEventListener::class);
    }
}
