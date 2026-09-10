<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class WorkflowAnalyticsService
{
    /**
     * Get completion time statistics (avg, min, max, median) for completed tasks.
     *
     * @return array<string, mixed>
     */
    public function getCompletionTimeStats(?string $from = null, ?string $to = null): array
    {
        $cacheKey = 'analytics:completion_times:'.($from ?? 'all').'_'.($to ?? 'all');

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($from, $to): array {
            $query = Task::query()->where('status', 'completed')->whereNotNull('completed_at');

            if ($from) {
                $query->where('completed_at', '>=', Carbon::parse($from));
            }
            if ($to) {
                $query->where('completed_at', '<=', Carbon::parse($to));
            }

            $durations = $query->get()->map(function (Task $task): float {
                return (float) $task->created_at->diffInMinutes($task->completed_at);
            })->sort()->values();

            if ($durations->isEmpty()) {
                return [
                    'completed_count' => 0,
                    'avg_duration_minutes' => 0,
                    'min_duration_minutes' => 0,
                    'max_duration_minutes' => 0,
                    'median_duration_minutes' => 0,
                ];
            }

            $count = $durations->count();
            $middle = (int) floor($count / 2);
            $median = ($count % 2 === 0)
                ? ($durations[$middle - 1] + $durations[$middle]) / 2
                : $durations[$middle];

            return [
                'completed_count' => $count,
                'avg_duration_minutes' => round($durations->avg(), 2),
                'min_duration_minutes' => round($durations->min(), 2),
                'max_duration_minutes' => round($durations->max(), 2),
                'median_duration_minutes' => round($median, 2),
            ];
        });
    }

    /**
     * Detect bottlenecks: assignees and steps with highest pending duration and overdue rates.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getBottlenecks(): array
    {
        return Cache::remember('analytics:bottlenecks', now()->addMinutes(10), function (): array {
            return User::query()
                ->whereHas('tasks', function ($query): void {
                    $query->whereIn('status', ['pending', 'in_progress']);
                })
                ->withCount([
                    'tasks as pending_tasks_count' => function ($query): void {
                        $query->whereIn('status', ['pending', 'in_progress']);
                    },
                    'tasks as overdue_tasks_count' => function ($query): void {
                        $query->overdue();
                    },
                ])
                ->orderByDesc('overdue_tasks_count')
                ->orderByDesc('pending_tasks_count')
                ->take(10)
                ->get()
                ->map(fn (User $user): array => [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->getRoleNames()->first() ?? 'Employee',
                    'pending_tasks_count' => (int) $user->pending_tasks_count,
                    'overdue_tasks_count' => (int) $user->overdue_tasks_count,
                    'overdue_rate' => $user->pending_tasks_count > 0
                        ? round(($user->overdue_tasks_count / $user->pending_tasks_count) * 100, 2)
                        : 0.0,
                ])->toArray();
        });
    }

    /**
     * Aggregate department / role performance metrics.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDepartmentPerformance(): array
    {
        return Cache::remember('analytics:department_performance', now()->addMinutes(10), function (): array {
            $users = User::with(['roles', 'tasks'])->get();
            $grouped = $users->groupBy(fn (User $u): string => $u->getRoleNames()->first() ?? 'Operations');

            $results = [];
            foreach ($grouped as $department => $deptUsers) {
                $totalTasks = 0;
                $completedTasks = 0;
                $overdueTasks = 0;

                foreach ($deptUsers as $deptUser) {
                    $totalTasks += $deptUser->tasks->count();
                    $completedTasks += $deptUser->tasks->where('status', 'completed')->count();
                    $overdueTasks += $deptUser->tasks->filter(fn (Task $t): bool => (bool) $t->sla_breached || ($t->due_at && $t->due_at->isPast() && $t->status !== 'completed'))->count();
                }

                $completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 2) : 100.0;
                $slaCompliance = $totalTasks > 0 ? round((($totalTasks - $overdueTasks) / $totalTasks) * 100, 2) : 100.0;

                $results[] = [
                    'department' => $department,
                    'member_count' => $deptUsers->count(),
                    'total_tasks' => $totalTasks,
                    'completed_tasks' => $completedTasks,
                    'overdue_tasks' => $overdueTasks,
                    'completion_rate' => $completionRate,
                    'sla_compliance_rate' => $slaCompliance,
                ];
            }

            return $results;
        });
    }

    /**
     * Overall SLA compliance rate and breach statistics.
     *
     * @return array<string, mixed>
     */
    public function getSlaComplianceMetrics(): array
    {
        return Cache::remember('analytics:sla_compliance', now()->addMinutes(10), function (): array {
            $total = Task::count();
            $breached = Task::query()->overdue()->count();
            $onTime = max(0, $total - $breached);
            $complianceRate = $total > 0 ? round(($onTime / $total) * 100, 2) : 100.0;

            return [
                'total_tasks' => $total,
                'on_time_tasks' => $onTime,
                'breached_tasks' => $breached,
                'compliance_rate' => $complianceRate,
            ];
        });
    }

    /**
     * Throughput and process efficiency metrics.
     *
     * @return array<string, mixed>
     */
    public function getProcessEfficiency(): array
    {
        return Cache::remember('analytics:process_efficiency', now()->addMinutes(10), function (): array {
            $since = now()->subDays(7);
            $createdRecent = Task::where('created_at', '>=', $since)->count();
            $completedRecent = Task::where('status', 'completed')->where('completed_at', '>=', $since)->count();

            $efficiencyRatio = $createdRecent > 0
                ? round(($completedRecent / $createdRecent) * 100, 2)
                : 100.0;

            return [
                'window_days' => 7,
                'tasks_created' => $createdRecent,
                'tasks_completed' => $completedRecent,
                'efficiency_ratio' => $efficiencyRatio,
                'net_velocity' => $completedRecent - $createdRecent,
            ];
        });
    }

    /**
     * Clear all analytics cached results.
     */
    public function clearAnalyticsCache(): void
    {
        Cache::forget('analytics:completion_times:all_all');
        Cache::forget('analytics:bottlenecks');
        Cache::forget('analytics:department_performance');
        Cache::forget('analytics:sla_compliance');
        Cache::forget('analytics:process_efficiency');
    }
}
