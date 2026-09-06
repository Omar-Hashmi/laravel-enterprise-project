<?php

use App\Jobs\ProcessWorkflowAutoActions;
use App\Jobs\ProcessWorkflowEscalations;
use App\Jobs\ProcessWorkflowSlaMonitoring;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new ProcessWorkflowEscalations)->everyFiveMinutes()->withoutOverlapping();
Schedule::job(new ProcessWorkflowSlaMonitoring)->everyFiveMinutes()->withoutOverlapping();
Schedule::job(new ProcessWorkflowAutoActions)->everyMinute()->withoutOverlapping();
