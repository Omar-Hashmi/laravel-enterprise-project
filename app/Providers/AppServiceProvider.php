<?php

namespace App\Providers;

use App\Models\Form;
use App\Models\Workflow;
use App\Models\WorkflowInstance;
use App\Policies\FormPolicy;
use App\Policies\WorkflowInstancePolicy;
use App\Policies\WorkflowPolicy;
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
    }
}
