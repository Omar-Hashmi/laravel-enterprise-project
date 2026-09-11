<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkflowAssignment;
use App\Services\AdminDashboardService;
use App\Services\WorkflowAnalyticsService;
use App\Services\WorkflowEngine;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TaskViewController extends Controller
{
    /**
     * Display the tasks management view.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Task::class);
        $user = $request->user();
        $query = Task::query()->with(['user', 'creator', 'delegatedBy', 'delegatedTo', 'workflowInstance']);

        // Filter: assigned_to_me
        if ($request->boolean('assigned_to_me') || $request->query('assigned') === 'me') {
            $query->assignedTo($user->id);
        }

        // Filter: status
        $status = (string) $request->query('status');
        if (! empty($status)) {
            match ($status) {
                'assigned' => $query->assignedTo($user->id),
                'pending' => $query->pending(),
                'completed' => $query->completed(),
                'delegated' => $query->delegated(),
                'overdue' => $query->overdue(),
                default => $query->where('status', $status),
            };
        }

        // Filter: priority
        if ($request->filled('priority')) {
            $query->where('priority', (string) $request->query('priority'));
        }

        $tasks = $query->latest('due_at')->latest('id')->paginate(15)->withQueryString();

        // Summary metrics
        $summary = [
            'assigned_count' => Task::query()->assignedTo($user->id)->count(),
            'pending_count' => Task::query()->pending()->count(),
            'completed_count' => Task::query()->completed()->count(),
            'delegated_count' => Task::query()->delegated()->count(),
            'overdue_count' => Task::query()->overdue()->count(),
            'total_count' => Task::count(),
        ];

        $users = User::orderBy('name')->get(['id', 'name', 'email']);

        return view('tasks.index', compact('tasks', 'summary', 'users', 'status'));
    }

    /**
     * Complete a task from web UI.
     */
    public function complete(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('complete', $task);
        $validated = $request->validate([
            'action_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $task->markComplete($validated['action_notes'] ?? 'Completed via Web UI');

        if ($task->workflow_instance_id) {
            $assignment = null;
            if (! empty($task->metadata['assignment_id'])) {
                $assignment = WorkflowAssignment::find($task->metadata['assignment_id']);
            }
            if (! $assignment) {
                $assignment = WorkflowAssignment::where('workflow_instance_id', $task->workflow_instance_id)
                    ->where('status', 'pending')
                    ->first();
            }

            if ($assignment && $assignment->status === 'pending') {
                $actor = $request->user() ?? $task->user;
                try {
                    app(WorkflowEngine::class)->approve($assignment, $actor, $validated['action_notes'] ?? 'Approved via Web UI');
                } catch (\Throwable) {
                    $assignment->update([
                        'status' => 'approved',
                        'comment' => $validated['action_notes'] ?? 'Approved via Web UI',
                        'responded_at' => now(),
                    ]);
                }
            }
        }

        try {
            app(WorkflowAnalyticsService::class)->clearAnalyticsCache();
            app(AdminDashboardService::class)->clearDashboardCache();
        } catch (\Throwable) {
        }

        return redirect()->back()->with('success', "Task '{$task->title}' marked as completed.");
    }

    /**
     * Delegate a task from web UI.
     */
    public function delegate(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('delegate', $task);
        $validated = $request->validate([
            'target_user_id' => ['required', 'integer', 'exists:users,id'],
            'action_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $task->delegateTo(
            (int) $validated['target_user_id'],
            $request->user()->id,
            $validated['action_notes'] ?? null
        );

        return redirect()->back()->with('success', "Task '{$task->title}' successfully delegated.");
    }
}
