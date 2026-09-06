<?php

namespace App\Http\Controllers;

use App\Models\Workflow;
use App\Models\WorkflowAssignment;
use App\Models\WorkflowInstance;
use App\Services\WorkflowEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkflowInstanceController extends Controller
{
    public function index(Request $request, Workflow $workflow): JsonResponse
    {
        $this->authorize('viewAny', WorkflowInstance::class);
        $user = $request->user();
        $query = $workflow->instances()->with(['currentStep', 'initiator', 'assignments']);

        if (! $user->can('workflow.manage')) {
            $roles = $user->getRoleNames();
            $query->where(function ($query) use ($user, $roles): void {
                $query->where('initiated_by', $user->id)
                    ->orWhereHas('assignments', function ($query) use ($user, $roles): void {
                        $query->where('assigned_to', $user->id)->orWhereIn('assigned_role', $roles);
                    });
            });
        }

        return response()->json($query->latest()->paginate(25));
    }

    public function store(Request $request, Workflow $workflow, WorkflowEngine $engine): JsonResponse
    {
        $this->authorize('create', WorkflowInstance::class);
        $validated = $request->validate(['data' => ['sometimes', 'array']]);
        $instance = $engine->start($workflow, $request->user(), $validated['data'] ?? []);

        return response()->json($instance->load(['workflow', 'currentStep', 'assignments']), 201);
    }

    public function show(WorkflowInstance $workflowInstance): JsonResponse
    {
        $this->authorize('view', $workflowInstance);

        return response()->json($workflowInstance->load(['workflow', 'currentStep', 'assignments.step', 'formSubmissions', 'initiator', 'auditLogs.user']));
    }

    public function cancel(Request $request, WorkflowInstance $workflowInstance, WorkflowEngine $engine): JsonResponse
    {
        $this->authorize('update', $workflowInstance);
        $instance = $engine->cancel($workflowInstance, $request->user());

        return response()->json($instance->load(['currentStep', 'assignments']));
    }

    public function approve(Request $request, WorkflowAssignment $assignment, WorkflowEngine $engine): JsonResponse
    {
        return $this->respond($request, $assignment, $engine, 'approve');
    }

    public function reject(Request $request, WorkflowAssignment $assignment, WorkflowEngine $engine): JsonResponse
    {
        return $this->respond($request, $assignment, $engine, 'reject');
    }

    private function respond(Request $request, WorkflowAssignment $assignment, WorkflowEngine $engine, string $decision): JsonResponse
    {
        $instance = $assignment->instance()->firstOrFail();
        $this->authorize($decision, [$instance, $assignment]);
        $validated = $request->validate(['comment' => ['nullable', 'string', 'max:5000']]);
        $instance = $engine->{$decision}($assignment, $request->user(), $validated['comment'] ?? null);

        return response()->json($instance->load(['currentStep', 'assignments']));
    }
}
