<?php

namespace App\Console\Commands;

use App\Events\TaskOverdueEvent;
use App\Models\Task;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckTaskSlaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:check-sla';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan open tasks and mark SLA breaches for overdue tasks';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $overdueTasks = Task::query()
            ->where('status', '!=', 'completed')
            ->where('status', '!=', 'cancelled')
            ->where('sla_breached', false)
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->get();

        $count = 0;
        foreach ($overdueTasks as $task) {
            $task->update(['sla_breached' => true]);
            Log::warning("Task SLA breached: Task UUID {$task->uuid} (ID: {$task->id}) is overdue.");
            TaskOverdueEvent::dispatch($task);
            $count++;
        }

        $this->info("Processed {$count} overdue tasks with SLA breaches.");

        return Command::SUCCESS;
    }
}
