<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Models\WorkflowAssignment;
use App\Services\AdminDashboardService;
use App\Services\WorkflowAnalyticsService;
use App\Services\WorkflowEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaskController extends Controller
{
    /**
     * Display a paginated listing of tasks with filters.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Task::query()->with(['user', 'creator', 'delegatedBy', 'delegatedTo']);

        // Filter: assigned_to_me
        if ($request->boolean('assigned_to_me')) {
            $query->assignedTo($request->user()->id);
        }

        // Filter: status
        if ($request->filled('status')) {
            $status = (string) $request->query('status');
            match ($status) {
                'assigned' => $query->assignedTo($request->user()->id),
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

        $perPage = (int) $request->query('per_page', 15);
        $tasks = $query->latest()->paginate($perPage);

        return TaskResource::collection($tasks);
    }

    /**
     * Get summary metrics for tasks.
     */
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'assigned_count' => Task::query()->assignedTo($user->id)->count(),
            'pending_count' => Task::query()->pending()->count(),
            'completed_count' => Task::query()->completed()->count(),
            'delegated_count' => Task::query()->delegated()->count(),
            'overdue_count' => Task::query()->overdue()->count(),
        ]);
    }

    /**
     * Store a newly created task.
     */
    public function store(StoreTaskRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['creator_id'] = $request->user()->id;

        $task = Task::create($validated);

        return (new TaskResource($task->load(['user', 'creator'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified task.
     */
    public function show(Task $task): TaskResource
    {
        return new TaskResource($task->load(['user', 'creator', 'delegatedBy', 'delegatedTo', 'workflowInstance', 'formSubmission']));
    }

    /**
     * Mark the task as completed.
     */
    public function complete(Request $request, Task $task): TaskResource
    {
        $validated = $request->validate([
            'action_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $task->markComplete($validated['action_notes'] ?? null);

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
                    app(WorkflowEngine::class)->approve($assignment, $actor, $validated['action_notes'] ?? 'Approved via Task API');
                } catch (\Throwable) {
                    $assignment->update([
                        'status' => 'approved',
                        'comment' => $validated['action_notes'] ?? 'Approved via Task API',
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

        return new TaskResource($task->fresh()->load(['user', 'creator', 'delegatedBy', 'delegatedTo']));
    }

    /**
     * Delegate the task to another user.
     */
    public function delegate(Request $request, Task $task): TaskResource
    {
        $validated = $request->validate([
            'target_user_id' => ['required', 'integer', 'exists:users,id'],
            'action_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $task->delegateTo(
            (int) $validated['target_user_id'],
            $request->user()->id,
            $validated['action_notes'] ?? null
        );

        return new TaskResource($task->fresh()->load(['user', 'creator', 'delegatedBy', 'delegatedTo']));
    }
}
