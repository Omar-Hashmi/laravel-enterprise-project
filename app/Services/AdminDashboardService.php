<?php

namespace App\Services;

use App\Models\Task;
use App\Models\WorkflowAssignment;
use App\Models\WorkflowInstance;
use Illuminate\Support\Facades\Cache;

class AdminDashboardService
{
    /**
     * Get high-level KPI metrics for the executive dashboard.
     *
     * @return array<string, mixed>
     */
    public function getKpis(): array
    {
        return Cache::remember('dashboard:kpis', now()->addMinutes(5), function (): array {
            $activeTasks = Task::query()->whereIn('status', ['pending', 'in_progress'])->count();
            $overdueTasks = Task::query()->overdue()->count();
            $pendingApprovals = WorkflowAssignment::query()->where('status', 'pending')->count();
            $runningWorkflows = WorkflowInstance::query()->where('status', 'in_progress')->count();

            $completed24h = Task::query()
                ->where('status', 'completed')
                ->where('completed_at', '>=', now()->subHours(24))
                ->count();

            $created24h = Task::query()
                ->where('created_at', '>=', now()->subHours(24))
                ->count();

            $rate24h = $created24h > 0 ? round(($completed24h / $created24h) * 100, 2) : 100.0;

            return [
                'active_tasks' => $activeTasks,
                'overdue_tasks' => $overdueTasks,
                'pending_approvals' => $pendingApprovals,
                'running_workflows' => $runningWorkflows,
                'completed_24h' => $completed24h,
                'completion_rate_24h' => $rate24h,
            ];
        });
    }

    /**
     * Get delayed processes exceeding their SLA.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDelayedProcesses(int $limit = 10): array
    {
        return Cache::remember("dashboard:delayed_processes:{$limit}", now()->addMinutes(5), function () use ($limit): array {
            return Task::query()->overdue()
                ->with(['user', 'creator'])
                ->orderBy('due_at')
                ->take($limit)
                ->get()
                ->map(fn (Task $task): array => [
                    'id' => $task->id,
                    'uuid' => $task->uuid,
                    'title' => $task->title,
                    'status' => $task->status,
                    'priority' => $task->priority,
                    'assignee' => $task->user?->name ?? 'Unassigned',
                    'due_at' => $task->due_at?->toISOString(),
                    'delayed_hours' => $task->due_at ? round(now()->diffInHours($task->due_at, false) * -1, 1) : 0,
                    'sla_breached' => (bool) $task->sla_breached,
                ])->toArray();
        });
    }

    /**
     * Get recent user activity across tasks and workflows.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getUserActivity(int $limit = 15): array
    {
        return Cache::remember("dashboard:user_activity:{$limit}", now()->addMinutes(2), function () use ($limit): array {
            return Task::query()
                ->with(['user', 'creator', 'delegatedTo'])
                ->latest('updated_at')
                ->take($limit)
                ->get()
                ->map(fn (Task $task): array => [
                    'id' => $task->id,
                    'uuid' => $task->uuid,
                    'title' => $task->title,
                    'status' => $task->status,
                    'actor' => $task->user?->name ?? 'System',
                    'action_notes' => $task->action_notes,
                    'timestamp' => $task->updated_at->toISOString(),
                ])->toArray();
        });
    }

    /**
     * Get aggregate approval statistics.
     *
     * @return array<string, int>
     */
    public function getApprovalStatistics(): array
    {
        return Cache::remember('dashboard:approval_stats', now()->addMinutes(5), function (): array {
            return [
                'approved' => WorkflowAssignment::where('status', 'approved')->count(),
                'rejected' => WorkflowAssignment::where('status', 'rejected')->count(),
                'pending' => WorkflowAssignment::where('status', 'pending')->count(),
                'escalated' => WorkflowAssignment::where('status', 'escalated')->count(),
                'delegated' => Task::where('status', 'delegated')->count(),
            ];
        });
    }

    /**
     * Clear all admin dashboard cache keys.
     */
    public function clearDashboardCache(): void
    {
        Cache::forget('dashboard:kpis');
        Cache::forget('dashboard:delayed_processes:10');
        Cache::forget('dashboard:user_activity:15');
        Cache::forget('dashboard:approval_stats');
    }
}
